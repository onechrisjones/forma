<?php
// Buffer all output to prevent accidental output before headers
ob_start();

// Set content type to JSON first
header('Content-Type: application/json');

// Function to send a clean JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    global $debug;
    http_response_code($statusCode);
    
    // Add debug info if there was an error
    if (isset($data['error']) && $statusCode >= 400) {
        $data['debug'] = $debug;
    }
    
    // Clear any buffered output
    ob_clean();
    
    // Output JSON and exit
    echo json_encode($data);
    exit;
}

// All errors should return JSON too
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    sendJsonResponse(['success' => false, 'error' => "PHP Error: $errstr"], 500);
    return true;
}
set_error_handler('jsonErrorHandler');

// Catch uncaught exceptions
function jsonExceptionHandler($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage());
    sendJsonResponse(['success' => false, 'error' => "Exception: " . $exception->getMessage()], 500);
}
set_exception_handler('jsonExceptionHandler');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['forma_user'])) {
    sendJsonResponse(['error' => 'Unauthorized'], 401);
}

// Get the blog directory path
$blogDir = dirname(dirname(dirname(__FILE__))) . '/content/blog';
$feedsDir = dirname(dirname(dirname(__FILE__))) . '/feeds';

// Debug information
$debug = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'blogDir' => $blogDir,
    'exists' => file_exists($blogDir),
    'readable' => is_readable($blogDir),
    'writable' => is_writable($blogDir)
];

// Function to generate a slug from a title
function generateSlugFromTitle($title) {
    if (empty($title)) {
        return '';
    }
    
    // Convert to lowercase, remove accents, replace spaces with hyphens
    $slug = strtolower($title);
    
    // Remove special characters
    $slug = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $slug);
    
    // Replace spaces with hyphens
    $slug = preg_replace('/\s+/', '-', $slug);
    
    // Remove consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Trim hyphens from beginning and end
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Parse YAML front matter from markdown content
 */
function parseMarkdownWithFrontMatter($content) {
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)/s', $content, $matches)) {
        // Try to parse YAML if extension is available
        if (function_exists('yaml_parse')) {
            $yaml = @yaml_parse($matches[1]);
            if ($yaml !== false) {
                return [
                    'frontMatter' => $yaml,
                    'content' => $matches[2]
                ];
            }
        }
        
        // Fallback: Parse YAML-like format manually
        $lines = explode("\n", $matches[1]);
        $frontMatter = [];
        $currentArray = null;
        $currentKey = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) continue;
            
            // Check for array item (starts with - )
            if (preg_match('/^\s*-\s+(.*)$/', $line, $item)) {
                // If we're already processing an array, add to it
                if ($currentArray !== null && isset($frontMatter[$currentArray])) {
                    if (!is_array($frontMatter[$currentArray])) {
                        // Convert to array if it wasn't already
                        $frontMatter[$currentArray] = [$frontMatter[$currentArray]];
                    }
                    $frontMatter[$currentArray][] = trim($item[1]);
                }
                continue;
            }
            
            // Check for key-value pair
            if (preg_match('/^([^:]+):\s*(.*)$/', $line, $kv)) {
                $key = trim($kv[1]);
                $value = trim($kv[2]);
                
                // If this might be an array declaration (value is empty)
                if (empty($value)) {
                    $currentArray = $key;
                    $frontMatter[$key] = [];
                    continue;
                }
                
                // Handle arrays (comma-separated values)
                if (strpos($value, ',') !== false) {
                    $value = array_map('trim', explode(',', $value));
                }
                
                $frontMatter[$key] = $value;
                $currentKey = $key;
            }
        }
        
        return [
            'frontMatter' => $frontMatter,
            'content' => $matches[2]
        ];
    }
    return [
        'frontMatter' => [],
        'content' => $content
    ];
}

/**
 * Generate RSS feed from blog posts
 */
function generateRSSFeed() {
    global $blogDir, $feedsDir;
    
    try {
        // Get site settings
        $configFile = dirname(dirname(dirname(__FILE__))) . '/config/config.json';
        if (!file_exists($configFile)) {
            return false;
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Get site and blog settings
        $siteSettings = $config['site'] ?? [];
        $blogSettings = $config['blog'] ?? [];
        
        $siteUrl = $siteSettings['url'] ?? '';
        $siteTitle = $siteSettings['title'] ?? 'Blog';
        $siteDescription = $siteSettings['description'] ?? '';
        $feedPosts = $blogSettings['feed_posts'] ?? 20;
        $excerptLength = $blogSettings['excerpt_length'] ?? 250;
        
        // Start RSS feed
        $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $rss .= '<channel>' . "\n";
        
        // Ensure all values are strings
        if (is_array($siteTitle)) $siteTitle = implode(' ', $siteTitle);
        if (is_array($siteDescription)) $siteDescription = implode(' ', $siteDescription);
        
        $rss .= '<title>' . htmlspecialchars($siteTitle) . '</title>' . "\n";
        $rss .= '<link>' . htmlspecialchars($siteUrl) . '</link>' . "\n";
        $rss .= '<description>' . htmlspecialchars($siteDescription) . '</description>' . "\n";
        $rss .= '<language>' . htmlspecialchars($siteSettings['language'] ?? 'en') . '</language>' . "\n";
        $rss .= '<lastBuildDate>' . date('r') . '</lastBuildDate>' . "\n";
        
        // Add atom:link for RSS autodiscovery
        if (!empty($siteUrl)) {
            $feedUrl = rtrim($siteUrl, '/') . '/feeds/blog.xml';
            $rss .= '<atom:link href="' . htmlspecialchars($feedUrl) . '" rel="self" type="application/rss+xml" />' . "\n";
        }
        
        // Get all blog posts
        $posts = [];
        foreach (scandir($blogDir) as $file) {
            if ($file === '.' || $file === '..' || $file === '.DS_Store') continue;
            if (!preg_match('/\.md$/i', $file)) continue;
            
            $content = file_get_contents($blogDir . '/' . $file);
            if ($content === false) continue;
            
            $parsed = parseMarkdownWithFrontMatter($content);
            
            if (isset($parsed['frontMatter']['date'])) {
                // Validate and correct front matter fields
                $frontMatter = $parsed['frontMatter'];
                
                // Ensure date is a string
                if (isset($frontMatter['date']) && is_array($frontMatter['date'])) {
                    $frontMatter['date'] = implode(' ', $frontMatter['date']);
                }
                
                // Ensure categories is an array
                if (isset($frontMatter['categories']) && !is_array($frontMatter['categories'])) {
                    $frontMatter['categories'] = [$frontMatter['categories']];
                }
                
                // Ensure tags is an array
                if (isset($frontMatter['tags']) && !is_array($frontMatter['tags'])) {
                    $frontMatter['tags'] = [$frontMatter['tags']];
                }
                
                $posts[] = [
                    'file' => $file,
                    'frontMatter' => $frontMatter,
                    'content' => $parsed['content']
                ];
            }
        }
        
        // Sort posts by date (newest first)
        usort($posts, function($a, $b) {
            // Get the date values, ensuring they're strings
            $dateA = $a['frontMatter']['date'] ?? '';
            $dateB = $b['frontMatter']['date'] ?? '';
            
            // Convert to string if they're arrays
            if (is_array($dateA)) {
                $dateA = implode(' ', $dateA);
            }
            if (is_array($dateB)) {
                $dateB = implode(' ', $dateB);
            }
            
            // Convert to timestamps
            $timeA = strtotime($dateA) ?: 0;
            $timeB = strtotime($dateB) ?: 0;
            
            return $timeB - $timeA;
        });
        
        // Limit the number of posts in the feed
        $posts = array_slice($posts, 0, $feedPosts);
        
        // Add items to RSS feed
        foreach ($posts as $post) {
            $rss .= '<item>' . "\n";
            
            // Ensure title is a string
            $title = $post['frontMatter']['title'] ?? pathinfo($post['file'], PATHINFO_FILENAME);
            if (is_array($title)) $title = implode(' ', $title);
            $rss .= '<title>' . htmlspecialchars($title) . '</title>' . "\n";
            
            // Use slug if available, otherwise use the filename
            $slug = isset($post['frontMatter']['slug']) && !empty($post['frontMatter']['slug']) ? 
                $post['frontMatter']['slug'] : 
                pathinfo($post['file'], PATHINFO_FILENAME);
            
            $postUrl = $siteUrl . '/blog/' . $slug;
            $rss .= '<link>' . htmlspecialchars($postUrl) . '</link>' . "\n";
            $rss .= '<guid isPermaLink="true">' . htmlspecialchars($postUrl) . '</guid>' . "\n";
            
            // Ensure date is a string before passing to strtotime
            $pubDate = $post['frontMatter']['date'] ?? '';
            if (is_array($pubDate)) {
                $pubDate = implode(' ', $pubDate);
            }
            
            $rss .= '<pubDate>' . date('r', strtotime($pubDate) ?: time()) . '</pubDate>' . "\n";
            
            if (isset($post['frontMatter']['description'])) {
                $description = $post['frontMatter']['description'];
                if (is_array($description)) $description = implode(' ', $description);
                $rss .= '<description>' . htmlspecialchars($description) . '</description>' . "\n";
            } else {
                // Create a description from the content if none exists
                $content = $post['content'];
                $excerpt = substr(strip_tags($content), 0, $excerptLength);
                if (strlen($content) > $excerptLength) $excerpt .= '...';
                $rss .= '<description>' . htmlspecialchars($excerpt) . '</description>' . "\n";
            }
            
            if (isset($post['frontMatter']['author'])) {
                $author = $post['frontMatter']['author'];
                if (is_array($author)) $author = implode(' ', $author);
                $rss .= '<author>' . htmlspecialchars($author) . '</author>' . "\n";
            } elseif (isset($siteSettings['default_author'])) {
                $rss .= '<author>' . htmlspecialchars($siteSettings['default_author']) . '</author>' . "\n";
            }
            
            if (isset($post['frontMatter']['categories'])) {
                // Make sure categories is treated as an array
                $categories = $post['frontMatter']['categories'];
                if (!is_array($categories)) {
                    // If it's a string, make it a single-item array
                    $categories = [$categories];
                }
                foreach ($categories as $category) {
                    if (is_array($category)) $category = implode(' ', $category);
                    $rss .= '<category>' . htmlspecialchars($category) . '</category>' . "\n";
                }
            }
            $rss .= '</item>' . "\n";
        }
        
        $rss .= '</channel>' . "\n";
        $rss .= '</rss>';
        
        // Save RSS feed
        if (file_put_contents($feedsDir . '/blog.xml', $rss) === false) {
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        // Don't throw the error as RSS generation is not critical
        return false;
    }
}

try {
    // Create directories if they don't exist
    if (!file_exists($blogDir)) {
        if (!mkdir($blogDir, 0755, true)) {
            throw new Exception("Failed to create blog directory");
        }
    }
    if (!file_exists($feedsDir)) {
        if (!mkdir($feedsDir, 0755, true)) {
            throw new Exception("Failed to create feeds directory");
        }
    }
    
    // Check if directories are writable
    if (!is_writable($blogDir)) {
        throw new Exception("Blog directory is not writable");
    }

    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['file'])) {
                // Get specific file
                $filename = basename($_GET['file']);
                $filepath = $blogDir . '/' . $filename;
                
                if (!file_exists($filepath)) {
                    sendJsonResponse(['error' => 'File not found'], 404);
                }
                
                $content = file_get_contents($filepath);
                if ($content === false) {
                    sendJsonResponse(['error' => 'Failed to read file'], 500);
                }
                
                $parsed = parseMarkdownWithFrontMatter($content);
                
                // Validate and correct front matter fields
                $frontMatter = $parsed['frontMatter'];
                
                // Ensure date is a string
                if (isset($frontMatter['date']) && is_array($frontMatter['date'])) {
                    $frontMatter['date'] = implode(' ', $frontMatter['date']);
                }
                
                // Ensure categories is an array
                if (isset($frontMatter['categories']) && !is_array($frontMatter['categories'])) {
                    $frontMatter['categories'] = [$frontMatter['categories']];
                }
                
                // Ensure tags is an array
                if (isset($frontMatter['tags']) && !is_array($frontMatter['tags'])) {
                    $frontMatter['tags'] = [$frontMatter['tags']];
                }
                
                sendJsonResponse([
                    'filename' => pathinfo($filename, PATHINFO_FILENAME),
                    'frontMatter' => $frontMatter,
                    'content' => $parsed['content']
                ]);
            } else {
                // List all markdown files
                if (!is_readable($blogDir)) {
                    sendJsonResponse(['error' => 'Blog directory is not readable'], 500);
                }
                
                // Clear file system caches to ensure fresh directory listing
                clearstatcache(true, $blogDir);
                
                $allFiles = scandir($blogDir);
                $files = [];
                foreach ($allFiles as $file) {
                    if ($file === '.' || $file === '..' || $file === '.DS_Store') continue;
                    if (preg_match('/\.md$/i', $file)) {
                        $files[] = $file;
                    }
                }
                
                // Sort files by modification time (newest first)
                if (!empty($files)) {
                    // Clear cache once for the directory before sorting
                    foreach ($files as $file) {
                        clearstatcache(true, $blogDir . '/' . $file);
                    }
                    
                    usort($files, function($a, $b) use ($blogDir) {
                        // No need for clearstatcache here since we already cleared all files
                        return filemtime($blogDir . '/' . $b) - filemtime($blogDir . '/' . $a);
                    });
                }
                
                sendJsonResponse(['files' => $files]);
            }
            break;

        case 'POST':
            $filename = $_POST['filename'] ?? '';
            $content = $_POST['content'] ?? '';
            $use_separate_fields = isset($_POST['use_separate_fields']) && $_POST['use_separate_fields'] === 'true';
            
            if (empty($filename)) {
                sendJsonResponse(['success' => false, 'error' => 'Filename is required'], 400);
            }
            
            // Ensure filename has .md extension
            if (!str_ends_with($filename, '.md')) {
                $filename .= '.md';
            }
            
            // Sanitize filename
            $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);
            
            // NEW APPROACH: If separate fields were sent, construct the front matter on the server
            if ($use_separate_fields) {
                // Get all front matter fields
                $title = $_POST['title'] ?? $filename;
                $slug = $_POST['slug'] ?? generateSlugFromTitle($title);
                $date = $_POST['date'] ?? date('Y-m-d');
                $author = $_POST['author'] ?? '';
                $description = $_POST['description'] ?? '';
                $categories = $_POST['categories'] ?? '';
                $tags = $_POST['tags'] ?? '';
                
                // Build YAML front matter manually
                $yamlFrontMatter = "---\n";
                $yamlFrontMatter .= "title: " . $title . "\n";
                
                if (!empty($slug)) {
                    $yamlFrontMatter .= "slug: " . $slug . "\n";
                }
                
                $yamlFrontMatter .= "date: " . $date . "\n";
                
                if (!empty($author)) {
                    $yamlFrontMatter .= "author: " . $author . "\n";
                }
                
                if (!empty($description)) {
                    // Handle multi-line descriptions with quotes
                    if (strpos($description, "\n") !== false || preg_match('/[:{}[\],&*#?|<>=!%@`]/', $description)) {
                        $description = str_replace('"', '\\"', $description);
                        $yamlFrontMatter .= "description: \"" . $description . "\"\n";
                    } else {
                        $yamlFrontMatter .= "description: " . $description . "\n";
                    }
                }
                
                if (!empty($categories)) {
                    $categoryItems = array_map('trim', explode(',', $categories));
                    if (count($categoryItems) === 1) {
                        $yamlFrontMatter .= "categories: " . $categoryItems[0] . "\n";
                    } else if (count($categoryItems) > 1) {
                        $yamlFrontMatter .= "categories:\n";
                        foreach ($categoryItems as $category) {
                            if (!empty($category)) {
                                $yamlFrontMatter .= "  - " . $category . "\n";
                            }
                        }
                    }
                }
                
                if (!empty($tags)) {
                    $tagItems = array_map('trim', explode(',', $tags));
                    if (count($tagItems) === 1) {
                        $yamlFrontMatter .= "tags: " . $tagItems[0] . "\n";
                    } else if (count($tagItems) > 1) {
                        $yamlFrontMatter .= "tags:\n";
                        foreach ($tagItems as $tag) {
                            if (!empty($tag)) {
                                $yamlFrontMatter .= "  - " . $tag . "\n";
                            }
                        }
                    }
                }
                
                $yamlFrontMatter .= "---\n\n";
                
                // Append content to YAML front matter
                $fullContent = $yamlFrontMatter . $content;
                
                // Save the file with the constructed content
                $filepath = $blogDir . '/' . $filename;
                $result = file_put_contents($filepath, $fullContent);
                
                if ($result === false) {
                    $error = error_get_last();
                    sendJsonResponse([
                        'success' => false, 
                        'error' => 'Failed to save file: ' . ($error ? $error['message'] : 'Unknown error')
                    ], 500);
                } else {
                    // Force file system synchronization and clear caches
                    clearstatcache(true, $filepath);
                    clearstatcache(true, $blogDir);
                    
                    // Regenerate RSS feed silently
                    generateRSSFeed();
                    
                    // Successfully saved, return success JSON
                    sendJsonResponse(['success' => true]);
                }
            } else {
                // OLD APPROACH: Front matter was generated on the client
                // Detect any BOM or invalid characters at the start of the content
                $firstFewBytes = substr($content, 0, 10);
                $hexBytes = bin2hex($firstFewBytes);
                
                // Remove UTF-8 BOM if present
                if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                    $content = substr($content, 3);
                }
                
                // Trim any invisible chars at the start
                $content = ltrim($content);
                
                // Try to parse and re-serialize the content to ensure valid YAML
                try {
                    // Check if the content has YAML front matter
                    $hasYamlPattern = '/^---\s*\n[\s\S]*?\n---\s*\n/';
                    $hasYaml = preg_match($hasYamlPattern, $content, $matches);
                    
                    if (!$hasYaml) {
                        // Try to manually detect if this looks like front matter with a more lenient check
                        $alternatePattern = '/^---[\s\S]*?---\s/';
                        $hasAlternateFormat = preg_match($alternatePattern, $content, $altMatches);
                        
                        if ($hasAlternateFormat) {
                            // Allow it to continue since we found something that looks like front matter
                            $hasYaml = true;
                            $matches = $altMatches;
                        } else {
                            // One more check - does it at least start with --- ?
                            if (substr(trim($content), 0, 3) === '---') {
                                // Let's try to analyze what's wrong with the format
                                $lines = explode("\n", substr($content, 0, 200));
                            }
                        }
                    }
                    
                    // Always force a valid front matter structure if we're not being strict
                    if (!$hasYaml) {
                        $yamlHeader = "---\ntitle: " . $filename . "\ndate: " . date('Y-m-d') . "\n---\n\n";
                        $content = $yamlHeader . $content;
                    }
                    
                    // Save the file directly with the provided content
                    $filepath = $blogDir . '/' . $filename;
                    $result = file_put_contents($filepath, $content);
                    
                    if ($result === false) {
                        $error = error_get_last();
                        sendJsonResponse([
                            'success' => false, 
                            'error' => 'Failed to save file: ' . ($error ? $error['message'] : 'Unknown error')
                        ], 500);
                    } else {
                        // Force file system synchronization and clear caches
                        clearstatcache(true, $filepath);
                        clearstatcache(true, $blogDir);
                        
                        // Regenerate RSS feed silently
                        generateRSSFeed();
                        
                        // Successfully saved, return success JSON
                        sendJsonResponse(['success' => true]);
                    }
                } catch (Exception $e) {
                    // Return error as JSON
                    sendJsonResponse([
                        'success' => false, 
                        'error' => 'Exception: ' . $e->getMessage()
                    ], 500);
                }
            }
            break;

        case 'DELETE':
            if (!isset($_GET['file'])) {
                sendJsonResponse(['error' => 'No file specified'], 400);
            }
            
            // Properly decode and validate filename
            $rawFilename = $_GET['file'];
            $filename = basename(urldecode($rawFilename));
            $filepath = $blogDir . '/' . $filename;
            
            // Verify the file exists
            if (!file_exists($filepath)) {
                sendJsonResponse(['error' => 'File not found'], 404);
            }
            
            // Verify we have permission to delete
            if (!is_writable($filepath)) {
                sendJsonResponse(['error' => 'File not writable'], 403);
            }
            
            // Delete the file with better error handling
            error_clear_last(); // Clear any previous errors
            
            if (!unlink($filepath)) {
                $error = error_get_last();
                $errorMsg = $error ? $error['message'] : 'Unknown error';
                sendJsonResponse([
                    'error' => 'Failed to delete file: ' . $errorMsg
                ], 500);
            }
            
            // Force file system synchronization and clear caches
            clearstatcache(true, $filepath);
            clearstatcache(true, $blogDir);
            
            // Regenerate RSS feed silently
            generateRSSFeed();
            
            sendJsonResponse(['success' => true]);
            break;

        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("Exception in blog API: " . $e->getMessage());
    sendJsonResponse([
        'error' => $e->getMessage()
    ], 500);
}

// End of file - no closing tag needed 