<?php
session_start();
// Re-enable Twig requirement
require_once dirname(dirname(dirname(__FILE__))) . '/lib/Twig/init.php';

// Check if user is logged in
if (!isset($_SESSION['forma_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Get the snippets directory path
$snippetsDir = dirname(dirname(dirname(__FILE__))) . '/content/snippets';

// Debug output
error_log("=== Snippets API Debug ===");
error_log("Snippets directory: " . $snippetsDir);
error_log("Directory exists: " . (file_exists($snippetsDir) ? 'yes' : 'no'));
error_log("Directory readable: " . (is_readable($snippetsDir) ? 'yes' : 'no'));

// Create directory if it doesn't exist
if (!file_exists($snippetsDir)) {
    error_log("Creating snippets directory");
    mkdir($snippetsDir, 0755, true);
}

// Load shortcodes map
$shortcodesFile = $snippetsDir . '/.shortcodes.json';
if (file_exists($shortcodesFile)) {
    $shortcodes = json_decode(file_get_contents($shortcodesFile), true) ?? [];
    error_log("Loaded shortcodes: " . print_r($shortcodes, true));
} else {
    $shortcodes = [];
    error_log("No shortcodes file found");
}

/**
 * Process Twig template
 */
function processTwigTemplate($content) {
    global $twig;
    
    try {
        // Create a template from the content
        $template = $twig->createTemplate($content);
        
        // Render the template
        return $template->render([]);
    } catch (Exception $e) {
        error_log("Twig processing error: " . $e->getMessage());
        return $content; // Return original content if Twig processing fails
    }
}

/**
 * Save shortcodes map
 */
function saveShortcodes() {
    global $shortcodesFile, $shortcodes;
    error_log("=== Saving Shortcodes Map ===");
    error_log("Shortcodes file path: " . $shortcodesFile);
    error_log("Current shortcodes: " . print_r($shortcodes, true));
    
    // Ensure the directory exists
    $dir = dirname($shortcodesFile);
    if (!file_exists($dir)) {
        error_log("Creating directory: " . $dir);
        mkdir($dir, 0755, true);
    }
    
    // Write the shortcodes map
    $json = json_encode($shortcodes, JSON_PRETTY_PRINT);
    error_log("Writing JSON: " . $json);
    
    $result = file_put_contents($shortcodesFile, $json);
    if ($result === false) {
        error_log("Failed to save shortcodes map");
        error_log("Error: " . error_get_last()['message']);
    } else {
        error_log("Successfully saved shortcodes map");
        error_log("File size: " . $result . " bytes");
    }
}

/**
 * Get shortcode from filename
 */
function getShortcode($filename) {
    global $shortcodes;
    return array_search($filename, $shortcodes) ?: pathinfo($filename, PATHINFO_FILENAME);
}

/**
 * Process shortcodes in content
 */
function process_shortcodes($content, $shortcodes = null, $snippetsDir = null) {
    // Use passed shortcodes if available, otherwise load from file
    if ($shortcodes === null) {
        global $snippetsDir;
        $shortcodesFile = $snippetsDir . '/.shortcodes.json';
        $shortcodes = [];
        if (file_exists($shortcodesFile)) {
            $shortcodes = json_decode(file_get_contents($shortcodesFile), true) ?? [];
        }
    }
    
    // Use passed snippetsDir if available
    if ($snippetsDir === null) {
        global $snippetsDir;
    }
    
    // Process shortcodes
    $content = preg_replace_callback('/\[\[(.*?)\]\]/', function($matches) use ($shortcodes, $snippetsDir) {
        $shortcode = trim($matches[1]);
        if (isset($shortcodes[$shortcode])) {
            $snippetFile = $snippetsDir . '/' . $shortcodes[$shortcode];
            if (file_exists($snippetFile)) {
                $snippetContent = file_get_contents($snippetFile);
                
                // Check if content contains Twig syntax
                if (strpos($snippetContent, '{{') !== false || strpos($snippetContent, '{%') !== false) {
                    return processTwigTemplate($snippetContent);
                }
                
                return $snippetContent;
            }
        }
        return $matches[0]; // Return original shortcode if not found
    }, $content);
    
    return $content;
}

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['get_shortcodes'])) {
            // Return the shortcodes map
            if (file_exists($shortcodesFile)) {
                echo json_encode($shortcodes);
            } else {
                echo json_encode([]);
            }
            exit;
        } elseif (isset($_GET['file'])) {
            // Get specific file
            $filename = basename($_GET['file']);
            $filepath = $snippetsDir . '/' . $filename;
            
            error_log("Loading file: " . $filename);
            error_log("Full path: " . $filepath);
            
            if (file_exists($filepath)) {
                $content = file_get_contents($filepath);
                error_log("Content length: " . strlen($content));
                
                echo json_encode([
                    'filename' => pathinfo($filename, PATHINFO_FILENAME),
                    'shortcode' => getShortcode($filename),
                    'content' => $content
                ]);
            } else {
                error_log("File not found: " . $filepath);
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
            }
        } else {
            // List all files
            try {
                $allFiles = scandir($snippetsDir);
                error_log("All files in directory: " . print_r($allFiles, true));
                
                $files = array_diff($allFiles, ['.', '..', '.shortcodes.json', '.DS_Store']);
                error_log("Filtered files: " . print_r($files, true));
                
                $files = array_values($files);
                error_log("Final files array: " . print_r($files, true));
                
                echo json_encode($files);
            } catch (Exception $e) {
                error_log("Error listing files: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Error listing files: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST':
        // Save file
        $filename = $_POST['filename'];
        $shortcode = trim($_POST['shortcode']); // Trim whitespace
        $content = $_POST['content'];
        
        error_log("=== Saving New Snippet ===");
        error_log("Filename: " . $filename);
        error_log("Shortcode: " . $shortcode);
        error_log("Content length: " . strlen($content));
        
        // Validate shortcode
        if (empty($shortcode)) {
            error_log("Error: Empty shortcode provided");
            http_response_code(400);
            echo json_encode(['error' => 'Shortcode cannot be empty']);
            exit;
        }
        
        // Add .html extension if not present
        if (!preg_match('/\.(html|twig)$/', $filename)) {
            $filename .= '.html';
        }
        
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        $filepath = $snippetsDir . '/' . $filename;
        
        error_log("Final filename: " . $filename);
        error_log("File path: " . $filepath);
        
        // Update shortcodes map
        $shortcodes[$shortcode] = $filename;
        
        // Clean up any empty keys
        $shortcodes = array_filter($shortcodes, function($key) {
            return !empty($key);
        }, ARRAY_FILTER_USE_KEY);
        
        error_log("Updated shortcodes map: " . print_r($shortcodes, true));
        saveShortcodes();
        
        if (file_put_contents($filepath, $content) !== false) {
            error_log("Successfully saved snippet file");
            echo json_encode(['success' => true]);
        } else {
            error_log("Failed to save file: " . $filepath);
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save file']);
        }
        break;

    case 'DELETE':
        if (isset($_GET['file'])) {
            $filename = basename($_GET['file']);
            $filepath = $snippetsDir . '/' . $filename;
            
            if (file_exists($filepath)) {
                // Remove from shortcodes map
                $shortcode = getShortcode($filename);
                unset($shortcodes[$shortcode]);
                saveShortcodes();
                
                // Delete file
                if (unlink($filepath)) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to delete file']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'File parameter required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 