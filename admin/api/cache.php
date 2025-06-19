<?php
// Buffer all output to prevent accidental output before headers
ob_start();

// Set content type to JSON
header('Content-Type: application/json');

// Function to send a clean JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    
    // Clear any buffered output
    ob_clean();
    
    // Output JSON and exit
    echo json_encode($data);
    exit;
}

// Set up error handlers
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    sendJsonResponse(['success' => false, 'error' => "PHP Error: $errstr"], 500);
    return true;
}
set_error_handler('jsonErrorHandler');

function jsonExceptionHandler($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage());
    sendJsonResponse(['success' => false, 'error' => "Exception: " . $exception->getMessage()], 500);
}
set_exception_handler('jsonExceptionHandler');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['forma_user'])) {
    sendJsonResponse(['error' => 'Unauthorized'], 401);
}

// Set path constants
define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
define('CACHE_DIR', ROOT_DIR . '/cache');
define('CONFIG_DIR', ROOT_DIR . '/config');

// Create cache directory if it doesn't exist
if (!file_exists(CACHE_DIR)) {
    if (!mkdir(CACHE_DIR, 0755, true)) {
        sendJsonResponse(['success' => false, 'error' => 'Failed to create cache directory'], 500);
    }
}

/**
 * Get cache status (size, count, etc.)
 */
function getCacheStatus() {
    if (!file_exists(CACHE_DIR)) {
        return [
            'size' => '0 bytes',
            'count' => 0,
            'last_rebuild' => 'Never',
            'files' => [],
            'server_info' => getServerInfo()
        ];
    }
    
    $totalSize = 0;
    $fileCount = 0;
    $lastRebuild = 'Never';
    $lastRebuildFile = CACHE_DIR . '/.last_rebuild';
    $cacheFiles = [];
    
    if (file_exists($lastRebuildFile)) {
        $timestamp = file_get_contents($lastRebuildFile);
        if ($timestamp) {
            $lastRebuild = date('Y-m-d H:i:s', (int)$timestamp);
        }
    }
    
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(CACHE_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($it as $file) {
        if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'html') {
            $totalSize += $file->getSize();
            $fileCount++;
            
            // Add to files list
            $relativePath = str_replace(CACHE_DIR, '', $file->getPathname());
            $cacheFiles[] = [
                'path' => $relativePath,
                'size' => formatSize($file->getSize()),
                'modified' => date('Y-m-d H:i:s', filemtime($file->getPathname()))
            ];
        }
    }
    
    // Sort files by modification time (newest first)
    usort($cacheFiles, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
    
    // Format size for display
    $formattedSize = formatSize($totalSize);
    
    return [
        'size' => $formattedSize,
        'count' => $fileCount,
        'last_rebuild' => $lastRebuild,
        'files' => $cacheFiles,
        'server_info' => getServerInfo()
    ];
}

/**
 * Format file size
 */
function formatSize($bytes) {
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
function getServerInfo() {
    return [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'cache_directory' => CACHE_DIR,
        'directory_writable' => is_writable(CACHE_DIR) ? 'Yes' : 'No'
    ];
}

/**
 * Clear all cached files
 */
function clearCache() {
    if (!file_exists(CACHE_DIR)) {
        return true;
    }
    
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(CACHE_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($it as $file) {
        if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'html') {
            if (!unlink($file->getRealPath())) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Rebuild the cache by warming up commonly accessed pages
 */
function rebuildCache() {
    // Clear the cache first
    if (!clearCache()) {
        return false;
    }
    
    // Create or update the last rebuild timestamp
    $lastRebuildFile = CACHE_DIR . '/.last_rebuild';
    file_put_contents($lastRebuildFile, time());
    
    // Load the site settings to get the URL
    $configFile = CONFIG_DIR . '/config.json';
    if (!file_exists($configFile)) {
        return false;
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    
    $siteUrl = $config['site']['url'] ?? 'http://localhost';
    $siteUrl = rtrim($siteUrl, '/');
    
    // Get list of pages to warm up (start with the home page)
    $pagesToWarm = ['/'];
    
    // Add blog posts 
    $blogDir = ROOT_DIR . '/content/blog';
    if (is_dir($blogDir)) {
        foreach (scandir($blogDir) as $file) {
            if ($file === '.' || $file === '..' || $file === '.DS_Store') continue;
            if (!preg_match('/\.md$/i', $file)) continue;
            
            // Get the slug from the front matter if available
            $content = file_get_contents($blogDir . '/' . $file);
            $slug = pathinfo($file, PATHINFO_FILENAME);
            
            if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
                $frontMatter = $matches[1];
                if (preg_match('/^\s*slug:\s*(.*)$/im', $frontMatter, $slugMatch)) {
                    $slug = trim($slugMatch[1]);
                }
            }
            
            $pagesToWarm[] = '/blog/' . $slug;
        }
    }
    
    // Add regular pages
    $pagesDir = ROOT_DIR . '/content/pages';
    if (is_dir($pagesDir)) {
        foreach (scandir($pagesDir) as $file) {
            if ($file === '.' || $file === '..' || $file === '.DS_Store') continue;
            if (!preg_match('/\.(html|md)$/i', $file)) continue;
            
            // Skip special templates like blog-archive.html
            if (in_array($file, ['blog-archive.html', 'blog-single.html', 'podcast-archive.html', 'podcast-single.html'])) {
                continue;
            }
            
            $slug = '/' . pathinfo($file, PATHINFO_FILENAME);
            $pagesToWarm[] = $slug;
        }
    }
    
    // TODO: Use a non-blocking approach for large sites
    // For now, we'll warm up the first 10 pages synchronously
    $warmedPages = 0;
    $pagesToWarm = array_slice($pagesToWarm, 0, 10);
    
    foreach ($pagesToWarm as $page) {
        // Make a request to the page to cache it
        $url = $siteUrl . $page;
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'X-Cache-Warm: 1',
                    'User-Agent: Forma Cache Warmer'
                ]
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result !== false) {
            $warmedPages++;
        }
    }
    
    return $warmedPages > 0;
}

// Handle the request
try {
    $action = $_GET['action'] ?? 'status';
    
    switch ($action) {
        case 'clear':
            $success = clearCache();
            sendJsonResponse([
                'success' => $success,
                'status' => getCacheStatus()
            ]);
            break;
            
        case 'rebuild':
            $success = rebuildCache();
            sendJsonResponse([
                'success' => $success,
                'status' => getCacheStatus()
            ]);
            break;
            
        case 'status':
        default:
            sendJsonResponse([
                'success' => true,
                'status' => getCacheStatus()
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("Exception in cache API: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
} 