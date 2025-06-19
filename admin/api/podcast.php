<?php
session_start();

// For debugging purposes
error_log('Podcast API called, session ID: ' . session_id());
error_log('Session data: ' . print_r($_SESSION, true));
error_log('Auth check: ' . (isset($_SESSION['forma_user']) ? 'Passed' : 'Failed'));

// Check if user is logged in
if (!isset($_SESSION['forma_user'])) {
    http_response_code(401);
    $response = [
        'error' => 'Unauthorized',
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'debug' => 'No forma_user in session'
    ];
    echo json_encode($response);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Get the content directory path
$contentDir = dirname(dirname(dirname(__FILE__))) . '/content';
$feedsDir = dirname(dirname(dirname(__FILE__))) . '/feeds';
$uploadsDir = dirname(dirname(dirname(__FILE__))) . '/uploads';

// For debugging only
error_log('Content dir: ' . $contentDir);

// Create directories if they don't exist
if (!file_exists($feedsDir)) {
    mkdir($feedsDir, 0755, true);
}

// Try both possible locations for the podcast file
$podcastFile = $contentDir . '/podcast.json';
$legacyPodcastFile = $contentDir . '/podcast/episodes.json';

// For debugging
error_log('Main podcast file path: ' . $podcastFile);
error_log('Legacy podcast file path: ' . $legacyPodcastFile);
error_log('Main file exists: ' . (file_exists($podcastFile) ? 'Yes' : 'No'));
error_log('Legacy file exists: ' . (file_exists($legacyPodcastFile) ? 'Yes' : 'No'));

// Load episodes data
if (file_exists($podcastFile)) {
    $podcastData = json_decode(file_get_contents($podcastFile), true) ?? ['episodes' => []];
    $episodes = $podcastData['episodes'] ?? [];
    
    // For debugging only
    error_log('Podcast data loaded from main file, found ' . count($episodes) . ' episodes');
} elseif (file_exists($legacyPodcastFile)) {
    // Try legacy location
    $episodes = json_decode(file_get_contents($legacyPodcastFile), true) ?? [];
    $podcastData = ['episodes' => $episodes];
    
    // For debugging only
    error_log('Podcast data loaded from legacy file, found ' . count($episodes) . ' episodes');
} else {
    $podcastData = ['episodes' => []];
    $episodes = [];
    file_put_contents($podcastFile, json_encode($podcastData, JSON_PRETTY_PRINT));
    
    // For debugging only
    error_log('Podcast file not found, created empty podcast.json');
}

// For debugging - show what we're about to return
error_log('Episodes to return: ' . json_encode($episodes));

/**
 * Generate podcast RSS feed
 */
function generatePodcastFeed() {
    global $contentDir, $feedsDir, $episodes, $uploadsDir;
    
    // Get podcast settings
    $settings = json_decode(file_get_contents($contentDir . '/../config/config.json'), true);
    $podcastSettings = $settings['podcast'] ?? [];
    $siteUrl = $settings['site']['url'] ?? 'http://localhost';
    
    // Start RSS feed
    $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $rss .= '<rss version="2.0" ' .
            'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" ' .
            'xmlns:content="http://purl.org/rss/1.0/modules/content/" ' .
            'xmlns:podcast="https://podcastindex.org/namespace/1.0" ' .
            'xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $rss .= '<channel>' . "\n";
    
    // Channel information
    $rss .= '<title>' . htmlspecialchars($podcastSettings['title'] ?? '') . '</title>' . "\n";
    $rss .= '<link>' . htmlspecialchars($siteUrl) . '</link>' . "\n";
    $rss .= '<language>' . htmlspecialchars($podcastSettings['language'] ?? 'en-us') . '</language>' . "\n";
    $rss .= '<copyright>Copyright ' . date('Y') . ' ' . htmlspecialchars($podcastSettings['author'] ?? '') . '</copyright>' . "\n";
    $rss .= '<itunes:subtitle>' . htmlspecialchars($podcastSettings['description'] ?? '') . '</itunes:subtitle>' . "\n";
    $rss .= '<itunes:author>' . htmlspecialchars($podcastSettings['author'] ?? '') . '</itunes:author>' . "\n";
    $rss .= '<itunes:summary>' . htmlspecialchars($podcastSettings['description'] ?? '') . '</itunes:summary>' . "\n";
    $rss .= '<description>' . htmlspecialchars($podcastSettings['description'] ?? '') . '</description>' . "\n";
    $rss .= '<itunes:owner>' . "\n";
    $rss .= '    <itunes:name>' . htmlspecialchars($podcastSettings['author'] ?? '') . '</itunes:name>' . "\n";
    $rss .= '    <itunes:email>' . htmlspecialchars($podcastSettings['email'] ?? '') . '</itunes:email>' . "\n";
    $rss .= '</itunes:owner>' . "\n";
    
    // Fix cover art URL - ensure it has full domain
    $coverArt = $podcastSettings['image'] ?? '';
    if ($coverArt && !preg_match('/^https?:\/\//', $coverArt)) {
        $coverArt = $siteUrl . '/uploads/' . $coverArt;
    }
    $rss .= '<itunes:image href="' . htmlspecialchars($coverArt) . '" />' . "\n";
    $rss .= '<itunes:category text="' . htmlspecialchars($podcastSettings['category'] ?? '') . '">' . "\n";
    if (isset($podcastSettings['subcategory']) && !empty($podcastSettings['subcategory'])) {
        $rss .= '    <itunes:category text="' . htmlspecialchars($podcastSettings['subcategory']) . '" />' . "\n";
    }
    $rss .= '</itunes:category>' . "\n";
    $rss .= '<itunes:explicit>' . ($podcastSettings['explicit'] === 'yes' ? 'yes' : 'no') . '</itunes:explicit>' . "\n";
    
    // Add atom:link for podcast feed URL (self-reference, required by many validators)
    $rss .= '<atom:link href="' . htmlspecialchars($siteUrl . '/feeds/podcast.xml') . '" rel="self" type="application/rss+xml" />' . "\n";
    
    // Sort episodes by publish date
    usort($episodes, function($a, $b) {
        return strtotime($b['publish_date']) - strtotime($a['publish_date']);
    });
    
    // For debugging
    error_log("Starting to add episodes to feed, total episodes: " . count($episodes));
    $validEpisodeCount = 0;
    
    // Add episodes
    foreach ($episodes as $episode) {
        // Skip episodes without required fields
        if (empty($episode['title']) || empty($episode['audio_file'])) {
            error_log("Skipping episode without title or audio file: " . ($episode['id'] ?? 'unknown'));
            continue;
        }
        
        // Skip future episodes
        if (!isset($episode['publish_date']) || strtotime($episode['publish_date']) > time()) {
            error_log("Skipping future episode: " . $episode['title'] . " (" . ($episode['publish_date'] ?? 'unknown date') . ")");
            continue; 
        }
        
        $validEpisodeCount++;
        error_log("Adding episode to feed: " . $episode['title']);

        // Format audio file URL
        $audioUrl = $episode['audio_file'];
        if (!preg_match('/^https?:\/\//', $audioUrl)) {
            $audioUrl = $siteUrl . '/uploads/' . $audioUrl;
        }
        
        // Try to get the file size for the audio file
        $fileSize = 0;
        $localPath = '';
        $mimeType = 'audio/mpeg'; // Default to MP3
        
        if (!preg_match('/^https?:\/\//', $episode['audio_file'])) {
            $localPath = $uploadsDir . '/' . $episode['audio_file'];
            if (file_exists($localPath)) {
                $fileSize = filesize($localPath);
                
                // Detect MIME type based on file extension
                $extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
                switch ($extension) {
                    case 'mp3':
                        $mimeType = 'audio/mpeg';
                        break;
                    case 'm4a':
                    case 'mp4':
                        $mimeType = 'audio/mp4';
                        break;
                    case 'ogg':
                        $mimeType = 'audio/ogg';
                        break;
                    case 'wav':
                        $mimeType = 'audio/wav';
                        break;
                }
                
                error_log("Found local audio file, size: " . $fileSize . " bytes, type: " . $mimeType . " - " . $localPath);
            } else {
                error_log("Audio file not found locally: " . $localPath);
            }
        } else {
            // For remote files, try to get headers to determine size
            $headers = get_headers($audioUrl, 1);
            if (isset($headers['Content-Length'])) {
                $fileSize = (int)$headers['Content-Length'];
                error_log("Found remote audio file, size from headers: " . $fileSize . " bytes");
            }
            if (isset($headers['Content-Type'])) {
                $mimeType = $headers['Content-Type'];
                error_log("Content type from headers: " . $mimeType);
            }
        }
        
        // Format episode art URL
        $episodeArt = isset($episode['episode_art']) ? $episode['episode_art'] : null;
        if ($episodeArt && !preg_match('/^https?:\/\//', $episodeArt)) {
            $episodeArt = $siteUrl . '/uploads/' . $episodeArt;
        }
        
        $rss .= '<item>' . "\n";
        $rss .= '    <title>' . htmlspecialchars($episode['title']) . '</title>' . "\n";
        $rss .= '    <itunes:title>' . htmlspecialchars($episode['title']) . '</itunes:title>' . "\n";
        $rss .= '    <description>' . htmlspecialchars($episode['description']) . '</description>' . "\n";
        $rss .= '    <itunes:summary>' . htmlspecialchars($episode['description']) . '</itunes:summary>' . "\n";
        
        if (isset($episode['show_notes']) && !empty($episode['show_notes'])) {
            // Convert Markdown to HTML
            $html = $episode['show_notes'];
            
            // Make sure Parsedown is available
            require_once dirname(dirname(dirname(__FILE__))) . '/lib/Parsedown.php';
            
            if (class_exists('Parsedown')) {
                $parsedown = new Parsedown();
                $html = $parsedown->text($html);
                error_log("Converted show notes to HTML with Parsedown for episode: " . $episode['title']);
            } else {
                // Simple fallback for Markdown conversion
                $html = nl2br(htmlspecialchars($html));
                error_log("Parsedown not available, using fallback conversion for episode: " . $episode['title']);
            }
            
            $rss .= '    <content:encoded><![CDATA[' . $html . ']]></content:encoded>' . "\n";
        }
        
        $rss .= '    <enclosure url="' . htmlspecialchars($audioUrl) . '" length="' . $fileSize . '" type="' . $mimeType . '" />' . "\n";
        $rss .= '    <guid isPermaLink="false">' . htmlspecialchars($episode['id']) . '</guid>' . "\n";
        $rss .= '    <pubDate>' . date('r', strtotime($episode['publish_date'])) . '</pubDate>' . "\n";
        $rss .= '    <itunes:duration>' . htmlspecialchars($episode['duration']) . '</itunes:duration>' . "\n";
        $rss .= '    <itunes:explicit>' . ($episode['explicit'] === 'true' ? 'yes' : 'no') . '</itunes:explicit>' . "\n";
        
        if (isset($episode['episode_number'])) {
            $rss .= '    <itunes:episode>' . intval($episode['episode_number']) . '</itunes:episode>' . "\n";
        }
        if (isset($episode['season_number'])) {
            $rss .= '    <itunes:season>' . intval($episode['season_number']) . '</itunes:season>' . "\n";
        }
        if (isset($episode['episode_type'])) {
            $rss .= '    <itunes:episodeType>' . htmlspecialchars($episode['episode_type']) . '</itunes:episodeType>' . "\n";
        }
        if ($episodeArt) {
            $rss .= '    <itunes:image href="' . htmlspecialchars($episodeArt) . '" />' . "\n";
        }
        if (isset($episode['keywords']) && !empty($episode['keywords'])) {
            $rss .= '    <itunes:keywords>' . htmlspecialchars($episode['keywords']) . '</itunes:keywords>' . "\n";
        }
        
        $rss .= '</item>' . "\n";
    }
    
    // Log how many episodes were actually included
    error_log("Finished adding episodes to feed, valid episodes included: " . $validEpisodeCount);
    
    $rss .= '</channel>' . "\n";
    $rss .= '</rss>';
    
    // Save RSS feed
    if (file_put_contents($feedsDir . '/podcast.xml', $rss)) {
        error_log("Successfully wrote podcast feed to " . $feedsDir . '/podcast.xml');
    } else {
        error_log("Failed to write podcast feed to " . $feedsDir . '/podcast.xml');
    }
}

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get specific episode
            $episode = null;
            foreach ($episodes as $ep) {
                if ($ep['id'] === $_GET['id']) {
                    $episode = $ep;
                    break;
                }
            }
            
            if ($episode) {
                echo json_encode($episode);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Episode not found']);
            }
        } else {
            // List all episodes
            echo json_encode($episodes);
            
            // For debugging only - log what we're sending
            error_log('Podcast API response: ' . json_encode($episodes));
        }
        break;

    case 'POST':
        // Check if this is a specific action
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'regenerateFeed':
                    // Just regenerate the podcast feed without creating episodes
                    generatePodcastFeed();
                    echo json_encode(['success' => true, 'message' => 'Podcast RSS feed regenerated successfully']);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Unknown action: ' . $_GET['action']]);
                    break;
            }
            break;
        }
        
        // If no action specified, handle as episode creation/update
        // Prepare episode data
        $episode = [
            'id' => $_POST['id'] ?? uniqid(),
            'title' => $_POST['title'] ?? '',
            'episode_number' => intval($_POST['episode_number'] ?? 0),
            'season_number' => intval($_POST['season_number'] ?? 1),
            'publish_date' => $_POST['publish_date'] ?? date('Y-m-d'),
            'duration' => $_POST['duration'] ?? '00:00:00',
            'explicit' => $_POST['explicit'] ?? 'false',
            'description' => $_POST['description'] ?? '',
            'audio_file' => $_POST['audio_file'] ?? '',
            'episode_type' => $_POST['episode_type'] ?? 'full',
            'show_notes' => $_POST['show_notes'] ?? '',
            'keywords' => $_POST['keywords'] ?? ''
        ];
        
        // Validate required fields
        if (empty($episode['title']) || empty($episode['audio_file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and audio file are required fields']);
            break;
        }
        
        if (!empty($_POST['episode_art'])) {
            $episode['episode_art'] = $_POST['episode_art'];
        }
        
        // Update or add episode
        $found = false;
        foreach ($episodes as $key => $ep) {
            if ($ep['id'] === $episode['id']) {
                $episodes[$key] = $episode;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $episodes[] = $episode;
        }
        
        // Save episodes to podcast.json
        $podcastData['episodes'] = $episodes;
        if (file_put_contents($podcastFile, json_encode($podcastData, JSON_PRETTY_PRINT))) {
            // Generate podcast feed
            generatePodcastFeed();
            
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save episode']);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            // Remove episode
            $newEpisodes = [];
            foreach ($episodes as $ep) {
                if ($ep['id'] !== $_GET['id']) {
                    $newEpisodes[] = $ep;
                }
            }
            
            $podcastData['episodes'] = $newEpisodes;
            if (file_put_contents($podcastFile, json_encode($podcastData, JSON_PRETTY_PRINT))) {
                // Generate podcast feed
                generatePodcastFeed();
                
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete episode']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Episode ID required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 