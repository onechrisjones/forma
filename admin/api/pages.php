<?php
// Set content type to JSON first
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['forma_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the pages directory path
$pagesDir = dirname(dirname(dirname(__FILE__))) . '/content/pages';

// Debug information
$debug = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'pagesDir' => $pagesDir,
    'exists' => file_exists($pagesDir),
    'readable' => is_readable($pagesDir),
    'session' => $_SESSION,
    'error' => error_get_last()
];

try {
    // Create directory if it doesn't exist
    if (!file_exists($pagesDir)) {
        if (!mkdir($pagesDir, 0755, true)) {
            throw new Exception("Failed to create pages directory");
        }
    }

    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['file'])) {
                // Get specific file
                $filename = basename($_GET['file']);
                $filepath = $pagesDir . '/' . $filename;
                
                if (file_exists($filepath)) {
                    $content = file_get_contents($filepath);
                    
                    // Check for META section at the beginning of the file
                    $meta = [];
                    if (preg_match('/^\s*<!--META\s*(.*?)\s*-->/s', $content, $matches)) {
                        // Parse the meta data
                        $metaContent = $matches[1];
                        $metaLines = explode("\n", $metaContent);
                        
                        foreach ($metaLines as $line) {
                            if (preg_match('/^\s*([^:]+):\s*(.*)$/', $line, $kv)) {
                                $key = trim($kv[1]);
                                $value = trim($kv[2]);
                                $meta[$key] = $value;
                            }
                        }
                        
                        // Remove META section from content if requested
                        if (isset($_GET['strip_meta']) && $_GET['strip_meta'] === 'true') {
                            $content = preg_replace('/^\s*<!--META\s*(.*?)\s*-->\s*/s', '', $content);
                        }
                    }
                    
                    // Don't process shortcodes in the admin panel
                    echo json_encode([
                        'filename' => pathinfo($filename, PATHINFO_FILENAME),
                        'content' => $content,
                        'meta' => $meta
                    ]);
                } else {
                    throw new Exception('File not found');
                }
            } else {
                // List all files
                if (!is_readable($pagesDir)) {
                    throw new Exception('Pages directory is not readable');
                }
                $files = array_diff(scandir($pagesDir), ['.', '..']);
                echo json_encode([
                    'files' => array_values($files),
                    'debug' => $debug
                ]);
            }
            break;

        case 'POST':
            // Save file
            if (!isset($_POST['filename']) || !isset($_POST['content'])) {
                throw new Exception('Missing filename or content');
            }
            
            $filename = $_POST['filename'];
            $content = $_POST['content'];
            
            // Add extension if not present
            if (!preg_match('/\.(html|md)$/', $filename)) {
                $filename .= '.html'; // Default to HTML
            }
            
            // Sanitize filename
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
            $filepath = $pagesDir . '/' . $filename;
            
            if (file_put_contents($filepath, $content) === false) {
                throw new Exception('Failed to save file');
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            // Delete file
            if (!isset($_GET['file'])) {
                throw new Exception('No file specified for deletion');
            }
            
            $filename = basename($_GET['file']);
            $filepath = $pagesDir . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception('File does not exist');
            }
            
            // Delete the file
            if (!unlink($filepath)) {
                throw new Exception('Failed to delete file');
            }
            
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => $debug
    ]);
} 