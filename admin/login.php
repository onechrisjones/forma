<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include common configuration
require_once dirname(__DIR__) . '/config.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = json_decode(file_get_contents(CONFIG_DIR . '/users.json'), true);
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($config['users'][$username]) && 
        password_verify($password, $config['users'][$username]['password'])) {
        $_SESSION['forma_user'] = $username;
        header('Location: /admin');
        exit;
    }

    $error = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Forma</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/core.css">
    <style>
        body {
            position: relative;
            overflow: hidden;
        }
        
        /* Animated background */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .bg-gradient-1 {
            animation: slide 15s ease-in-out infinite alternate;
            background-image: linear-gradient(-60deg, rgba(252, 190, 52, 0.1) 50%, rgba(30, 30, 30, 0.8) 50%);
            opacity: 0.5;
        }

        .bg-gradient-2 {
            animation-direction: alternate-reverse;
            animation-duration: 20s;
            background-image: linear-gradient(-60deg, rgba(30, 30, 30, 0.9) 50%, rgba(217, 158, 0, 0.1) 50%);
            opacity: 0.5;
        }

        .bg-gradient-3 {
            animation-duration: 25s;
            background-image: linear-gradient(-60deg, rgba(37, 37, 38, 0.8) 50%, rgba(230, 169, 18, 0.1) 50%);
            opacity: 0.5;
        }

        @keyframes slide {
            0% {
                transform: translateX(-15%);
            }
            100% {
                transform: translateX(15%);
            }
        }
        
        .login-container {
            position: relative;
            z-index: 10;
            background-color: rgba(37, 37, 38, 0.8);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        @keyframes pulse-glow {
            0% {
                filter: drop-shadow(0 0 5px rgba(252, 190, 52, 0.5));
            }
            50% {
                filter: drop-shadow(0 0 20px rgba(252, 190, 52, 0.8));
            }
            100% {
                filter: drop-shadow(0 0 5px rgba(252, 190, 52, 0.5));
            }
        }
        
        .logo-svg {
            width: 100px;
            height: auto;
            margin-bottom: 10px;
            animation: pulse-glow 3s ease-in-out infinite;
            fill: #fcbe34;
            color: #fcbe34;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .back-link {
            color: #fcbe34;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: #e6a912;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Animated background layers -->
    <div class="bg-animation bg-gradient-1"></div>
    <div class="bg-animation bg-gradient-2"></div>
    <div class="bg-animation bg-gradient-3"></div>

    <div class="login-container">
        <div class="logo-container">
            <svg class="logo-svg" width="400" height="280" viewBox="0 0 400 280" version="1.1" xmlns="http://www.w3.org/2000/svg">
                <g stroke="none" stroke-width="1" fill="currentColor" fill-rule="evenodd">
                    <path d="M0,0 L400,0 L320,80 L0,80 L0,0 Z"></path>
                    <path d="M0,100 L300,100 L220,180 L0,180 L0,100 Z"></path>
                    <path d="M0,200 L80,200 L0,280 L0,200 Z"></path>
                </g>
            </svg>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn"><i class="fas fa-lock"></i> Login</button>
        </form>
        
        <div class="back-to-site">
            <a href="/" class="back-link"><i class="fas fa-arrow-left"></i> Back to site</a>
        </div>
    </div>
</body>
</html> 