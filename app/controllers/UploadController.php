<?php
// FILE: /app/controllers/UploadController.php

require_once __DIR__ . '/../core/Controller.php';

class UploadController extends Controller
{
    public function upload()
    {
        $this->requireAuth();

        if (!$this->validateCSRF()) {
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        if (!isset($_FILES['file'])) {
            $this->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        $file = $_FILES['file'];

        // Validate file
        $maxSize = config('app.upload.max_size', 10485760); // 10MB default
        $allowedTypes = config('app.upload.allowed_types', ['jpg', 'jpeg', 'png', 'pdf']);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Upload failed'], 400);
        }

        if ($file['size'] > $maxSize) {
            $this->json(['success' => false, 'message' => 'File too large'], 400);
        }

        $extension = getFileExtension($file['name']);

        if (!in_array($extension, $allowedTypes)) {
            $this->json(['success' => false, 'message' => 'File type not allowed'], 400);
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = __DIR__ . '/../../storage/uploads/' . $filename;

        // Ensure upload directory exists
        $uploadDir = dirname($uploadPath);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $this->json(['success' => false, 'message' => 'Failed to save file'], 500);
        }

        // Update tenant storage usage
        $user = $this->getUser();
        if ($user['tenant_id']) {
            $tenantUsageModel = $this->model('TenantUsage');
            $sizeMb = round($file['size'] / 1048576, 2);
            $tenantUsageModel->addStorageUsage($user['tenant_id'], $sizeMb);
        }

        $this->json([
            'success' => true,
            'filename' => $filename,
            'url' => $this->url('storage/uploads/' . $filename)
        ]);
    }
}
