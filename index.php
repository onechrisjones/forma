<?php
/**
 * Forma CMS
 * A lightweight flat-file CMS
 * @author Chris Jones <@onechrisjones>
 * @link https://alta-forma.com
 */

// Error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('ROOT_DIR', __DIR__);
define('CONTENT_DIR', ROOT_DIR . '/content');
define('CONFIG_DIR', ROOT_DIR . '/config');
define('ADMIN_DIR', ROOT_DIR . '/admin');
define('CACHE_DIR', ROOT_DIR . '/cache');

// Include Parsedown for markdown support
require_once ROOT_DIR . '/lib/Parsedown.php';

// Include Twig
require_once ROOT_DIR . '/lib/Twig/init.php';

// Include common configuration
require_once ROOT_DIR . '/config.php';

// Load configuration
if (file_exists(CONFIG_DIR . '/config.json')) {
    $config = json_decode(file_get_contents(CONFIG_DIR . '/config.json'), true);
} else {
    $config = [];
}

/**
 * Basic caching functions
 */
function isCachingEnabled() {
    global $config;
    $enabled = isset($config['cache']['enabled']) && $config['cache']['enabled'] === true;
    error_log("isCachingEnabled check: " . ($enabled ? 'true' : 'false') . " (config value: " . (isset($config['cache']['enabled']) ? var_export($config['cache']['enabled'], true) : 'not set') . ")");
    return $enabled;
}

function shouldCachePath($path) {
    global $config;
    
    if (!isCachingEnabled()) {
        error_log("Caching disabled for path: $path");
        return false;
    }
    
    // Don't cache admin routes
    if (strpos($path, '/admin') === 0) {
        error_log("Not caching admin path: $path");
        return false;
    }
    
    // Check excluded paths
    if (isset($config['cache']['excluded_paths']) && is_array($config['cache']['excluded_paths'])) {
        foreach ($config['cache']['excluded_paths'] as $excludedPath) {
            if ($excludedPath === $path || ($excludedPath !== '/' && strpos($path, $excludedPath) === 0)) {
                error_log("Path excluded from cache: $path (matches $excludedPath)");
                return false;
            }
        }
    }
    
    // Special handling for blog and podcast URLs
    // They use dynamic templates, so don't check for content files
    if (strpos($path, '/blog') === 0 || strpos($path, '/podcast') === 0) {
        error_log("Special route eligible for caching: $path");
        return true;
    }
    
    error_log("Path eligible for caching: $path");
    return true;
}

function getCacheFilePath($path) {
    // Generate a cache-friendly filename
    $cacheFile = $path;
    error_log("getCacheFilePath: Original path: $path");
    
    // Ensure we have a valid path
    if (empty($cacheFile) || $cacheFile === '') {
        $cacheFile = '/home';
        error_log("Empty path detected, using default: $cacheFile");
    }
    
    // Home page special case
    if ($cacheFile === '/') {
        $cacheFile = '/home';
        error_log("Root path detected, using: $cacheFile");
    }
    
    // Ensure path is clean
    $cacheFile = rtrim($cacheFile, '/');
    
    // Handle special routes like /blog and /podcast
    if ($cacheFile === '/blog') {
        $cacheFile = '/blog/index';
        error_log("Blog index path detected, using: $cacheFile");
    } else if ($cacheFile === '/podcast') {
        $cacheFile = '/podcast/index';
        error_log("Podcast index path detected, using: $cacheFile");
    }
    
    // Full path to cache file
    $fullCachePath = CACHE_DIR . $cacheFile . '.html';
    error_log("Full cache path: $fullCachePath for request path: $path");
    
    // Create directory structure if needed
    $cacheDir = dirname($fullCachePath);
    if (!file_exists($cacheDir)) {
        $result = mkdir($cacheDir, 0777, true); // Changed from 0755 to 0777 for testing
        error_log("Creating cache directory: $cacheDir - " . ($result ? 'Success' : 'Failed: ' . (error_get_last() ? error_get_last()['message'] : 'Unknown error')));
    }
    
    return $fullCachePath;
}

function getCachedPage($path) {
    error_log("getCachedPage called for path: $path");
    
    if (!shouldCachePath($path)) {
        error_log("getCachedPage: Not using cache for path: $path");
        return false;
    }
    
    $cacheFile = getCacheFilePath($path);
    error_log("getCachedPage: Looking for cache file: $cacheFile");
    
    if (!file_exists($cacheFile)) {
        error_log("Cache miss: $cacheFile (file doesn't exist)");
        return false;
    }
    
    // Check if cache is expired
    global $config;
    $cacheTtl = $config['cache']['ttl'] ?? 3600; // Default 1 hour
    $fileAge = time() - filemtime($cacheFile);
    error_log("Cache file age: $fileAge seconds (TTL: $cacheTtl)");
    
    if ($fileAge > $cacheTtl) {
        error_log("Cache expired: $cacheFile (age: {$fileAge}s, ttl: {$cacheTtl}s)");
        return false; // Cache expired
    }
    
    $content = file_get_contents($cacheFile);
    if ($content === false) {
        error_log("Error reading cache file: $cacheFile");
        return false;
    }
    
    error_log("Cache hit: $cacheFile (" . strlen($content) . " bytes)");
    return $content;
}

function cachePageOutput($path, $output) {
    error_log("cachePageOutput called for path: $path with " . strlen($output) . " bytes");
    
    if (!shouldCachePath($path)) {
        error_log("Not caching due to shouldCachePath() returning false for: $path");
        return false;
    }
    
    // Don't cache if this is a cache-warming request
    if (isset($_SERVER['HTTP_X_CACHE_WARM'])) {
        error_log("Not caching due to cache-warm header: $path");
        return false;
    }
    
    // Don't cache empty output
    if (empty($output)) {
        error_log("Not caching empty output for: $path");
        return false;
    }
    
    $cacheFile = getCacheFilePath($path);
    error_log("Preparing to cache output for path: $path to file: $cacheFile");
    
    // Add cache metadata to the output
    $output .= "\n<!-- Cache metadata start -->";
    $output .= "\n<!-- Cached at: " . date('Y-m-d H:i:s') . " -->";
    $output .= "\n<!-- Cache path: " . $path . " -->";
    $output .= "\n<!-- Cache file: " . basename($cacheFile) . " -->";
    $output .= "\n<!-- Cache file full path: " . $cacheFile . " -->";
    $output .= "\n<!-- Cache TTL: " . ($config['cache']['ttl'] ?? 3600) . " seconds -->";
    $output .= "\n<!-- Cache metadata end -->";
    
    // Verify cache directory exists
    $cacheDir = dirname($cacheFile);
    if (!file_exists($cacheDir)) {
        error_log("Creating cache directory: $cacheDir");
        $result = mkdir($cacheDir, 0777, true);
        error_log("Directory creation result: " . ($result ? "Success" : "Failed: " . error_get_last()['message']));
    }
    
    error_log("Cache directory permissions: " . substr(sprintf('%o', fileperms($cacheDir)), -4));
    error_log("Writing " . strlen($output) . " bytes to cache file: $cacheFile");
    
    // First, try to write to a temporary file, then rename it
    // This avoids race conditions where the file is partially written when read
    $tempFile = $cacheFile . '.tmp.' . uniqid();
    $result = file_put_contents($tempFile, $output);
    
    if ($result === false) {
        error_log("Cache write FAILED (temp file): " . (error_get_last() ? error_get_last()['message'] : 'Unknown error'));
        return false;
    }
    
    // Now rename the temp file to the final file
    if (!rename($tempFile, $cacheFile)) {
        error_log("Failed to rename temp cache file: " . (error_get_last() ? error_get_last()['message'] : 'Unknown error'));
        unlink($tempFile); // Clean up the temp file
        return false;
    }
    
    error_log("Cache write SUCCESS: $result bytes written to $cacheFile");
    return $result;
}

// Basic routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove trailing slashes
$path = rtrim($path, '/');

// Log the path for debugging
error_log("Original request path: " . $path);

// Fix empty path for home page
if ($path === '') {
    $path = '/';
    error_log("Empty path detected, setting to root path: $path");
}

// Admin panel route - handle this BEFORE any caching logic
if ($path === '/admin') {
    error_log("Admin route detected, bypassing cache");
    require ADMIN_DIR . '/index.php';
    exit;
}

// Check for cached version first (only for GET requests)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("Checking for cached version of path: $path");
    $cachedOutput = getCachedPage($path);
    if ($cachedOutput !== false) {
        error_log("Serving cached version of path: $path");
        echo $cachedOutput;
        exit;
    } else {
        error_log("No cached version found for path: $path, will generate content");
    }
}

// Start output buffering to capture rendered content for caching
ob_start();

// Add a timestamp comment for debugging cache
echo "<!-- Page generated at: " . date('Y-m-d H:i:s') . " -->\n";

// ===== SPECIAL ROUTE HANDLERS - MUST BE BEFORE CONTENT FILE LOOKUP =====

// Check if this is a podcast URL - SPECIAL ROUTE HANDLER
if (strpos($path, '/podcast') === 0) {
    error_log("Podcast route detected: $path");
    // Special case for exact '/podcast' path (no trailing slash)
    if ($path === '/podcast') {
        $episodeId = '';
        error_log("Podcast archive page requested");
    } else {
        $episodeId = substr($path, 9); // Remove '/podcast/' from the path
        error_log("Podcast episode requested with ID: $episodeId");
    }
    
    // Load podcast data
    $podcastFile = CONTENT_DIR . '/podcast.json';
    error_log("Looking for podcast data: $podcastFile (exists: " . (file_exists($podcastFile) ? 'yes' : 'no') . ")");
    
    if (!file_exists($podcastFile)) {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        echo "<p>Podcast data not found.</p>";
        
        // Finalize cache and exit
        $output = ob_get_contents();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            cachePageOutput($path, $output);
        }
        ob_end_flush();
        exit;
    }
    
    $podcastData = json_decode(file_get_contents($podcastFile), true);
    if (!$podcastData || !isset($podcastData['episodes'])) {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        echo "<p>Invalid podcast data.</p>";
        
        // Finalize cache and exit
        $output = ob_get_contents();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            cachePageOutput($path, $output);
        }
        ob_end_flush();
        exit;
    }
    
    // Get basic podcast info from config
    $podcastInfo = $config['podcast'] ?? [];
    
    // If no episode ID, show podcast archive page
    if (empty($episodeId)) {
        // Sort episodes by publish date (newest first)
        usort($podcastData['episodes'], function($a, $b) {
            $dateA = strtotime($a['publish_date'] ?? '');
            $dateB = strtotime($b['publish_date'] ?? '');
            return $dateB - $dateA;
        });
        
        // Use the podcast-archive template page with Twig
        $podcastArchiveTemplate = CONTENT_DIR . '/pages/podcast-archive.html';
        error_log("Looking for podcast archive template: $podcastArchiveTemplate (exists: " . (file_exists($podcastArchiveTemplate) ? 'yes' : 'no') . ")");
        
        if (file_exists($podcastArchiveTemplate)) {
            try {
                // Read the template
                $templateContent = file_get_contents($podcastArchiveTemplate);
                
                // Remove META section if present
                if (preg_match('/^\s*<!--META\s*(.*?)\s*-->/s', $templateContent, $matches)) {
                    $templateContent = preg_replace('/^\s*<!--META\s*(.*?)\s*-->\s*/s', '', $templateContent);
                }
                
                // Process the template with Twig
                global $twig;
                
                // Make sure we have a Twig instance
                if (!$twig) {
                    throw new Exception("Twig is not initialized");
                }
                
                // Register common filters (cached)
                registerTwigFilters();
                
                // Create a template from the content
                $template = $twig->createTemplate($templateContent);
                
                // Set up the context variables
                $context = [
                    'episodes' => $podcastData['episodes'],
                    'podcast' => [
                        'title' => $podcastInfo['title'] ?? $config['site']['title'] ?? 'Podcast',
                        'description' => $podcastInfo['description'] ?? '',
                        'cover_art' => $podcastInfo['image'] ?? ''
                    ],
                    'site' => [
                        'title' => $config['site']['title'] ?? 'My Site',
                        'description' => $config['site']['description'] ?? '',
                        'url' => $config['site']['url'] ?? ''
                    ],
                    'config' => $config
                ];
                
                // Render the template
                $content = $template->render($context);
                
                // Parse shortcodes in the rendered content
                $content = parseShortcodes($content);
                
                // Output the rendered template
                ob_clean(); // Clear buffer but keep buffering active
                echo $content;
                
                // Finalize cache and exit
                $output = ob_get_contents();
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    error_log("Caching podcast archive page");
                    cachePageOutput($path, $output);
                }
                ob_end_flush();
                exit;
            } catch (Exception $e) {
                error_log("Error rendering podcast archive template: " . $e->getMessage());
                // Fall back to default rendering below
            }
        } else {
            error_log("Podcast archive template not found, using fallback rendering");
        }
    } else {
        // Find the specific episode
        $episode = null;
        foreach ($podcastData['episodes'] as $ep) {
            if ($ep['id'] === $episodeId) {
                $episode = $ep;
                break;
            }
        }
        
        if (!$episode) {
            header("HTTP/1.0 404 Not Found");
            echo "<h1>404 Not Found</h1>";
            echo "<p>Episode not found.</p>";
            
            // Finalize cache and exit
            $output = ob_get_contents();
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                cachePageOutput($path, $output);
            }
            ob_end_flush();
            exit;
        }
        
        // Convert show notes from markdown to HTML if present
        if (!empty($episode['show_notes'])) {
            $parsedown = new Parsedown();
            $episode['show_notes_html'] = $parsedown->text($episode['show_notes']);
        }
        
        // Use the podcast-single template page with Twig
        $podcastSingleTemplate = CONTENT_DIR . '/pages/podcast-single.html';
        error_log("Looking for podcast single template: $podcastSingleTemplate (exists: " . (file_exists($podcastSingleTemplate) ? 'yes' : 'no') . ")");
        
        if (file_exists($podcastSingleTemplate)) {
            try {
                // Read the template
                $templateContent = file_get_contents($podcastSingleTemplate);
                
                // Remove META section if present
                if (preg_match('/^\s*<!--META\s*(.*?)\s*-->/s', $templateContent, $matches)) {
                    $templateContent = preg_replace('/^\s*<!--META\s*(.*?)\s*-->\s*/s', '', $templateContent);
                }
                
                // Process the template with Twig
                global $twig;
                
                // Make sure we have a Twig instance
                if (!$twig) {
                    throw new Exception("Twig is not initialized");
                }
                
                // Register common filters (cached)
                registerTwigFilters();
                
                // Create a template from the content
                $template = $twig->createTemplate($templateContent);
                
                // Set up the context variables
                $context = [
                    'episode' => $episode,
                    'podcast' => [
                        'title' => $podcastInfo['title'] ?? $config['site']['title'] ?? 'Podcast',
                        'description' => $podcastInfo['description'] ?? '',
                        'cover_art' => $podcastInfo['image'] ?? ''
                    ],
                    'site' => [
                        'title' => $config['site']['title'] ?? 'My Site',
                        'description' => $config['site']['description'] ?? '',
                        'url' => $config['site']['url'] ?? ''
                    ],
                    'config' => $config,
                    'podcast_feed' => '/feeds/podcast.xml'
                ];
                
                // Add debugging for the episode data
                error_log("Episode data: " . json_encode($episode));
                
                // Check if the audio file exists
                $audioFile = $episode['audio_file'] ?? null;
                if ($audioFile) {
                    // Check multiple possible locations for the audio file
                    $audioPath1 = ROOT_DIR . '/uploads/' . $audioFile; // Absolute server path
                    $audioPath2 = UPLOADS_DIR . '/' . $audioFile;      // Using UPLOADS_DIR constant
                    
                    $context['audio_exists'] = file_exists($audioPath1) || file_exists($audioPath2);
                    $context['audio_path'] = '/uploads/' . $audioFile; // Browser-accessible path
                    
                    error_log("Audio file checks: $audioFile");
                    error_log("Path 1 ($audioPath1): " . (file_exists($audioPath1) ? 'EXISTS' : 'NOT FOUND'));
                    error_log("Path 2 ($audioPath2): " . (file_exists($audioPath2) ? 'EXISTS' : 'NOT FOUND'));
                    error_log("Setting context audio_exists: " . ($context['audio_exists'] ? 'true' : 'false'));
                    
                    // Ensure the file has a browser-accessible URL regardless of existence check
                    $context['audio_url'] = '/uploads/' . $audioFile;
                } else {
                    error_log("No audio file specified in episode data");
                    $context['audio_exists'] = false;
                    $context['audio_url'] = '';
                }
                
                // Render the template
                $renderedContent = $template->render($context);
                
                // Parse shortcodes in the rendered content
                $renderedContent = parseShortcodes($renderedContent);
                
                // Output the rendered template
                ob_clean(); // Clear buffer but keep buffering active
                echo $renderedContent;
                
                // Finalize cache and exit
                $output = ob_get_contents();
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    error_log("Caching podcast episode page");
                    cachePageOutput($path, $output);
                }
                ob_end_flush();
                exit;
            } catch (Exception $e) {
                error_log("Error rendering podcast single template: " . $e->getMessage());
                // Fall back to default rendering below
            }
        } else {
            error_log("Podcast single template not found, using fallback rendering");
        }
    }
    
    // Finalize cache and exit
    $output = ob_get_contents();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        cachePageOutput($path, $output);
    }
    ob_end_flush();
    exit;
}

// Check if this is a blog URL - SPECIAL ROUTE HANDLER
if (strpos($path, '/blog') === 0) {
    error_log("Blog route detected: $path");
    // Special case for exact '/blog' path (no trailing slash)
    if ($path === '/blog') {
        $slug = '';
        error_log("Blog archive page requested");
    } else {
        // Make sure we're removing the right number of characters
        // for paths like /blog/slug (should remove 6 characters: '/blog/')
        $slug = substr($path, 6); // Remove '/blog/' from the path
        error_log("Blog post requested with slug: $slug");
    }
    
    // OPTIMIZED: Single directory scan for both archive and individual posts
    $blogDir = CONTENT_DIR . '/blog';
    $posts = [];
    $postFile = null;
    
    if (is_dir($blogDir)) {
        // Check if blog directory exists
        error_log("Blog directory found: $blogDir");
        
        // Single scan handles both archive listing and post lookup
        foreach (scandir($blogDir) as $file) {
            if ($file === '.' || $file === '..' || $file === '.DS_Store') continue;
            if (!preg_match('/\.md$/i', $file)) continue;
            
            $content = file_get_contents($blogDir . '/' . $file);
            $postData = [];
            
            // Parse YAML front matter if present
            if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)/s', $content, $matches)) {
                $yamlData = [];
                if (function_exists('yaml_parse')) {
                    $yaml = yaml_parse($matches[1]);
                    $yamlData = is_array($yaml) ? $yaml : [];
                } else {
                    // Manual parsing 
                    $lines = explode("\n", $matches[1]);
                    foreach ($lines as $line) {
                        if (preg_match('/^\s*([^:]+):\s*(.*)$/i', $line, $kv)) {
                            $yamlData[trim($kv[1])] = trim($kv[2]);
                        }
                    }
                }
                
                // Get post data
                $postData = [
                    'title' => $yamlData['title'] ?? pathinfo($file, PATHINFO_FILENAME),
                    'date' => $yamlData['date'] ?? '',
                    'author' => $yamlData['author'] ?? '',
                    'description' => $yamlData['description'] ?? '',
                    'slug' => $yamlData['slug'] ?? pathinfo($file, PATHINFO_FILENAME),
                    'filename' => $file,
                    'content' => $matches[2] ?? $content,
                    'yamlData' => $yamlData
                ];
                
                // Add to posts array for archive
                if (!empty($postData['title'])) {
                    $posts[] = $postData;
                }
                
                // Check if this is the post we're looking for (if slug specified)
                if (!empty($slug) && !$postFile) {
                    // Check by slug in front matter
                    if (isset($yamlData['slug']) && trim($yamlData['slug']) === $slug) {
                        $postFile = $blogDir . '/' . $file;
                        $foundPostData = $postData;
                    }
                    // Check by filename
                    elseif (pathinfo($file, PATHINFO_FILENAME) === $slug) {
                        $postFile = $blogDir . '/' . $file;
                        $foundPostData = $postData;
                    }
                }
            }
        }
    } else {
        error_log("Blog directory not found: $blogDir");
    }
    
    // If no slug, show blog archive page
    if (empty($slug)) {
        // Sort posts by date (newest first)
        usort($posts, function($a, $b) {
            $dateA = strtotime($a['date']) ?: 0;
            $dateB = strtotime($b['date']) ?: 0;
            return $dateB - $dateA;
        });
        
        // Use the blog-archive template page with Twig
        $blogArchiveTemplate = CONTENT_DIR . '/pages/blog-archive.html';
        error_log("Looking for blog archive template: $blogArchiveTemplate (exists: " . (file_exists($blogArchiveTemplate) ? 'yes' : 'no') . ")");
        
        if (file_exists($blogArchiveTemplate)) {
            try {
                // Read the template
                $templateContent = file_get_contents($blogArchiveTemplate);
                
                // Remove META section if present
                if (preg_match('/^\s*<!--META\s*(.*?)\s*-->/s', $templateContent, $matches)) {
                    $templateContent = preg_replace('/^\s*<!--META\s*(.*?)\s*-->\s*/s', '', $templateContent);
                }
                
                // Process the template with Twig
                global $twig;
                
                // Make sure we have a Twig instance
                if (!$twig) {
                    throw new Exception("Twig is not initialized");
                }
                
                // Register common filters (cached)
                registerTwigFilters();
                
                // Create a template from the content
                $template = $twig->createTemplate($templateContent);
                
                // Set up the context variables
                $context = [
                    'posts' => $posts,
                    'site' => [
                        'title' => $config['site']['title'] ?? 'My Site',
                        'description' => $config['site']['description'] ?? '',
                        'url' => $config['site']['url'] ?? ''
                    ],
                    'config' => $config
                ];
                
                // Render the template
                $content = $template->render($context);
                
                // Parse shortcodes in the rendered content
                $content = parseShortcodes($content);
                
                // Output the rendered template
                ob_clean(); // Clear buffer but keep buffering active
                echo $content;
                
                // Finalize cache and exit
                $output = ob_get_contents();
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    error_log("Caching blog archive page");
                    cachePageOutput($path, $output);
                }
                ob_end_flush();
                exit;
            } catch (Exception $e) {
                error_log("Error rendering blog archive template: " . $e->getMessage());
                // Fall back to default rendering below
            }
        } else {
            error_log("Blog archive template not found, using fallback rendering");
        }
    } else if ($postFile && file_exists($postFile)) {
        // Use already-parsed data instead of re-reading file
        $content = $foundPostData['content'];
        $yamlData = $foundPostData['yamlData'];
        
        // Convert markdown to HTML if needed
        if (pathinfo($postFile, PATHINFO_EXTENSION) === 'md') {
            $parsedown = new Parsedown();
            $content = $parsedown->text($content);
        }
        
        // Parse shortcodes
        $content = parseShortcodes($content);
        
        // Prepare post data for template
        $postData = $yamlData;
        $postData['content'] = $content;
        $postData['slug'] = $slug;
        
        // Use the blog-single template page with Twig
        $blogSingleTemplate = CONTENT_DIR . '/pages/blog-single.html';
        error_log("Looking for blog single template: $blogSingleTemplate (exists: " . (file_exists($blogSingleTemplate) ? 'yes' : 'no') . ")");
        
        if (file_exists($blogSingleTemplate)) {
            try {
                // Read the template
                $templateContent = file_get_contents($blogSingleTemplate);
                
                // Remove META section if present
                if (preg_match('/^\s*<!--META\s*(.*?)\s*-->/s', $templateContent, $matches)) {
                    $templateContent = preg_replace('/^\s*<!--META\s*(.*?)\s*-->\s*/s', '', $templateContent);
                }
                
                // Process the template with Twig
                global $twig;
                
                // Make sure we have a Twig instance
                if (!$twig) {
                    throw new Exception("Twig is not initialized");
                }
                
                // Register common filters (cached)
                registerTwigFilters();
                
                // Create a template from the content
                $template = $twig->createTemplate($templateContent);
                
                // Set up the context variables
                $context = [
                    'post' => $postData,
                    'site' => [
                        'title' => $config['site']['title'] ?? 'My Site',
                        'description' => $config['site']['description'] ?? '',
                        'url' => $config['site']['url'] ?? ''
                    ],
                    'config' => $config
                ];
                
                // Render the template
                $renderedContent = $template->render($context);
                
                // Parse shortcodes in the rendered content
                $renderedContent = parseShortcodes($renderedContent);
                
                // Output the rendered template
                ob_clean(); // Clear buffer but keep buffering active
                echo $renderedContent;
                
                // Finalize cache and exit
                $output = ob_get_contents();
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    error_log("Caching blog post page");
                    cachePageOutput($path, $output);
                }
                ob_end_flush();
                exit;
            } catch (Exception $e) {
                error_log("Error rendering blog single template: " . $e->getMessage());
                // Fall back to default rendering below
            }
        } else {
            error_log("Blog single template not found, using fallback rendering");
        }
    } else {
        // No blog post found
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        echo "<p>Blog post not found.</p>";
        
        // Finalize cache and exit
        $output = ob_get_contents();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            cachePageOutput($path, $output);
        }
        ob_end_flush();
        exit;
    }
    
    // Finalize cache and exit
    $output = ob_get_contents();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        cachePageOutput($path, $output);
    }
    ob_end_flush();
    exit;
}

// ===== STANDARD CONTENT FILE LOOKUP =====

// For content file lookup, use a normalized path
$contentPath = $path;

// Default to home page if root path
if ($contentPath === '/') {
    $contentPath = '/home';
    error_log("Root path detected, using content path: $contentPath for file lookup");
}

// Look for content file using slug metadata first
$contentFile = null;
$pageFiles = glob(CONTENT_DIR . '/pages/*.{html,md}', GLOB_BRACE);

foreach ($pageFiles as $file) {
    $content = file_get_contents($file);
    
    // Check for META section at the beginning of the file
    if (preg_match('/^\s*<!--META\s*(.*?)\s*-->/s', $content, $matches)) {
        $metaContent = $matches[1];
        $metaLines = explode("\n", $metaContent);
        
        foreach ($metaLines as $line) {
            if (preg_match('/^\s*slug:\s*(.*)$/i', $line, $slugMatch)) {
                $slug = '/' . trim($slugMatch[1]);
                
                // Check if the slug matches the requested path
                if ($slug === $path) {
                    $contentFile = $file;
                    // Remove the META section from content
                    $content = preg_replace('/^\s*<!--META\s*(.*?)\s*-->\s*/s', '', $content);
                    break 2; // Break both loops
                }
            }
        }
    }
}

// If no page with the slug was found, try the traditional approach
if (!$contentFile) {
    $contentFile = CONTENT_DIR . '/pages' . $contentPath . '.html';
    if (!file_exists($contentFile)) {
        $contentFile = CONTENT_DIR . '/pages' . $contentPath . '.md';
    }
    error_log("Looking for content file at: $contentFile (exists: " . (file_exists($contentFile) ? 'yes' : 'no') . ")");
}

// 404 if no content found
if (!file_exists($contentFile)) {
    header("HTTP/1.0 404 Not Found");
    ob_clean(); // Clear buffer but keep buffering active
    echo "<h1>404 Not Found</h1>";
    echo "<p>The requested page could not be found.</p>";
    error_log("404 Not Found: Content file does not exist: $contentFile for path: $path (content path: $contentPath)");
    // Continue execution to allow for potential caching of 404 pages
} else {
    // Get the content
    $content = file_get_contents($contentFile);
    error_log("=== Page Content Processing ===");
    error_log("Loading page: " . $contentFile);
    error_log("Content length: " . strlen($content));
    
    // Check if this is a complete HTML document
    $isCompleteHtml = preg_match('/<\!DOCTYPE.*?>/i', $content) || preg_match('/<html.*?>/i', $content);
    error_log("Is complete HTML document: " . ($isCompleteHtml ? 'yes' : 'no'));

    // If it's a markdown file, parse it
    if (pathinfo($contentFile, PATHINFO_EXTENSION) === 'md') {
        error_log("Processing markdown file");
        // Parse YAML front matter if present
        $yamlData = [];
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)/s', $content, $matches)) {
            if (function_exists('yaml_parse')) {
                $yaml = yaml_parse($matches[1]);
                $yamlData = is_array($yaml) ? $yaml : [];
            } else {
                // Manual parsing if yaml extension not available
                $yamlLines = explode("\n", $matches[1]);
                foreach ($yamlLines as $line) {
                    if (preg_match('/^\s*([^:]+):\s*(.*)$/i', $line, $kv)) {
                        $yamlData[trim($kv[1])] = trim($kv[2]);
                    }
                }
            }
            $content = $matches[2];
        }
        
        // Convert markdown to HTML
        $parsedown = new Parsedown();
        $content = $parsedown->text($content);
    }

    // Parse any shortcodes in the content
    error_log("Processing shortcodes in content");
    $content = parseShortcodes($content);
    error_log("Finished processing shortcodes");

    // If it's a complete HTML document, output it directly
    if ($isCompleteHtml) {
        error_log("Outputting complete HTML document");
        
        // Instead of exiting, add content to the output buffer
        // This ensures it can be cached later
        ob_clean(); // Clear the buffer but keep buffering active
        echo $content;
        
        // Continue execution to allow caching
    } else {
        // Check if there's a layout file
        if (file_exists(CONTENT_DIR . '/templates/layout.html')) {
            $layout = file_get_contents(CONTENT_DIR . '/templates/layout.html');
            // Simple variable replacement
            $layout = str_replace('{{content}}', $content, $layout);
            $layout = str_replace('{{site_title}}', $config['site']['title'] ?? 'My Site', $layout);
            $layout = str_replace('{{site_description}}', $config['site']['description'] ?? '', $layout);
            echo $layout;
        } else {
            // If no layout file exists, wrap content in a basic HTML structure
            ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['site']['title'] ?? 'My Site'; ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <?php echo $content; ?>
</body>
</html>
            <?php
        }
    }
}

// Finalize caching
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log("=== FINALIZING CACHE ===");
    
    // Get the buffered content
    $output = ob_get_contents();
    $outputLength = strlen($output);
    error_log("Output buffer size: $outputLength bytes");
    
    // Add cache debugging info at the end of the HTML
    $output .= "\n<!-- Cache status: " . (isCachingEnabled() ? "Enabled" : "Disabled") . " -->";
    $output .= "\n<!-- Cache time: " . date('Y-m-d H:i:s') . " -->";
    $output .= "\n<!-- Request path: " . $path . " -->";
    $output .= "\n<!-- Content path: " . $contentPath . " -->";
    
    // Save to cache if applicable
    error_log("Attempting to cache for path: $path");
    $cacheResult = cachePageOutput($path, $output);
    
    // End output buffering and send to browser
    ob_end_flush();
    
    error_log("Cache result for $path: " . ($cacheResult ? "Cached successfully ($cacheResult bytes)" : "Not cached"));
    error_log("=== END CACHING ===");
} else {
    // For non-GET requests, just flush the buffer
    ob_end_flush();
}

// If we get here, something went wrong with the routing
error_log("WARNING: Execution reached the end of the script without a proper route handler for path: $path");

/**
 * Register common Twig filters (only once per request)
 */
function registerTwigFilters() {
    global $twig;
    static $filtersRegistered = false;
    
    if ($filtersRegistered || !$twig) {
        return;
    }
    
    // Add markdown filter
    $twig->addFilter(new \Twig\TwigFilter('markdown', function ($text) {
        $parsedown = new Parsedown();
        return $parsedown->text($text);
    }));
    
    // Add raw filter if not available
    try {
        $twig->getFilter('raw');
    } catch (\Twig\Error\RuntimeError $e) {
        $twig->addFilter(new \Twig\TwigFilter('raw', function ($text) {
            return $text;
        }, ['is_safe' => ['html']]));
    }
    
    $filtersRegistered = true;
}

/**
 * Parse shortcodes in the content
 * @param string $content
 * @return string
 */
function parseShortcodes($content) {
    static $shortcodes = null; // Cache shortcodes map for the request
    
    // Load shortcodes map only once per request
    if ($shortcodes === null) {
        $shortcodesFile = CONTENT_DIR . '/snippets/.shortcodes.json';
        $shortcodes = [];
        if (file_exists($shortcodesFile)) {
            $shortcodes = json_decode(file_get_contents($shortcodesFile), true) ?? [];
        }
    }
    
    // Match [[shortcode]] pattern and replace
    return preg_replace_callback('/\[\[(.*?)\]\]/', function($matches) use ($shortcodes) {
        $shortcode = trim($matches[1]);
        $filename = $shortcodes[$shortcode] ?? $shortcode . '.html';
        $snippetFile = CONTENT_DIR . '/snippets/' . $filename;
        
        if (file_exists($snippetFile)) {
            $snippetContent = file_get_contents($snippetFile);
            
            // Process Twig template if it contains Twig syntax
            if (strpos($snippetContent, '{%') !== false || strpos($snippetContent, '{{') !== false) {
                try {
                    global $twig;
                    if ($twig) {
                        $template = $twig->createTemplate($snippetContent);
                        $context = [
                            'config' => $GLOBALS['config'] ?? [],
                            'shortcode' => $shortcode,
                            'filename' => $filename
                        ];
                        $snippetContent = $template->render($context);
                    }
                } catch (Exception $e) {
                    error_log("Twig processing error in shortcode '$shortcode': " . $e->getMessage());
                }
            }
            
            return $snippetContent;
        }
        
        return $matches[0]; // Return original shortcode if not found
    }, $content);
}
?> 