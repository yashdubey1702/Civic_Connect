<?php

require_once __DIR__ . '/Database.php';

// Stores PHP sessions in MySQL so Vercel serverless invocations can share login state.
class DatabaseSessionHandler implements SessionHandlerInterface
{
    private ?mysqli $conn = null;
    private int $ttl;

    public function __construct(int $ttl)
    {
        $this->ttl = max(300, $ttl);
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        if ($this->conn instanceof mysqli) {
            $this->conn->close();
            $this->conn = null;
        }

        return true;
    }

    public function read(string $id): string
    {
        $conn = $this->connection();
        $now = time();
        $stmt = $conn->prepare('SELECT data FROM app_sessions WHERE id = ? AND expires_at >= ? LIMIT 1');
        $stmt->bind_param('si', $id, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? (string) $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $conn = $this->connection();
        $updatedAt = time();
        $expiresAt = $updatedAt + $this->ttl;
        $stmt = $conn->prepare(
            'REPLACE INTO app_sessions (id, data, updated_at, expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssii', $id, $data, $updatedAt, $expiresAt);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function destroy(string $id): bool
    {
        $conn = $this->connection();
        $stmt = $conn->prepare('DELETE FROM app_sessions WHERE id = ?');
        $stmt->bind_param('s', $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function gc(int $max_lifetime): int|false
    {
        $conn = $this->connection();
        $now = time();
        $stmt = $conn->prepare('DELETE FROM app_sessions WHERE expires_at < ?');
        $stmt->bind_param('i', $now);
        $ok = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $ok ? $affected : false;
    }

    private function connection(): mysqli
    {
        if ($this->conn instanceof mysqli) {
            return $this->conn;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
        $this->ensureTable();

        return $this->conn;
    }

    private function ensureTable(): void
    {
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS app_sessions (
                id varchar(128) NOT NULL,
                data mediumblob NOT NULL,
                updated_at int unsigned NOT NULL,
                expires_at int unsigned NOT NULL,
                PRIMARY KEY (id),
                KEY idx_app_sessions_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}
