
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$upload_dir = "uploads/user_" . $user_id . "/";
$files = [];

// Check if directory exists
if (file_exists($upload_dir) && is_dir($upload_dir)) {
    $scan_files = scandir($upload_dir);
    
    foreach ($scan_files as $file) {
        // Skip . and .. directories
        if ($file != "." && $file != "..") {
            $file_path = $upload_dir . $file;
            
            // Get file information
            $file_size = filesize($file_path);
            $file_modified = filemtime($file_path);
            $file_type = mime_content_type($file_path);
            
            // Extract original filename from unique name (after underscore)
            $original_name = substr($file, strpos($file, '_') + 1);
            
            $files[] = [
                'name' => $original_name,
                'path' => $file_path,
                'size' => $file_size,
                'modified' => $file_modified,
                'type' => $file_type
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
} else {
    echo json_encode([
        'success' => true,
        'files' => [] // No files yet
    ]);
}
?>
