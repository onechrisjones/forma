<?php
/**
 * Forma CMS System Check
 * Minimal system requirements checker
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========================================
// SYSTEM CHECKS
// ========================================

$checks = [];
$critical_failed = false;

// PHP Version Check
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4.0', '>=');
$checks[] = [
    'group' => 'PHP',
    'name' => 'PHP Version (≥7.4.0)',
    'status' => $php_ok,
    'critical' => true,
    'message' => $php_ok ? "PHP $php_version" : "PHP $php_version (too old)"
];
if (!$php_ok) $critical_failed = true;

// Required Extensions
$required_extensions = ['json', 'fileinfo', 'session', 'mbstring'];
$missing_extensions = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}
$ext_ok = empty($missing_extensions);
$checks[] = [
    'group' => 'PHP',
    'name' => 'Required Extensions',
    'status' => $ext_ok,
    'critical' => true,
    'message' => $ext_ok ? 'All required extensions loaded' : 'Missing: ' . implode(', ', $missing_extensions)
];
if (!$ext_ok) $critical_failed = true;

// Directory Permissions
$required_dirs = ['content', 'uploads', 'feeds', 'config'];
$failed_dirs = [];
foreach ($required_dirs as $dir) {
    $full_path = __DIR__ . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($full_path) || !is_writable($full_path)) {
        $failed_dirs[] = $dir;
    }
}
$dirs_ok = empty($failed_dirs);
$checks[] = [
    'group' => 'Filesystem',
    'name' => 'Directory Permissions',
    'status' => $dirs_ok,
    'critical' => true,
    'message' => $dirs_ok ? 'All directories writable' : 'Check: ' . implode(', ', $failed_dirs)
];
if (!$dirs_ok) $critical_failed = true;

// .htaccess Check
$htaccess_ok = file_exists(__DIR__ . '/.htaccess');
$checks[] = [
    'group' => 'Server',
    'name' => '.htaccess File',
    'status' => $htaccess_ok,
    'critical' => true,
    'message' => $htaccess_ok ? 'Present' : 'Missing .htaccess file'
];
if (!$htaccess_ok) $critical_failed = true;

// mod_rewrite Check (simplified)
$rewrite_ok = function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules());
$checks[] = [
    'group' => 'Server',
    'name' => 'Apache mod_rewrite',
    'status' => $rewrite_ok,
    'critical' => true,
    'message' => $rewrite_ok ? 'Enabled' : 'May not be enabled'
];
if (!$rewrite_ok) $critical_failed = true;

// Optional Checks
$optional_extensions = ['gd', 'zip', 'curl'];
$missing_optional = [];
foreach ($optional_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_optional[] = $ext;
    }
}
if (!empty($missing_optional)) {
    $checks[] = [
        'group' => 'Optional',
        'name' => 'Recommended Extensions',
        'status' => false,
        'critical' => false,
        'message' => 'Missing: ' . implode(', ', $missing_optional)
    ];
}

// Group checks by category
$grouped_checks = [];
foreach ($checks as $check) {
    $grouped_checks[$check['group']][] = $check;
}

$overall_status = !$critical_failed;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forma CMS System Check</title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwcHgiIGhlaWdodD0iMjgwcHgiIHZpZXdCb3g9IjAgMCA0MDAgMjgwIj48ZyBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0iI2ZjYmUzNCIgZmlsbC1ydWxlPSJldmVub2RkIj48cGF0aCBkPSJNMCwwIEw0MDAsMCBMMzIwLDgwIEwwLDgwIEwwLDAgWiI+PC9wYXRoPjxwYXRoIGQ9Ik0wLDEwMCBMMzAwLDEwMCBMMjIwLDE4MCBMMCwxODAgTDAsMTAwIFoiPjwvcGF0aD48cGF0aCBkPSJNMCwyMDAgTDgwLDIwMCBMMCwyODAgTDAsMjAwIFoiPjwvcGF0aD48L2c+PC9zdmc+">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts: Chakra Petch -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #fcbe34;
            --primary-end: #e6a912;
            --primary-dark: #d99e00;
            --bg: #1e1e1e;
            --bg-end: #252526;
            --text: #d4d4d4;
            --text-muted: #888888;
            --border: #3e3e42;
            --hover-bg: rgba(255, 255, 255, 0.1);
            --error: #f44336;
            --error-end: #d32f2f;
            --success: #4caf50;
            --success-end: #388e3c;
            --warning: #ff9800;
            --warning-end: #f57c00;
            --footer-bg: #2d2d2d;
            --footer-bg-end: #252526;
            --accent: #fcbe34;
            --accent-hover: #e6a912;
            --accent-text: #121212;
            --font-family: 'Chakra Petch', sans-serif;
        }
        
        * {
            font-family: var(--font-family);
            box-sizing: border-box;
        }
        
        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .navbar {
            background-color: var(--bg-end);
            border-bottom: 1px solid var(--border);
        }
        
        .navbar-brand {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.75rem;
        }
        
        .navbar-brand svg {
            fill: var(--primary);
            color: var(--primary);
        }
        
        .container-main {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }
        
        .logo-container {
            margin-bottom: 1.5rem;
        }
        
        .logo-svg {
            color: var(--primary);
            filter: drop-shadow(0 0 10px rgba(252, 190, 52, 0.3));
        }
        
        .main-title {
            color: var(--primary);
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .status-banner {
            background-color: var(--bg-end);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 3rem;
            text-align: center;
        }
        
        .status-banner.success {
            border-color: var(--success);
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .status-banner.error {
            border-color: var(--error);
            background-color: rgba(244, 67, 54, 0.1);
        }
        
        .status-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .status-banner.success .status-icon {
            color: var(--success);
        }
        
        .status-banner.error .status-icon {
            color: var(--error);
        }
        
        .status-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .check-group {
            background-color: var(--bg-end);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .check-group:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .group-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .group-icon {
            font-size: 1.2rem;
        }
        
        .group-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: auto;
        }
        
        .group-status.pass {
            background-color: var(--success);
        }
        
        .group-status.fail {
            background-color: var(--error);
        }
        
        .check-item {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: background-color 0.2s;
        }
        
        .check-item:last-child {
            border-bottom: none;
        }
        
        .check-item:hover {
            background-color: var(--hover-bg);
        }
        
        .check-item.failed {
            background-color: rgba(244, 67, 54, 0.08);
        }
        
        .check-status {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            flex-shrink: 0;
            position: relative;
        }
        
        .check-status.pass {
            background-color: var(--success);
        }
        
        .check-status.fail {
            background-color: var(--error);
        }
        
        .check-status.warn {
            background-color: var(--warning);
        }
        
        .check-status::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: white;
        }
        
        .check-name {
            font-weight: 500;
            min-width: 180px;
        }
        
        .check-message {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .check-item.failed .check-message {
            color: #ffcdd2;
        }
        
        .actions {
            margin-top: 3rem;
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--accent-text);
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-end);
            border-color: var(--primary-end);
            color: var(--accent-text);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(252, 190, 52, 0.3);
        }
        
        .footer {
            background-color: var(--footer-bg);
            border-top: 1px solid var(--border);
            padding: 2rem 0;
            text-align: center;
            margin-top: 3rem;
        }
        
        .footer-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .footer-brand {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .footer-logo {
            width: 24px;
            height: 18px;
            color: var(--primary);
        }
        
        .footer-text {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .container-main {
                padding: 1rem;
            }
            
            .main-title {
                font-size: 2rem;
            }
            
            .check-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .check-name {
                min-width: auto;
            }
            
            .group-header {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                <svg width="32" height="24" viewBox="0 0 400 280" class="me-2">
                    <g stroke="none" stroke-width="1" fill="currentColor" fill-rule="evenodd">
                        <path d="M0,0 L400,0 L320,80 L0,80 L0,0 Z"></path>
                        <path d="M0,100 L300,100 L220,180 L0,180 L0,100 Z"></path>
                        <path d="M0,200 L80,200 L0,280 L0,200 Z"></path>
                    </g>
                </svg>
                Forma
            </a>
        </div>
    </nav>

    <div class="container-main">
        <!-- Header -->
        <div class="header-section">
            <div class="logo-container">
                <svg width="80" height="56" viewBox="0 0 400 280" class="logo-svg">
                    <g stroke="none" stroke-width="1" fill="currentColor" fill-rule="evenodd">
                        <path d="M0,0 L400,0 L320,80 L0,80 L0,0 Z"></path>
                        <path d="M0,100 L300,100 L220,180 L0,180 L0,100 Z"></path>
                        <path d="M0,200 L80,200 L0,280 L0,200 Z"></path>
                    </g>
                </svg>
            </div>
            <h1 class="main-title">System Check</h1>
            <p class="subtitle">Verifying server requirements for Forma CMS</p>
        </div>
        
        <!-- Status Banner -->
        <div class="status-banner <?php echo $overall_status ? 'success' : 'error'; ?>">
            <div class="status-icon">
                <i class="fas <?php echo $overall_status ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
            </div>
            <h2 class="status-title"><?php echo $overall_status ? 'System Ready' : 'Issues Found'; ?></h2>
            <p class="mb-0"><?php echo $overall_status 
                ? 'All critical requirements are met. Forma CMS should work properly.' 
                : 'Please fix the highlighted issues before proceeding.'; ?></p>
        </div>
        
        <!-- Check Groups -->
        <?php foreach ($grouped_checks as $group_name => $group_checks): ?>
            <?php 
            $group_has_failures = false;
            $group_critical_failures = false;
            foreach ($group_checks as $check) {
                if (!$check['status']) {
                    $group_has_failures = true;
                    if ($check['critical']) {
                        $group_critical_failures = true;
                    }
                }
            }
            
            // Icon mapping for groups
            $group_icons = [
                'PHP' => 'fa-code',
                'Filesystem' => 'fa-folder',
                'Server' => 'fa-server',
                'Optional' => 'fa-puzzle-piece'
            ];
            $group_icon = $group_icons[$group_name] ?? 'fa-cog';
            ?>
            <div class="check-group">
                <div class="group-header">
                    <i class="fas <?php echo $group_icon; ?> group-icon"></i>
                    <?php echo $group_name; ?>
                    <div class="group-status <?php echo $group_critical_failures ? 'fail' : 'pass'; ?>"></div>
                </div>
                <?php foreach ($group_checks as $check): ?>
                    <?php if (!$check['status'] || $group_name === 'PHP'): // Always show PHP, only show others if failed ?>
                        <div class="check-item <?php echo !$check['status'] ? 'failed' : ''; ?>">
                            <div class="check-status <?php 
                                if ($check['status']) echo 'pass';
                                elseif ($check['critical']) echo 'fail';
                                else echo 'warn';
                            ?>"></div>
                            <div class="check-name"><?php echo $check['name']; ?></div>
                            <div class="check-message"><?php echo $check['message']; ?></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        
        <!-- Actions -->
        <div class="actions">
            <?php if ($overall_status): ?>
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-rocket me-2"></i>Continue to Forma CMS
                </a>
            <?php else: ?>
                <a href="javascript:location.reload()" class="btn btn-primary">
                    <i class="fas fa-refresh me-2"></i>Re-check System
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <svg width="24" height="18" viewBox="0 0 400 280" class="footer-logo">
                    <g stroke="none" stroke-width="1" fill="currentColor" fill-rule="evenodd">
                        <path d="M0,0 L400,0 L320,80 L0,80 L0,0 Z"></path>
                        <path d="M0,100 L300,100 L220,180 L0,180 L0,100 Z"></path>
                        <path d="M0,200 L80,200 L0,280 L0,200 Z"></path>
                    </g>
                </svg>
                Forma CMS
            </div>
            <p class="footer-text">&copy; <?php echo date('Y'); ?> Forma CMS. Crafted by @onechrisjones.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 