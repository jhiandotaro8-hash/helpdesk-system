<?php
// admin/index.php
include '../config/db.php';
checkLogin();

if ($_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$statsStmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM files) as total_files,
        (SELECT COUNT(*) FROM files WHERE status = 'pending') as pending_files,
        (SELECT COUNT(*) FROM files WHERE status = 'approved') as approved_files
");
$statsStmt->execute();
$stats = $statsStmt->fetch();

$pendingStmt = $pdo->prepare("
    SELECT f.*, u.username 
    FROM files f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.status = 'pending' 
    ORDER BY f.uploaded_at DESC
");
$pendingStmt->execute();
$pendingFiles = $pendingStmt->fetchAll();

$allFilesStmt = $pdo->prepare("
    SELECT f.*, u.username 
    FROM files f 
    JOIN users u ON f.user_id = u.id 
    ORDER BY f.uploaded_at DESC
");
$allFilesStmt->execute();
$allFiles = $allFilesStmt->fetchAll();

$usersStmt = $pdo->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$usersStmt->execute();
$users = $usersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Support HelpDesk</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- SIDEBAR -->
        <aside class="sidebar admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="100" cy="100" r="95" fill="none" stroke="white" stroke-width="8"/>
                        <circle cx="100" cy="70" r="35" fill="white"/>
                        <rect x="55" y="45" width="14" height="32" rx="7" fill="#003366"/>
                        <rect x="131" y="45" width="14" height="32" rx="7" fill="#003366"/>
                        <path d="M 62 48 Q 100 28 138 48" stroke="#003366" stroke-width="10" fill="none" stroke-linecap="round"/>
                        <line x1="120" y1="72" x2="136" y2="62" stroke="#003366" stroke-width="7" stroke-linecap="round"/>
                        <circle cx="139" cy="59" r="5" fill="#003366"/>
                    </svg>
                </div>
                <h2>Support</h2>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-page="dashboard">
                    📊 Dashboard
                </a>
                <a href="#" class="nav-item" data-page="pending">
                    ⏳ New Tickets
                </a>
                <a href="#" class="nav-item" data-page="allfiles">
                    🎫 All Tickets
                </a>
                <a href="#" class="nav-item" data-page="users">
                    👥 Manage Users
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <p>Agent:</p>
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </div>
                <a href="../php/logout.php" class="btn-logout">🚪 Logout</a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <h1>Admin Dashboard</h1>
                </div>
                <div class="header-right">
                    <span class="time-display" id="timeDisplay"></span>
                </div>
            </header>

            <div class="content-area">
                <!-- DASHBOARD PAGE -->
                <section id="dashboard" class="page-section active">
                    <h2>System Overview 📈</h2>

                    <div class="stats-grid admin-stats">
                        <div class="stat-card">
                            <div class="stat-icon">👥</div>
                            <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🎫</div>
                            <div class="stat-number"><?php echo $stats['total_files']; ?></div>
                            <div class="stat-label">Total Tickets</div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-icon">⏳</div>
                            <div class="stat-number"><?php echo $stats['pending_files']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon">✅</div>
                            <div class="stat-number"><?php echo $stats['approved_files']; ?></div>
                            <div class="stat-label">Resolved</div>
                        </div>
                    </div>

                    <div class="card">
                        <h3>📊 Quick Statistics</h3>
                        <div class="stats-info">
                            <p>Total Customers: <strong><?php echo $stats['total_users']; ?></strong></p>
                            <p>Total Tickets: <strong><?php echo $stats['total_files']; ?></strong></p>
                            <p>Pending Tickets: <strong><?php echo $stats['pending_files']; ?></strong></p>
                            <p>Resolved Tickets: <strong><?php echo $stats['approved_files']; ?></strong></p>
                        </div>
                    </div>
                </section>

                <!-- PENDING TICKETS PAGE -->
                <section id="pending" class="page-section">
                    <div class="card">
                        <h2>⏳ Pending Tickets (<?php echo count($pendingFiles); ?>)</h2>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Ticket Name</th>
                                        <th>From Customer</th>
                                        <th>Size</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pendingFiles)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">✅ No pending tickets!</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pendingFiles as $file): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                                <td><?php echo htmlspecialchars($file['username']); ?></td>
                                                <td><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                                                <td><?php echo date('M d, Y H:i', strtotime($file['uploaded_at'])); ?></td>
                                                <td>
                                                    <button class="btn-approve" onclick="approveFile(<?php echo $file['id']; ?>)">
                                                        ✅ Resolve
                                                    </button>
                                                    <button class="btn-reject" onclick="rejectFile(<?php echo $file['id']; ?>)">
                                                        ❌ Close
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ALL TICKETS PAGE -->
                <section id="allfiles" class="page-section">
                    <div class="card">
                        <h2>🎫 All Tickets (<?php echo count($allFiles); ?>)</h2>
                        <div class="filter-bar">
                            <input type="text" id="searchFiles" placeholder="🔍 Search tickets...">
                            <select id="filterStatus">
                                <option value="">All Status</option>
                                <option value="pending">⏳ Pending</option>
                                <option value="approved">✅ Resolved</option>
                                <option value="disapproved">❌ Closed</option>
                            </select>
                        </div>
                        <div class="table-container">
                            <table class="data-table" id="filesTable">
                                <thead>
                                    <tr>
                                        <th>Ticket Name</th>
                                        <th>From Customer</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allFiles as $file): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                            <td><?php echo htmlspecialchars($file['username']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $file['status']; ?>">
                                                    <?php 
                                                    if ($file['status'] == 'pending') echo '⏳ Pending';
                                                    elseif ($file['status'] == 'approved') echo '✅ Resolved';
                                                    else echo '❌ Closed';
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($file['uploaded_at'])); ?></td>
                                            <td>
                                                <button class="btn-delete" onclick="deleteFile(<?php echo $file['id']; ?>)">
                                                    🗑️ Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- MANAGE USERS PAGE -->
                <section id="users" class="page-section">
                    <div class="card">
                        <h2>👥 Manage Customers (<?php echo count($users); ?>)</h2>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                        🗑️ Delete
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Self</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Update time display
        function updateTime() {
            const now = new Date();
            document.getElementById('timeDisplay').textContent = now.toLocaleTimeString();
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>