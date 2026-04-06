<?php
// user/index.php
include '../config/db.php';
checkLogin();

if ($_SESSION['role'] != 'user') {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, file_name, file_size, status, uploaded_at 
    FROM files 
    WHERE user_id = ? 
    ORDER BY uploaded_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$files = $stmt->fetchAll();

$statStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'disapproved' THEN 1 ELSE 0 END) as disapproved
    FROM files 
    WHERE user_id = ?
");
$statStmt->execute([$_SESSION['user_id']]);
$stats = $statStmt->fetch();

$notifStmt = $pdo->prepare("
    SELECT id, message, is_read, created_at FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$notifStmt->execute([$_SESSION['user_id']]);
$notifications = $notifStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Support HelpDesk</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="100" cy="100" r="95" fill="none" stroke="#4A9FD8" stroke-width="8"/>
                        <circle cx="100" cy="70" r="35" fill="#4A9FD8"/>
                        <rect x="55" y="45" width="14" height="32" rx="7" fill="white"/>
                        <rect x="131" y="45" width="14" height="32" rx="7" fill="white"/>
                        <path d="M 62 48 Q 100 28 138 48" stroke="white" stroke-width="10" fill="none" stroke-linecap="round"/>
                        <line x1="120" y1="72" x2="136" y2="62" stroke="white" stroke-width="7" stroke-linecap="round"/>
                        <circle cx="139" cy="59" r="5" fill="white"/>
                        <ellipse cx="100" cy="125" rx="38" ry="42" fill="white" opacity="0.2"/>
                    </svg>
                </div>
                <h2>HelpDesk</h2>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-page="dashboard">
                    📊 Dashboard
                </a>
                <a href="#" class="nav-item" data-page="upload">
                    🎫 Submit Ticket
                </a>
                <a href="#" class="nav-item" data-page="myfiles">
                    📋 My Tickets
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <p>Logged in as:</p>
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </div>
                <a href="../php/logout.php" class="btn-logout">🚪 Logout</a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="notification-bell">
                        <span>🔔</span>
                        <span class="badge" id="notif-count">
                            <?php echo count(array_filter($notifications, fn($n) => !$n['is_read'])); ?>
                        </span>
                    </div>
                </div>
            </header>

            <div class="content-area">
                <!-- DASHBOARD PAGE -->
                <section id="dashboard" class="page-section active">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">🎫</div>
                            <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                            <div class="stat-label">Total Tickets</div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-icon">⏳</div>
                            <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon">✅</div>
                            <div class="stat-number"><?php echo $stats['approved'] ?? 0; ?></div>
                            <div class="stat-label">Resolved</div>
                        </div>
                        <div class="stat-card danger">
                            <div class="stat-icon">❌</div>
                            <div class="stat-number"><?php echo $stats['disapproved'] ?? 0; ?></div>
                            <div class="stat-label">Closed</div>
                        </div>
                    </div>

                    <div class="card">
                        <h3>📬 Recent Updates</h3>
                        <div class="notifications-list">
                            <?php if (empty($notifications)): ?>
                                <p class="text-center">No notifications yet</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- SUBMIT TICKET PAGE -->
                <section id="upload" class="page-section">
                    <div class="card">
                        <h2>🎫 Submit New Ticket</h2>
                        <form id="uploadForm" class="upload-form">
                            <div class="upload-area" id="uploadArea">
                                <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <h3>Drag & Drop Files Here</h3>
                                <p>or click to browse</p>
                                <input type="file" id="fileInput" name="files" multiple hidden>
                            </div>
                            <button type="submit" class="btn-primary">📤 Submit Ticket</button>
                        </form>
                    </div>
                </section>

                <!-- MY TICKETS PAGE -->
                <section id="myfiles" class="page-section">
                    <div class="card">
                        <h2>📋 My Tickets</h2>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Ticket Name</th>
                                        <th>Size</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($files)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No tickets submitted yet</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($files as $file): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                                <td><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                                                <td><?php echo date('M d, Y', strtotime($file['uploaded_at'])); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $file['status']; ?>">
                                                        <?php 
                                                        if ($file['status'] == 'pending') echo '⏳ Pending';
                                                        elseif ($file['status'] == 'approved') echo '✅ Resolved';
                                                        else echo '❌ Closed';
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn-delete" onclick="deleteFile(<?php echo $file['id']; ?>)">
                                                        🗑️ Delete
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
            </div>
        </main>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>