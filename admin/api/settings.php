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
            'title' => $data['site_title'] ?? null,
            'description' => $data['site_description'] ?? null,
            'url' => $data['site_url'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'language' => $data['language'] ?? 'en'
        ];
    } elseif ($section == 'podcast') {
        // Keep podcast section as is
        return $data;
    }
    
    return $data;
}

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['section'])) {
            // Get specific section
            $requestedSection = $_GET['section'];
            
            // Special case for about section - load content from README.md
            if ($requestedSection === 'about') {
                // Path to README.md file
                $readmePath = dirname(dirname(dirname(__FILE__))) . '/README.md';
                
                // Get CMS version information
                $version = 'Unknown';
                $versionFile = dirname(dirname(dirname(__FILE__))) . '/version.php';
                if (file_exists($versionFile)) {
                    include_once $versionFile;
                    $version = defined('FORMA_VERSION') ? FORMA_VERSION : 'Unknown';
                }
                
                // System information
                $systemInfo = [
                    'php_version' => PHP_VERSION,
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'database_type' => 'Flat File',
                    'cms_version' => $version,
                    'version_date' => defined('FORMA_VERSION_DATE') ? FORMA_VERSION_DATE : null,
                    'dev_mode' => defined('FORMA_DEV_MODE') ? FORMA_DEV_MODE : false
                ];
                
                if (file_exists($readmePath)) {
                    // Load README content
                    $readmeContent = file_get_contents($readmePath);
                    
                    // Parse markdown to HTML using Parsedown if available
                    if (class_exists('Parsedown') || file_exists(dirname(dirname(dirname(__FILE__))) . '/lib/Parsedown.php')) {
                        if (!class_exists('Parsedown')) {
                            require_once dirname(dirname(dirname(__FILE__))) . '/lib/Parsedown.php';
                        }
                        $parsedown = new Parsedown();
                        $htmlContent = $parsedown->text($readmeContent);
                    } else {
                        // Fallback to raw markdown if Parsedown is not available
                        $htmlContent = nl2br(htmlspecialchars($readmeContent));
                    }
                    
                    echo json_encode([
                        'content' => $htmlContent,
                        'rawContent' => $readmeContent,
                        'system' => $systemInfo
                    ]);
                    exit;
                } else {
                    // Even if README is missing, return system info
                    echo json_encode([
                        'content' => '<p>README.md file not found</p>',
                        'rawContent' => 'README.md file not found',
                        'system' => $systemInfo
                    ]);
                    exit;
                }
            }
            
            $section = $sectionMapping[$requestedSection] ?? $requestedSection;
            
            error_log("Requested section: $requestedSection, Mapped to: $section");
            
            if (isset($config[$section])) {
                // If requesting an old section name, map to the new structure
                if ($requestedSection == 'general' && $section == 'site') {
                    echo json_encode([
                        'site_title' => $config[$section]['title'] ?? '',
                        'site_description' => $config[$section]['description'] ?? '',
                        'site_url' => $config[$section]['url'] ?? '',
                        'timezone' => $config[$section]['timezone'] ?? 'UTC',
                        'language' => $config[$section]['language'] ?? 'en'
                    ]);
                } else {
                    echo json_encode($config[$section]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Section not found', 'requested' => $requestedSection, 'mapped' => $section]);
            }
        } else {
            // Get all settings
            echo json_encode($config);
        }
        break;

    case 'POST':
        if (isset($_GET['section'])) {
            // Update section
            $requestedSection = $_GET['section'];
            $section = $sectionMapping[$requestedSection] ?? $requestedSection;
            
            error_log("Updating section: $requestedSection, Mapped to: $section");
            
            $data = json_decode(file_get_contents('php://input'), true);
            error_log("Received data: " . json_encode($data));
            
            if ($data) {
                // Map fields if necessary
                if ($requestedSection !== $section) {
                    $data = mapFields($requestedSection, $data);
                }
                
                // Update config
                $config[$section] = array_merge($config[$section] ?? [], $data);
                
                // Save changes
                if (saveSettings()) {
                    // Note: RSS feed regeneration will be handled by the blog section when needed
                    // Regenerating here was causing conflicts with blog API validation
                    
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to save settings']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Section parameter required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 