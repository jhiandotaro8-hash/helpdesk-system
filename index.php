<?php
// index.php
include 'config/db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: user/index.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$user['id'], 'login', 'User logged in']);

        if ($user['role'] == 'admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: user/index.php');
        }
        exit;
    } else {
        $error = '❌ Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Support HelpDesk</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                <div class="login-logo">
                    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <!-- Outer circle -->
                        <circle cx="100" cy="100" r="95" fill="none" stroke="#4A9FD8" stroke-width="8"/>
                        
                        <!-- Head (dark navy) -->
                        <circle cx="100" cy="70" r="35" fill="#003366"/>
                        
                        <!-- Headphones left -->
                        <rect x="55" y="45" width="14" height="32" rx="7" fill="#4A9FD8"/>
                        
                        <!-- Headphones right -->
                        <rect x="131" y="45" width="14" height="32" rx="7" fill="#4A9FD8"/>
                        
                        <!-- Headphones band -->
                        <path d="M 62 48 Q 100 28 138 48" stroke="#4A9FD8" stroke-width="10" fill="none" stroke-linecap="round"/>
                        
                        <!-- Microphone boom -->
                        <line x1="120" y1="72" x2="136" y2="62" stroke="white" stroke-width="7" stroke-linecap="round"/>
                        <circle cx="139" cy="59" r="5" fill="white"/>
                        
                        <!-- Body/Shoulders -->
                        <ellipse cx="100" cy="125" rx="38" ry="42" fill="#003366"/>
                        
                        <!-- Info circle -->
                        <circle cx="100" cy="157" r="26" fill="#4A9FD8"/>
                        <text x="100" y="164" font-size="30" font-weight="bold" fill="white" text-anchor="middle" font-family="Arial">i</text>
                    </svg>
                </div>

                <div class="login-header">
                    <h1>Support HelpDesk</h1>
                    <p>Customer Support Management System</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                </form>

                <div class="login-footer">
                    <h4>📋 Demo Accounts:</h4>
                    <table class="demo-table">
                        <tr>
                            <td><strong>Support Agent:</strong></td>
                            <td>admin / admin123</td>
                        </tr>
                        <tr>
                            <td><strong>Customer:</strong></td>
                            <td>user / user123</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>