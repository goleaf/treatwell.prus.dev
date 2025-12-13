---
title: File Upload System
description: Spec for implementing secure file upload functionality
tags: [files, upload, storage, security]
---

# File Upload System Specification

## Overview
This spec guides the implementation of a secure file upload system with proper validation, storage, and management capabilities.

## Requirements Analysis
- [ ] Secure file upload with validation
- [ ] Multiple file type support
- [ ] File size and dimension limits
- [ ] Virus scanning for uploaded files
- [ ] File organization and storage
- [ ] Image processing and thumbnails
- [ ] File access control and permissions

## Design Considerations
- Store files outside web root for security
- Implement proper file validation
- Use Laravel's filesystem abstraction
- Generate unique filenames to prevent conflicts
- Implement file access logging
- Consider cloud storage for scalability

## Implementation Tasks

### 1. Storage Configuration
```bash
php artisan storage:link
```
- [ ] Configure filesystem disks in `config/filesystems.php`
- [ ] Set up local and cloud storage options
- [ ] Configure proper permissions
- [ ] Create storage directories

### 2. File Upload Validation
- [ ] Create Form Request for file uploads
- [ ] Implement file type validation
- [ ] Set file size limits
- [ ] Validate image dimensions
- [ ] Check for malicious files

### 3. File Processing
- [ ] Generate unique filenames
- [ ] Create thumbnails for images
- [ ] Extract file metadata
- [ ] Implement virus scanning
- [ ] Process different file types

### 4. Database Schema
- [ ] Create files table migration
- [ ] Store file metadata
- [ ] Track file relationships
- [ ] Implement soft deletes
- [ ] Add indexing for performance

### 5. File Management
- [ ] Create file upload controller
- [ ] Implement file download/serving
- [ ] File deletion with cleanup
- [ ] Batch file operations
- [ ] File organization system

### 6. Security Implementation
- [ ] Access control for files
- [ ] Secure file serving
- [ ] File permission system
- [ ] Audit trail for file access
- [ ] Rate limiting for uploads

### 7. Frontend Interface
- [ ] Drag and drop upload interface
- [ ] Progress indicators
- [ ] File preview functionality
- [ ] Error handling and feedback
- [ ] Mobile-responsive design

### 8. Testing
- [ ] File upload tests
- [ ] Validation tests
- [ ] Security tests
- [ ] Performance tests
- [ ] Edge case handling

## File Upload Controller
```php
class FileUploadController extends Controller
{
    public function store(FileUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');
        
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs('uploads', $filename, 'private');
        
        // Create database record
        $fileRecord = File::create([
            'original_name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'user_id' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'file' => new FileResource($fileRecord),
        ]);
    }
    
    public function download(File $file): Response
    {
        // Check permissions
        $this->authorize('download', $file);
        
        // Log access
        FileAccess::create([
            'file_id' => $file->id,
            'user_id' => auth()->id(),
            'action' => 'download',
        ]);
        
        return Storage::disk('private')->download($file->path, $file->original_name);
    }
}
```

## File Model
```php
class File extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'original_name',
        'filename',
        'path',
        'mime_type',
        'size',
        'user_id',
    ];
    
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function getUrlAttribute(): string
    {
        return route('files.download', $this);
    }
    
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
```

## Validation Rules
```php
class FileUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpg,jpeg,png,pdf,doc,docx',
            ],
            'category' => ['nullable', 'string', 'max:50'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'file.max' => 'File size cannot exceed 10MB.',
            'file.mimes' => 'Only JPG, PNG, PDF, and DOC files are allowed.',
        ];
    }
}
```

## Security Features
- File type validation using MIME types
- Virus scanning integration
- Access control and permissions
- Secure file serving through controllers
- File access logging and auditing
- Rate limiting on upload endpoints

## Image Processing
```php
use Intervention\Image\Facades\Image;

public function createThumbnail(File $file): void
{
    if (!$file->isImage()) {
        return;
    }
    
    $image = Image::make(Storage::disk('private')->path($file->path));
    $thumbnail = $image->resize(300, 300, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
    });
    
    $thumbnailPath = 'thumbnails/' . $file->filename;
    Storage::disk('private')->put($thumbnailPath, $thumbnail->encode());
    
    $file->update(['thumbnail_path' => $thumbnailPath]);
}
```

## Best Practices
- Never trust user-provided filenames
- Store files outside the web root
- Implement proper access controls
- Use virus scanning for uploaded files
- Generate unique filenames
- Implement file cleanup for deleted records
- Monitor storage usage and implement quotas

## References
- Laravel File Storage Documentation
- Laravel Validation Documentation
- Image Processing Libraries (Intervention Image)