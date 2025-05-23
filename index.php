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

// Include Parsedown for markdown support
require_once __DIR__ . '/lib/Parsedown.php';

// Include Twig
require_once __DIR__ . '/lib/Twig/init.php';

// Include common configuration
require_once __DIR__ . '/config.php';

// Load configuration
if (file_exists(CONFIG_DIR . '/config.json')) {
    $config = json_decode(file_get_contents(CONFIG_DIR . '/config.json'), true);
} else {
    $config = [];
}

// Basic routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove trailing slashes
$path = rtrim($path, '/');

// Admin panel route
if ($path === '/admin') {
    require ADMIN_DIR . '/index.php';
    exit;
}

// Default to home page if no path specified
if ($path === '' || $path === '/') {
    $path = '/home';
}

// Check if this is a podcast URL
if (strpos($path, '/podcast') === 0) {
    // Special case for exact '/podcast' path (no trailing slash)
    if ($path === '/podcast') {
        $episodeId = '';
    } else {
        $episodeId = substr($path, 9); // Remove '/podcast/' from the path
    }
    
    // Load podcast data
    $podcastFile = CONTENT_DIR . '/podcast.json';
    if (!file_exists($podcastFile)) {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        echo "<p>Podcast data not found.</p>";
        exit;
    }
    
    $podcastData = json_decode(file_get_contents($podcastFile), true);
    if (!$podcastData || !isset($podcastData['episodes'])) {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 Not Found</h1>";
        echo "<p>Invalid podcast data.</p>";
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
                        'title' => $podcastInfo['title'] ?? $config['general']['site_title'] ?? 'Podcast',
                        'description' => $podcastInfo['description'] ?? '',
                        'cover_art' => $podcastInfo['cover_art'] ?? ''
                    ],
                    'site' => [
                        'title' => $config['general']['site_title'] ?? 'My Site',
                        'description' => $config['general']['site_description'] ?? '',
                        'url' => $config['general']['site_url'] ?? ''
                    ],
                    'config' => $config
                ];
                
                // Render the template
                $content = $template->render($context);
                
                // Parse shortcodes in the rendered content
                $content = parseShortcodes($content);
                
                // Output the rendered template
                echo $content;
                exit;
            } catch (Exception $e) {
                error_log("Error rendering podcast archive template: " . $e->getMessage());
                // Fall back to default rendering below
            }
        }
        
        // If we got here, either the template file doesn't exist or there was an error
        // Fall back to building the podcast archive HTML manually
        $content = '<h1>Podcast</h1>';
        $content .= '<div class="podcast-episodes">';
        
        foreach ($podcastData['episodes'] as $episode) {
            $content .= '<article class="episode">';
            $content .= '<h2><a href="/podcast/' . htmlspecialchars($episode['id']) . '">' . htmlspecialchars($episode['title']) . '</a></h2>';
            
            // Episode metadata
            $content .= '<div class="episode-meta">';
            $content .= '<span class="number">Episode: ' . htmlspecialchars($episode['episode_number']) . '</span>';
            $content .= '<span class="date">Date: ' . htmlspecialchars($episode['publish_date']) . '</span>';
            $content .= '<span class="duration">Duration: ' . htmlspecialchars($episode['duration']) . '</span>';
            $content .= '</div>';
            
            if (!empty($episode['description'])) {
                $content .= '<div class="description">' . htmlspecialchars($episode['description']) . '</div>';
            }
            
            $content .= '<div class="actions"><a href="/podcast/' . htmlspecialchars($episode['id']) . '">Listen Now</a></div>';
            $content .= '</article>';
        }
        
        $content .= '</div>';
        
        // Parse any shortcodes in the content
        $content = parseShortcodes($content);
        
        // Simple layout for fallback
        $layout = '<!DOCTYPE html>
<html>
<head>
    <title>Podcast - ' . htmlspecialchars($config['general']['site_title'] ?? 'My Site') . '</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .podcast-episodes {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .episode {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .episode h2 {
            margin-top: 0;
        }
        .episode-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #666;
            font-size: 0.9rem;
        }
        .actions a {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body>' . $content . '</body>
</html>';
        
        echo $layout;
        exit;
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
            exit;
        }
        
        // Convert show notes from markdown to HTML if present
        if (!empty($episode['show_notes'])) {
            $parsedown = new Parsedown();
            $episode['show_notes_html'] = $parsedown->text($episode['show_notes']);
        }
        
        // Use the podcast-single template page with Twig
        $podcastSingleTemplate = CONTENT_DIR . '/pages/podcast-single.html';
        
        // First, try to find a template with the podcast-single-template slug
        $pageFiles = glob(CONTENT_DIR . '/pages/*.html');
        foreach ($pageFiles as $file) {
            $fileContent = file_get_contents($file);
            if (preg_match('/^\s*<!--META\s*(.*?)\s*-->/s', $fileContent, $matches)) {
                $metaContent = $matches[1];
                $metaLines = explode("\n", $metaContent);
                
                foreach ($metaLines as $line) {
                    if (preg_match('/^\s*slug:\s*(.*)$/i', $line, $slugMatch)) {
                        $fileSlug = trim($slugMatch[1]);
                        if ($fileSlug === 'podcast-single-template') {
                            $podcastSingleTemplate = $file;
                            break 2; // Break both loops
                        }
                    }
                }
            }
        }
        
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
                        'title' => $podcastInfo['title'] ?? $config['general']['site_title'] ?? 'Podcast',
                        'description' => $podcastInfo['description'] ?? '',
                        'cover_art' => $podcastInfo['cover_art'] ?? ''
                    ],
                    'site' => [
                        'title' => $config['general']['site_title'] ?? 'My Site',
                        'description' => $config['general']['site_description'] ?? '',
                        'url' => $config['general']['site_url'] ?? ''
                    ],
                    'config' => $config
                ];
                
                // Render the template
                $content = $template->render($context);
                
                // Parse shortcodes in the rendered content
                $content = parseShortcodes($content);
                
                // Output the rendered template
                echo $content;
                exit;
            } catch (Exception $e) {
                error_log("Error rendering podcast single template: " . $e->getMessage());
                error_log("Error details: " . $e->getTraceAsString());
                
                // For debugging only - we'll display the error to see what's happening
                echo '<div style="background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px 0;">';
                echo '<h3>Template Rendering Error:</h3>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '</div>';
                
                // Fall back to default rendering below
            }
        }
        
        // Fall back to simple template if Twig fails
        $content = '<h1>' . htmlspecialchars($episode['title']) . '</h1>';
        
        // Episode metadata
        $content .= '<div class="episode-meta">';
        $content .= '<div class="meta-item"><strong>Episode:</strong> ' . htmlspecialchars($episode['episode_number']) . '</div>';
        $content .= '<div class="meta-item"><strong>Season:</strong> ' . htmlspecialchars($episode['season_number']) . '</div>';
        $content .= '<div class="meta-item"><strong>Date:</strong> ' . htmlspecialchars($episode['publish_date']) . '</div>';
        $content .= '<div class="meta-item"><strong>Duration:</strong> ' . htmlspecialchars($episode['duration']) . '</div>';
        $content .= '</div>';
        
        // Audio player
        $content .= '<div class="audio-player">';
        $content .= '<audio controls>';
        $content .= '<source src="/uploads/' . htmlspecialchars($episode['audio_file']) . '" type="audio/mpeg">';
        $content .= 'Your browser does not support the audio element.';
        $content .= '</audio>';
        $content .= '</div>';
        
        // Description
        if (!empty($episode['description'])) {
            $content .= '<div class="description">';
            $content .= '<h2>Description</h2>';
            $content .= '<p>' . htmlspecialchars($episode['description']) . '</p>';
            $content .= '</div>';
        }
        
        // Show notes
        if (!empty($episode['show_notes_html'])) {
            $content .= '<div class="show-notes">';
            $content .= '<h2>Show Notes</h2>';
            $content .= $episode['show_notes_html'];
            $content .= '</div>';
        }
        
        $content .= '<a href="/podcast" class="back-link">&larr; Back to All Episodes</a>';
        
        // Parse any shortcodes in the content
        $content = parseShortcodes($content);
        
        // Simple layout for fallback
        $layout = '<!DOCTYPE html>
<html>
<head>
    <title>' . htmlspecialchars($episode['title']) . ' - ' . htmlspecialchars($config['general']['site_title'] ?? 'My Site') . '</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .episode-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .meta-item {
            padding: 0.5rem 1rem;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .audio-player {
            margin-bottom: 2rem;
        }
        audio {
            width: 100%;
        }
        .description, .show-notes {
            margin-bottom: 2rem;
        }
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>' . $content . '</body>
</html>';
        
        echo $layout;
        exit;
    }
}

// Check if this is a blog URL
if (strpos($path, '/blog') === 0) {
    // Special case for exact '/blog' path (no trailing slash)
    if ($path === '/blog') {
        $slug = '';
    } else {
        // Make sure we're removing the right number of characters
        // for paths like /blog/slug (should remove 6 characters: '/blog/')
        $slug = substr($path, 6); // Remove '/blog/' from the path
    }
    
    // OPTIMIZED: Single directory scan for both archive and individual posts
    $blogDir = CONTENT_DIR . '/blog';
    $posts = [];
    $postFile = null;
    
    if (is_dir($blogDir)) {
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
                        'title' => $config['general']['site_title'] ?? 'My Site',
                        'description' => $config['general']['site_description'] ?? '',
                        'url' => $config['general']['site_url'] ?? ''
                    ],
                    'config' => $config
                ];
                
                // Render the template
                $content = $template->render($context);
                
                // Parse shortcodes in the rendered content
                $content = parseShortcodes($content);
                
                // Output the rendered template
                echo $content;
                exit;
            } catch (Exception $e) {
                error_log("Error rendering blog archive template: " . $e->getMessage());
                // Fall back to default rendering below
            }
        }
        
        // If we got here, either the template file doesn't exist or there was an error
        // Fall back to building the blog archive HTML manually
        $content = '<h1>Blog</h1>';
        $content .= '<div class="blog-posts">';
        
        foreach ($posts as $post) {
            $content .= '<article class="blog-post">';
            $content .= '<h2><a href="/blog/' . htmlspecialchars($post['slug']) . '">' . htmlspecialchars($post['title']) . '</a></h2>';
            
            if (!empty($post['date'])) {
                $content .= '<div class="date">' . htmlspecialchars($post['date']) . '</div>';
            }
            
            if (!empty($post['author'])) {
                $content .= '<div class="author">By ' . htmlspecialchars($post['author']) . '</div>';
            }
            
            if (!empty($post['description'])) {
                $content .= '<div class="description">' . htmlspecialchars($post['description']) . '</div>';
            }
            
            $content .= '<div class="read-more"><a href="/blog/' . htmlspecialchars($post['slug']) . '">Read more</a></div>';
            $content .= '</article>';
        }
        
        $content .= '</div>';
        
        // Parse any shortcodes in the content
        $content = parseShortcodes($content);
        
        // Render with layout
        if (file_exists(CONTENT_DIR . '/templates/blog-archive.html')) {
            $layout = file_get_contents(CONTENT_DIR . '/templates/blog-archive.html');
        } else if (file_exists(CONTENT_DIR . '/templates/layout.html')) {
            $layout = file_get_contents(CONTENT_DIR . '/templates/layout.html');
        } else {
            // Default to basic layout
            $layout = '<!DOCTYPE html>
<html>
<head>
    <title>Blog - {{site_title}}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .blog-posts {
            max-width: 800px;
            margin: 0 auto;
        }
        .blog-post {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .blog-post:last-child {
            border-bottom: none;
        }
        .date, .author {
            display: inline-block;
            margin-right: 15px;
            color: #666;
            font-size: 0.9em;
        }
        .description {
            margin-top: 10px;
        }
        .read-more {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    {{content}}
</body>
</html>';
        }
        
        // Replace variables in layout
        $layout = str_replace('{{content}}', $content, $layout);
        $layout = str_replace('{{site_title}}', $config['general']['site_title'] ?? 'My Site', $layout);
        
        echo $layout;
        exit;
    }
    
    // If blog post found, process it
    if ($postFile && file_exists($postFile)) {
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
                        'title' => $config['general']['site_title'] ?? 'My Site',
                        'description' => $config['general']['site_description'] ?? '',
                        'url' => $config['general']['site_url'] ?? ''
                    ],
                    'config' => $config
                ];
                
                // Render the template
                $renderedContent = $template->render($context);
                
                // Parse shortcodes in the rendered content
                $renderedContent = parseShortcodes($renderedContent);
                
                // Output the rendered template
                echo $renderedContent;
                exit;
            } catch (Exception $e) {
                error_log("Error rendering blog single template: " . $e->getMessage());
                // Fall back to default rendering below
            }
        }
        
        // Render the blog post with layout (fallback if template not found or error)
        if (file_exists(CONTENT_DIR . '/templates/blog-post.html')) {
            $layout = file_get_contents(CONTENT_DIR . '/templates/blog-post.html');
        } else if (file_exists(CONTENT_DIR . '/templates/layout.html')) {
            $layout = file_get_contents(CONTENT_DIR . '/templates/layout.html');
        } else {
            // Default to basic layout
            $layout = '<!DOCTYPE html>
<html>
<head>
    <title>{{title}} - {{site_title}}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h1>{{title}}</h1>
    <div class="post-meta">
        <span class="date">{{date}}</span>
        <span class="author">{{author}}</span>
    </div>
    <div class="content">
        {{content}}
    </div>
</body>
</html>';
        }
        
        // Replace variables in layout
        $layout = str_replace('{{content}}', $content, $layout);
        $layout = str_replace('{{title}}', $yamlData['title'] ?? 'Blog Post', $layout);
        $layout = str_replace('{{date}}', $yamlData['date'] ?? '', $layout);
        $layout = str_replace('{{author}}', $yamlData['author'] ?? '', $layout);
        $layout = str_replace('{{site_title}}', $config['general']['site_title'] ?? 'My Site', $layout);
        
        echo $layout;
        exit;
    }
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
    $contentFile = CONTENT_DIR . '/pages' . $path . '.html';
    if (!file_exists($contentFile)) {
        $contentFile = CONTENT_DIR . '/pages' . $path . '.md';
    }
}

// 404 if no content found
if (!file_exists($contentFile)) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Not Found</h1>";
    echo "<p>The requested page could not be found.</p>";
    exit;
}

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
        $yaml = yaml_parse($matches[1]);
        $content = $matches[2];
        $yamlData = is_array($yaml) ? $yaml : [];
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
    echo $content;
    exit;
}

// Check if there's a layout file
if (file_exists(CONTENT_DIR . '/templates/layout.html')) {
    $layout = file_get_contents(CONTENT_DIR . '/templates/layout.html');
    // Simple variable replacement
    $layout = str_replace('{{content}}', $content, $layout);
    $layout = str_replace('{{site_title}}', $config['general']['site_title'] ?? 'My Site', $layout);
    $layout = str_replace('{{site_description}}', $config['general']['site_description'] ?? '', $layout);
    echo $layout;
} else {
    // If no layout file exists, wrap content in a basic HTML structure
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['general']['site_title'] ?? 'My Site'; ?></title>
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