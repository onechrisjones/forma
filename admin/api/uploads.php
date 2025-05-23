<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['forma_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the uploads directory path
$uploadsDir = dirname(dirname(dirname(__FILE__))) . '/uploads';

// Debug output
error_log("Uploads directory: " . $uploadsDir);

// Create directory if it doesn't exist
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
    error_log("Created uploads directory: " . $uploadsDir);
}

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['file'])) {
            // Get specific file
            $filename = basename($_GET['file']);
            $filepath = $uploadsDir . '/' . $filename;
            
            if (file_exists($filepath)) {
                // For text files, return content
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, ['txt', 'md', 'html', 'js', 'css', 'php'])) {
                    echo file_get_contents($filepath);
                } else {
                    // For binary files, return file info
                    echo json_encode([
                        'filename' => $filename,
                        'size' => filesize($filepath),
                        'type' => mime_content_type($filepath)
                    ]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
            }
        } else {
            // List all files
            $files = array_diff(scandir($uploadsDir), ['.', '..']);
            error_log("Files found: " . print_r($files, true));
            echo json_encode(array_values($files));
        }
        break;

    case 'POST':
        // Handle file upload
        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
            $filename = basename($file['name']);
            $filepath = $uploadsDir . '/' . $filename;
            
            // Ensure filename is unique
            $i = 1;
            while (file_exists($filepath)) {
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $filepath = $uploadsDir . '/' . $name . '_' . $i . '.' . $ext;
                $i++;
            }
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload file']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded']);
        }
        break;

    case 'PUT':
        // Update file content
        parse_str(file_get_contents('php://input'), $putData);
        if (isset($putData['file']) && isset($putData['content'])) {
            $filename = basename($putData['file']);
            $filepath = $uploadsDir . '/' . $filename;
            
            if (file_exists($filepath)) {
                if (file_put_contents($filepath, $putData['content']) !== false) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update file']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'File and content required']);
        }
        break;

    case 'PATCH':
        // Rename file
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['oldFilename']) && isset($data['newFilename'])) {
            $oldPath = $uploadsDir . '/' . basename($data['oldFilename']);
            $newPath = $uploadsDir . '/' . basename($data['newFilename']);
            
            if (file_exists($oldPath)) {
                if (rename($oldPath, $newPath)) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to rename file']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Old and new filenames required']);
        }
        break;

    case 'DELETE':
        if (isset($_GET['file'])) {
            $filename = basename($_GET['file']);
            $filepath = $uploadsDir . '/' . $filename;
            
            if (file_exists($filepath) && unlink($filepath)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete file']);
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