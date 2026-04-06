// assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    initializePageNavigation();
    initializeUploadArea();
});

function initializePageNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const pageSections = document.querySelectorAll('.page-section');

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const page = item.dataset.page;
            
            navItems.forEach(n => n.classList.remove('active'));
            pageSections.forEach(s => s.classList.remove('active'));
            
            item.classList.add('active');
            const pageSection = document.getElementById(page);
            if (pageSection) {
                pageSection.classList.add('active');
            }
        });
    });
}

function initializeUploadArea() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');

    if (!uploadArea) return;

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileInput.files = e.dataTransfer.files;
        uploadArea.classList.remove('dragover');
    });

    if (uploadForm) {
        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(uploadForm);

            fetch('../php/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    fileInput.value = '';
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(err => alert('❌ Error: ' + err.message));
        });
    }
}

function deleteFile(fileId) {
    if (confirm('Delete this ticket?')) {
        fetch('../php/delete_file.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({file_id: fileId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Ticket deleted!');
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        });
    }
}

function approveFile(fileId) {
    if (confirm('Resolve this ticket?')) {
        fetch('../php/approve_file.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({file_id: fileId, status: 'approved'})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Ticket resolved!');
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        });
    }
}

function rejectFile(fileId) {
    const reason = prompt('Enter reason for closing:');
    if (reason) {
        fetch('../php/approve_file.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({file_id: fileId, status: 'disapproved', reason: reason})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('❌ Ticket closed!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function deleteUser(userId) {
    if (confirm('Delete this user?')) {
        fetch('../php/delete_user.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_id: userId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ User deleted!');
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        });
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchFiles');
    const filterSelect = document.getElementById('filterStatus');

    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const table = document.getElementById('filesTable');
            if (table) {
                table.querySelectorAll('tbody tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    }

    if (filterSelect) {
        filterSelect.addEventListener('change', function(e) {
            const status = e.target.value;
            const table = document.getElementById('filesTable');
            if (table) {
                table.querySelectorAll('tbody tr').forEach(row => {
                    if (!status) {
                        row.style.display = '';
                    } else {
                        const statusCell = row.querySelector('td:nth-child(3)');
                        if (statusCell) {
                            const cellText = statusCell.textContent.toLowerCase();
                            row.style.display = cellText.includes(status) ? '' : 'none';
                        }
                    }
                });
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', initializeSearch);