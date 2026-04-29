ALTER TABLE users
  MODIFY user_type ENUM('citizen','volunteer','municipal_admin','ward_admin','super_admin') NOT NULL;

CREATE TABLE IF NOT EXISTS volunteer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    ward_no VARCHAR(50) DEFAULT NULL,
    skills TEXT DEFAULT NULL,
    availability VARCHAR(100) DEFAULT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Suspended') DEFAULT 'Pending',
    admin_note TEXT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_volunteer_user (user_id),
    KEY idx_volunteer_profiles_status (status),
    KEY idx_volunteer_profiles_ward (ward_no)
);

CREATE TABLE IF NOT EXISTS volunteer_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    volunteer_user_id INT NOT NULL,
    assigned_by INT NOT NULL,
    status ENUM(
        'Assigned',
        'Accepted',
        'In Progress',
        'Completed',
        'Verified',
        'Rejected',
        'Cancelled'
    ) DEFAULT 'Assigned',
    assigned_note TEXT DEFAULT NULL,
    completion_note TEXT DEFAULT NULL,
    proof_image VARCHAR(255) DEFAULT NULL,
    admin_review_note TEXT DEFAULT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_volunteer_tasks_report (report_id),
    KEY idx_volunteer_tasks_user (volunteer_user_id),
    KEY idx_volunteer_tasks_status (status)
);

CREATE TABLE IF NOT EXISTS volunteer_task_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    old_status VARCHAR(50) DEFAULT NULL,
    new_status VARCHAR(50) NOT NULL,
    note TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_volunteer_task_updates_task (task_id),
    KEY idx_volunteer_task_updates_user (user_id)
);
