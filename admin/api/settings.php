<?php
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is a request for the about section
$isAboutRequest = isset($_GET['section']) && $_GET['section'] === 'about';

// Check if user is logged in (except for about section)
if (!$isAboutRequest && !isset($_SESSION['forma_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the config directory path
$configDir = dirname(dirname(dirname(__FILE__))) . '/config';

// Create directory if it doesn't exist
if (!file_exists($configDir)) {
    mkdir($configDir, 0755, true);
}

// Map old section names to new config structure
$sectionMapping = [
    'general' => 'site',
    'podcast' => 'podcast',
    // Add more mappings as needed
];

// Load settings
$configFile = $configDir . '/config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true) ?? [];
    // Log the loaded config
    error_log("Loaded config: " . json_encode($config));
} else {
    $config = [
        'site' => [
            'title' => 'My Website',
            'description' => '',
            'url' => '',
            'timezone' => 'UTC',
            'language' => 'en'
        ],
        'podcast' => [
            'title' => '',
            'description' => '',
            'author' => '',
            'email' => '',
            'category' => '',
            'subcategory' => '',
            'explicit' => 'no',
            'language' => 'en-us',
            'image' => ''
        ],
        'blog' => [
            'default_author' => '',
            'posts_per_page' => 10,
            'excerpt_length' => 250,
            'feed_posts' => 20
        ],
        'security' => [
            'session_lifetime' => 3600,
            'allowed_upload_types' => ["jpg", "jpeg", "png", "gif", "pdf", "mp3", "m4a", "mp4"],
            'max_upload_size' => 52428800
        ]
    ];
}

/**
 * Save settings
 */
function saveSettings() {
    global $configFile, $config;
    $result = file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    error_log("Saved config: " . json_encode($config) . " Result: " . ($result !== false ? "SUCCESS" : "FAILURE"));
    return $result !== false;
}

// Map old field names to new config structure
function mapFields($section, $data) {
    if ($section == 'general') {
        // Map general settings fields to site section
        return [
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'language' => $data['language'] ?? 'en'
        ];
    } elseif ($section == 'podcast') {
        // Keep podcast section as is
        return $data;
    }
    
    return $data;
}

// Handle different sections
$section = $_GET['section'] ?? 'general';

switch ($section) {
    case 'general':
        // Handle general settings
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Get settings
            $settings = $config['site'] ?? [];
            sendJsonResponse($settings);
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update settings
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(['success' => false, 'error' => 'Invalid JSON input'], 400);
            }
            
            // Update the config
            $config['site'] = array_merge($config['site'] ?? [], $input);
            
            // Save the config
            if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) === false) {
                sendJsonResponse(['success' => false, 'error' => 'Failed to save settings'], 500);
            }
            
            sendJsonResponse(['success' => true]);
        }
        break;
        
    case 'blog':
        // Handle blog settings
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Get settings
            $settings = $config['blog'] ?? [];
            sendJsonResponse($settings);
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update settings
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(['success' => false, 'error' => 'Invalid JSON input'], 400);
            }
            
            // Update the config
            $config['blog'] = array_merge($config['blog'] ?? [], $input);
            
            // Save the config
            if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) === false) {
                sendJsonResponse(['success' => false, 'error' => 'Failed to save settings'], 500);
            }
            
            sendJsonResponse(['success' => true]);
        }
        break;
        
    case 'podcast':
        // Handle podcast settings
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Get settings
            $settings = $config['podcast'] ?? [];
            sendJsonResponse($settings);
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update settings
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(['success' => false, 'error' => 'Invalid JSON input'], 400);
            }
            
            // Update the config
            $config['podcast'] = array_merge($config['podcast'] ?? [], $input);
            
            // Save the config
            if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) === false) {
                sendJsonResponse(['success' => false, 'error' => 'Failed to save settings'], 500);
            }
            
            sendJsonResponse(['success' => true]);
        }
        break;
        
    case 'cache':
        // Handle cache settings
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Get settings
            $settings = $config['cache'] ?? [
                'enabled' => false,
                'ttl' => 3600,
                'excluded_paths' => ['/admin']
            ];
            
            // Add cache status
            $settings['status'] = getCacheStatus();
            
            sendJsonResponse($settings);
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update settings
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(['success' => false, 'error' => 'Invalid JSON input'], 400);
            }
            
            // Update the config
            $config['cache'] = array_merge($config['cache'] ?? [], $input);
            
            // Ensure exclusions is always an array
            if (isset($config['cache']['excluded_paths']) && !is_array($config['cache']['excluded_paths'])) {
                if (empty($config['cache']['excluded_paths'])) {
                    $config['cache']['excluded_paths'] = [];
                } else {
                    $config['cache']['excluded_paths'] = [$config['cache']['excluded_paths']];
                }
            }
            
            // Always exclude admin
            if (!in_array('/admin', $config['cache']['excluded_paths'])) {
                $config['cache']['excluded_paths'][] = '/admin';
            }
            
            // Save the config
            if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) === false) {
                sendJsonResponse(['success' => false, 'error' => 'Failed to save settings'], 500);
            }
            
            sendJsonResponse(['success' => true]);
        }
        break;
        
    case 'about':
        // Handle about section
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Get system information
            $systemInfo = [
                'cms_version' => '1.0.0',
                'version_date' => '2023-05-20',
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'database_type' => 'Flat File',
                'dev_mode' => true
            ];
            
            // Try to get README content
            $readmeFile = dirname(dirname(dirname(__FILE__))) . '/README.md';
            $readmeContent = '';
            
            if (file_exists($readmeFile)) {
                $readmeContent = file_get_contents($readmeFile);
                
                // Convert markdown to HTML if Parsedown is available
                if (class_exists('Parsedown')) {
                    require_once dirname(dirname(dirname(__FILE__))) . '/lib/Parsedown.php';
                    $parsedown = new Parsedown();
                    $readmeContent = $parsedown->text($readmeContent);
                }
            }
            
            sendJsonResponse([
                'system' => $systemInfo,
                'content' => $readmeContent
            ]);
        }
        break;
        
    default:
        sendJsonResponse(['error' => 'Unknown section: ' . $section], 400);
}

/**
 * Get cache status
 */
function getCacheStatus() {
    $cacheDir = dirname(dirname(dirname(__FILE__))) . '/cache';
    
    if (!file_exists($cacheDir)) {
        return [
            'size' => '0 bytes',
            'count' => 0,
            'last_rebuild' => 'Never',
            'files' => [],
            'server_info' => getServerInfo($cacheDir)
        ];
    }
    
    $totalSize = 0;
    $fileCount = 0;
    $lastRebuild = 'Never';
    $lastRebuildFile = $cacheDir . '/.last_rebuild';
    $cacheFiles = [];
    
    if (file_exists($lastRebuildFile)) {
        $timestamp = file_get_contents($lastRebuildFile);
        if ($timestamp) {
            $lastRebuild = date('Y-m-d H:i:s', (int)$timestamp);
        }
    }
    
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($it as $file) {
        if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'html') {
            $totalSize += $file->getSize();
            $fileCount++;
            
            // Add to files list
            $relativePath = str_replace($cacheDir, '', $file->getPathname());
            $cacheFiles[] = [
                'path' => $relativePath,
                'size' => formatFileSize($file->getSize()),
                'modified' => date('Y-m-d H:i:s', filemtime($file->getPathname()))
            ];
        }
    }
    
    // Sort files by modification time (newest first)
    usort($cacheFiles, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
    
    // Format size for display
    $formattedSize = formatFileSize($totalSize);
    
    return [
        'size' => $formattedSize,
        'count' => $fileCount,
        'last_rebuild' => $lastRebuild,
        'files' => $cacheFiles,
        'server_info' => getServerInfo($cacheDir)
    ];
}

/**
 * Format file size for display
 */
function formatFileSize($bytes) {
    if ($bytes < 1024) {
        return $bytes . ' bytes';
    } elseif ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    }
}

/**
 * Get server information
 */
function getServerInfo($cacheDir) {
    return [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'cache_directory' => $cacheDir,
        'directory_writable' => is_writable($cacheDir) ? 'Yes' : 'No'
    ];
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
} 