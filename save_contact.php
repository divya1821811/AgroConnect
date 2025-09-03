<?php
// Save contact form data securely and return JSON response

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Basic validation
    if ($name && $email && $subject && $message) {
        // Save to a private file (not accessible to public)
        $data = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'date' => date('Y-m-d H:i:s')
        ];
        $file = __DIR__ . '/private_contact_messages.csv';
        $isNew = !file_exists($file);
        $fp = fopen($file, 'a');
        if ($fp) {
            if ($isNew) {
                fputcsv($fp, array_keys($data));
            }
            fputcsv($fp, $data);
            fclose($fp);
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'File write error']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Validation error']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}