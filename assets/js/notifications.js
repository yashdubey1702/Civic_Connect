(function () {
    const appRoot = '/town_issues/';
    const endpoint = `${appRoot}reports/get_notifications.php`;
    const markReadEndpoint = `${appRoot}reports/mark_notification_read.php`;

    let drawer = null;
    let overlay = null;
    let list = null;
    let countLabel = null;
    let triggerButtons = [];
    let currentCount = 0;

    function escapeHtml(value) {
        return (value == null ? '' : String(value))
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(value) {
        if (!value) return '';

        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return '';

        return date.toLocaleDateString('en-IN', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }

    function badgeText(count) {
        return count > 99 ? '99+' : String(count);
    }

    function statusClass(status) {
        return String(status || 'info').toLowerCase().replace(/\s+/g, '-');
    }

    function notificationUrl(value) {
        const url = String(value || '');

        if (!url) return '';
        if (/^(https?:|mailto:|tel:|\/)/i.test(url)) return url;

        return `${appRoot}${url.replace(/^\/+/, '')}`;
    }

    function ensureDrawer() {
        if (drawer) return;

        overlay = document.createElement('div');
        overlay.className = 'notification-drawer-overlay';
        overlay.hidden = true;
        overlay.addEventListener('click', closeDrawer);

        drawer = document.createElement('aside');
        drawer.className = 'notification-drawer';
        drawer.id = 'notificationDrawer';
        drawer.setAttribute('aria-label', 'Notifications');
        drawer.setAttribute('aria-hidden', 'true');
        drawer.innerHTML = `
            <div class="notification-drawer-header">
                <div>
                    <h2>Notifications</h2>
                    <p id="notificationCountLabel">Loading updates...</p>
                </div>
                <button type="button" class="notification-close-btn" aria-label="Close notifications">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notification-drawer-list" id="notificationList">
                <div class="notification-loading">Loading notifications...</div>
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.appendChild(drawer);

        list = drawer.querySelector('#notificationList');
        countLabel = drawer.querySelector('#notificationCountLabel');
        drawer.querySelector('.notification-close-btn').addEventListener('click', closeDrawer);
        list.addEventListener('click', handleNotificationClick);

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });
    }

    function setBadgeCount(count) {
        currentCount = Math.max(0, Number(count) || 0);

        triggerButtons.forEach(button => {
            let badge = button.querySelector('.notification-badge');

            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                button.appendChild(badge);
            }

            badge.textContent = badgeText(currentCount);
            badge.hidden = currentCount === 0;
            button.setAttribute('aria-label', `${currentCount} notifications`);
        });

        if (countLabel) {
            countLabel.textContent = currentCount === 1 ? '1 current update' : `${currentCount} current updates`;
        }
    }

    function renderNotifications(data) {
        const notifications = Array.isArray(data.notifications) ? data.notifications : [];
        const count = Number(data.count || notifications.length || 0);

        setBadgeCount(count);

        if (!list) return;

        if (!notifications.length) {
            list.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications</h3>
                    <p>You are all caught up.</p>
                </div>
            `;
            return;
        }

        list.innerHTML = notifications.map(item => {
            const content = `
                <div class="notification-item-icon status-${statusClass(item.status)}">
                    <i class="fas fa-circle"></i>
                </div>
                <div class="notification-item-body">
                    <div class="notification-item-title">${escapeHtml(item.title)}</div>
                    <div class="notification-item-message">${escapeHtml(item.message)}</div>
                    ${item.meta ? `<div class="notification-item-meta">${escapeHtml(item.meta)}</div>` : ''}
                    <div class="notification-item-date">${escapeHtml(formatDate(item.created_at))}</div>
                </div>
            `;

            if (item.url) {
                const url = notificationUrl(item.url);
                return `<a class="notification-item" href="${escapeHtml(url)}" data-notification-id="${escapeHtml(item.id)}">${content}</a>`;
            }

            return `<button type="button" class="notification-item" data-notification-id="${escapeHtml(item.id)}">${content}</button>`;
        }).join('');
    }

    function renderEmptyIfNeeded() {
        if (!list || list.querySelector('.notification-item')) return;

        list.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <h3>No notifications</h3>
                <p>You are all caught up.</p>
            </div>
        `;
    }

    function markNotificationRead(notificationId) {
        return fetch(markReadEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ notification_id: notificationId })
        })
            .then(response => response.json())
            .then(data => {
                if (!data || !data.success) {
                    throw new Error(data?.message || 'Unable to update notification');
                }
            });
    }

    function handleNotificationClick(event) {
        const item = event.target.closest('.notification-item[data-notification-id]');
        if (!item) return;

        const notificationId = item.getAttribute('data-notification-id');
        const href = item.getAttribute('href');

        event.preventDefault();

        markNotificationRead(notificationId)
            .then(() => {
                item.remove();
                setBadgeCount(currentCount - 1);
                renderEmptyIfNeeded();

                if (href) {
                    window.location.href = href;
                }
            })
            .catch(error => {
                console.error('Mark notification read failed:', error);

                if (href) {
                    window.location.href = href;
                }
            });
    }

    function loadNotifications() {
        ensureDrawer();

        return fetch(endpoint, {
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(response => response.json())
            .then(data => {
                if (!data || !data.success) {
                    throw new Error(data?.message || 'Unable to load notifications');
                }
                renderNotifications(data);
            })
            .catch(error => {
                console.error('Notification load failed:', error);
                setBadgeCount(0);
                if (countLabel) countLabel.textContent = 'Unable to load updates';
                if (list) {
                    list.innerHTML = '<div class="notification-empty"><i class="fas fa-exclamation-circle"></i><h3>Unable to load notifications</h3><p>Please try again later.</p></div>';
                }
            });
    }

    function openDrawer() {
        ensureDrawer();
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        overlay.hidden = false;
        document.body.classList.add('notification-drawer-open');
        drawer.querySelector('.notification-close-btn')?.focus();
        loadNotifications();
    }

    function closeDrawer() {
        if (!drawer || !overlay) return;

        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        overlay.hidden = true;
        document.body.classList.remove('notification-drawer-open');
    }

    document.addEventListener('DOMContentLoaded', () => {
        triggerButtons = Array.from(document.querySelectorAll('.notification-btn'));
        if (!triggerButtons.length) return;

        triggerButtons.forEach(button => {
            button.type = 'button';
            button.setAttribute('aria-haspopup', 'dialog');
            button.setAttribute('aria-controls', 'notificationDrawer');
            button.addEventListener('click', openDrawer);
        });

        loadNotifications();
    });
})();
