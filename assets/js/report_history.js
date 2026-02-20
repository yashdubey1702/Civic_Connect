// Filter reports by status

function filterReports() {
    const statusFilter = document.getElementById('reportStatusFilter');
    if (!statusFilter) return;

    const value = statusFilter.value;
    const rows = document.querySelectorAll('.report-row');

    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        row.style.display =
            value === 'all' || status === value.toLowerCase() ? '' : 'none';
    });
}

// Update & Delete

function openUpdateModal(reportId, category, description, lat, lng) {
    document.getElementById('update_report_id').value = reportId;
    document.getElementById('update_category').value = category;
    document.getElementById('update_description').value = description;
    document.getElementById('update_lat').value = lat;
    document.getElementById('update_lng').value = lng;

    const modal = document.getElementById('updateModal');
    if (modal) modal.style.display = "block";
}

function closeUpdateModal() {
    const modal = document.getElementById('updateModal');
    const form = document.getElementById('updateForm');
    if (modal) modal.style.display = "none";
    if (form) form.reset();
}

function confirmDelete(reportId) {
    if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        deleteReport(reportId);
    }
}

function deleteReport(reportId) {
    fetch('reports/delete_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: reportId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the report row from table
            const reportRow = document.getElementById('report-' + reportId);
            if (reportRow) {
                reportRow.remove();
            }
            showNotification('Report deleted successfully!', 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting report.', 'error');
    });
}

// Update form submission
document.getElementById('updateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('id', document.getElementById('update_report_id').value);
    formData.append('category', document.getElementById('update_category').value);
    formData.append('description', document.getElementById('update_description').value);
    formData.append('lat', document.getElementById('update_lat').value);
    formData.append('lng', document.getElementById('update_lng').value);
    
    const imageInput = document.getElementById('update_image');
    if (imageInput.files[0]) {
        formData.append('image', imageInput.files[0]);
    }
    
    fetch('reports/update_report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Report updated successfully!', 'success');
            closeUpdateModal();
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating report.', 'error');
    });
});

function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModal').style.display = "block";
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = "none";
}

// Close modals when clicking on X
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.addEventListener('click', function() {
        const modal = this.closest('.modal');
        modal.style.display = "none";
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('updateModal')) {
        closeUpdateModal();
    }
    if (event.target == document.getElementById('imageModal')) {
        closeImageModal();
    }
}

function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.user-sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Initialize status filter
    const statusFilter = document.getElementById('reportStatusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterReports);
    }
});

// Add notification styles
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease-out;
    }
    
    .notification.success {
        background: var(--success);
    }
    
    .notification.error {
        background: var(--danger);
    }
    
    .notification.warning {
        background: var(--warning);
        color: var(--dark);
    }
    
    .notification.fade-out {
        animation: slideOut 0.3s ease-in forwards;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

