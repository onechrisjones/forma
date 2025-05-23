<?php
// Simple autoloader for Twig classes
spl_autoload_register(function ($class) {
    // Only handle Twig classes
    if (strpos($class, 'Twig\\') !== 0) {
        return;
    }

    // Convert namespace to file path
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 5)) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize Twig
$loader = new Twig\Loader\ArrayLoader();
$twig = new Twig\Environment($loader, [
    'autoescape' => false,
    'debug' => true,
    'cache' => false
]);

// Add custom functions
$twig->addFunction(new Twig\TwigFunction('file_get_contents', function($file) {
    return file_exists($file) ? file_get_contents($file) : '';
}));

$twig->addFunction(new Twig\TwigFunction('list_files', function($dir) {
    if (!file_exists($dir)) {
        return [];
    }
    $files = scandir($dir);
    $files = array_diff($files, ['.', '..', '.DS_Store']);
    return array_values($files); // Reindex array
}));

$twig->addFunction(new Twig\TwigFunction('ends_with', function($haystack, $needle) {
    return str_ends_with($haystack, $needle);
}));

$twig->addFunction(new Twig\TwigFunction('preg_match', function($pattern, $subject) {
    $matches = [];
    preg_match($pattern, $subject, $matches);
    return $matches;
}));

$twig->addFunction(new Twig\TwigFunction('yaml_parse', function($yaml) {
    // Simple YAML-like parser for front matter
    $lines = explode("\n", $yaml);
    $result = [];
    $currentArray = null;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Handle array items
        if (preg_match('/^\s*-\s+(.*)$/', $line, $matches)) {
            if ($currentArray !== null) {
                if (!isset($result[$currentArray])) {
                    $result[$currentArray] = [];
                }
                $result[$currentArray][] = trim($matches[1]);
            }
            continue;
        }
        
        // Handle key-value pairs
        if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
            $key = trim($matches[1]);
            $value = trim($matches[2]);
            
            if (empty($value)) {
                $currentArray = $key;
                $result[$key] = [];
            } else {
                $currentArray = null;
                if (strpos($value, ',') !== false) {
                    $value = array_map('trim', explode(',', $value));
                }
                $result[$key] = $value;
            }
        }
    }
    
    return $result;
}));

// Add debug extension
$twig->addExtension(new Twig\Extension\DebugExtension());

// Make $twig available globally
global $twig; 