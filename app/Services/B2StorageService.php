<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class B2StorageService
{
    protected $bucketName;
    protected $bucketId;
    protected $apiUrl;
    protected $downloadUrl;
    protected $keyId;
    protected $applicationKey;
    protected $authorizationToken;
    protected $accountId;

    public function __construct()
    {
        $this->bucketName = env('BACKBLAZE_BUCKET');
        $this->bucketId = env('BACKBLAZE_BUCKET_ID', '85464b7255da46e5966d0612');
        $this->keyId = env('BACKBLAZE_KEY_ID');
        $this->applicationKey = env('BACKBLAZE_APPLICATION_KEY');
        
        // Get authorization token and URLs
        $authData = $this->getAuthorizationToken();
        $this->authorizationToken = $authData['authorizationToken'];
        $this->accountId = $authData['accountId'];
        $this->apiUrl = $authData['apiUrl'];
        $this->downloadUrl = $authData['downloadUrl'];
    }
    
    protected function getAuthorizationToken()
    {
        $authUrl = "https://api.backblazeb2.com/b2api/v2/b2_authorize_account";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode($this->keyId . ":" . $this->applicationKey)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("Failed to authorize with B2. HTTP code: {$httpCode}, Response: {$response}");
        }
        
        $authData = json_decode($response, true);
        return [
            'authorizationToken' => $authData['authorizationToken'],
            'accountId' => $authData['accountId'],
            'apiUrl' => $authData['apiUrl'],
            'downloadUrl' => $authData['downloadUrl']
        ];
    }

    public function uploadFile(UploadedFile $file, $folder = 'books/files')
    {
        try {
            // Get upload URL
            $uploadUrlResponse = $this->getUploadUrl();
            $uploadUrl = $uploadUrlResponse['uploadUrl'];
            $uploadAuthToken = $uploadUrlResponse['authorizationToken'];
            
            // Generate a unique filename while preserving the original extension
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $folder . '/' . $filename;

            // Get the file contents
            $fileContents = file_get_contents($file->getRealPath());
            $contentLength = strlen($fileContents);
            $contentSha1 = sha1($fileContents);
            
            // Get file modification time
            $lastModified = filemtime($file->getRealPath());
            
            // Set up the cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $uploadUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: " . $uploadAuthToken,
                "Content-Type: " . $file->getMimeType(),
                "Content-Length: " . $contentLength,
                "X-Bz-File-Name: " . $path,
                "X-Bz-Content-Sha1: " . $contentSha1,
                "X-Bz-Info-src_last_modified_millis: " . ($lastModified * 1000)
            ]);
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Check if the upload was successful
            if ($httpCode !== 200) {
                throw new \Exception("Failed to upload file to B2. HTTP code: {$httpCode}, Response: {$response}");
            }
            
            $uploadResult = json_decode($response, true);
            
            // Return the public URL using the download URL from authorization
            return $this->downloadUrl . "/file/" . $this->bucketName . "/" . $path;
        } catch (\Exception $e) {
            Log::error('B2 Upload Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    protected function getUploadUrl()
    {
        $url = $this->apiUrl . "/b2api/v2/b2_get_upload_url";
        $data = json_encode([
            "bucketId" => $this->bucketId
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: " . $this->authorizationToken,
            "Content-Type: application/json"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("Failed to get upload URL. HTTP code: {$httpCode}, Response: {$response}");
        }
        
        return json_decode($response, true);
    }

    public function uploadImage(UploadedFile $file, $folder = 'books/covers')
    {
        return $this->uploadFile($file, $folder);
    }

    public function uploadAuthorImage(UploadedFile $file)
    {
        // Validate that the file is an image
        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            throw new \Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }

        // Validate file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('File size too large. Maximum size allowed is 5MB.');
        }

        // Upload the image to the authors folder
        return $this->uploadFile($file, 'authors/images');
    }

    public function deleteFile($url)
    {
        try {
            // Extract the file ID from the URL
            $path = parse_url($url, PHP_URL_PATH);
            $path = ltrim($path, '/');
            
            // First, get the file ID
            $listUrl = $this->apiUrl . "/b2api/v2/b2_list_file_names";
            $listData = json_encode([
                "bucketId" => $this->bucketId,
                "prefix" => $path,
                "maxFileCount" => 1
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $listUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $listData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: " . $this->authorizationToken,
                "Content-Type: application/json"
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new \Exception("Failed to list file in B2. HTTP code: {$httpCode}, Response: {$response}");
            }
            
            $listResult = json_decode($response, true);
            
            if (empty($listResult['files'])) {
                // File not found, consider it deleted
                return true;
            }
            
            $fileId = $listResult['files'][0]['fileId'];
            
            // Now delete the file
            $deleteUrl = $this->apiUrl . "/b2api/v2/b2_delete_file_version";
            $deleteData = json_encode([
                "fileId" => $fileId,
                "fileName" => $path
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $deleteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $deleteData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: " . $this->authorizationToken,
                "Content-Type: application/json"
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new \Exception("Failed to delete file from B2. HTTP code: {$httpCode}, Response: {$response}");
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('B2 Delete Error: ' . $e->getMessage());
            throw $e;
        }
    }
} 