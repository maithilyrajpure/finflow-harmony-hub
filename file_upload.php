
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Set upload directory based on user ID to segregate files
$user_id = $_SESSION['user_id'];
$upload_dir = "uploads/user_" . $user_id . "/";

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$response = ['success' => false, 'message' => '', 'files' => []];

// Check if files were uploaded
if (isset($_FILES['files'])) {
    $files = $_FILES['files'];
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        // Get file details
        $file_name = $files['name'][$i];
        $file_tmp = $files['tmp_name'][$i];
        $file_error = $files['error'][$i];
        $file_size = $files['size'][$i];
        
        // Generate unique filename to prevent overwrites
        $new_filename = uniqid() . '_' . $file_name;
        $destination = $upload_dir . $new_filename;
        
        // Validate file
        if ($file_error === 0) {
            // Check file size - limit to 5MB
            if ($file_size <= 5242880) { // 5MB in bytes
                // Move file to destination
                if (move_uploaded_file($file_tmp, $destination)) {
                    $response['files'][] = [
                        'name' => $file_name,
                        'path' => $destination,
                        'size' => $file_size
                    ];
                } else {
                    $response['message'] = 'Failed to move uploaded file.';
                }
            } else {
                $response['message'] = 'File size exceeds limit (5MB).';
            }
        } else {
            $response['message'] = 'Error uploading file. Error code: ' . $file_error;
        }
    }
    
    if (count($response['files']) > 0) {
        $response['success'] = true;
        $response['message'] = 'Files uploaded successfully!';
    }
} else {
    $response['message'] = 'No files were uploaded.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
