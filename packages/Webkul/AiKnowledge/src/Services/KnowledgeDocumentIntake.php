<?php

namespace Webkul\AiKnowledge\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Tenant;
use Throwable;
use Webkul\AiKnowledge\Exceptions\KnowledgeDocumentIntakeException;
use Webkul\AiKnowledge\Models\KnowledgeDocument;
use Webkul\AiKnowledge\Repositories\KnowledgeDocumentRepository;
use ZipArchive;

/**
 * Stores a knowledge file for the current tenant and records a pending row.
 *
 * Indexing, chunking, and embeddings are intentionally not done here.
 */
class KnowledgeDocumentIntake
{
    private const DENIED_NAME_PARTS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php8',
        'exe', 'dll', 'so', 'js', 'mjs', 'svg', 'shtml', 'cgi', 'sh', 'bat', 'cmd', 'htaccess',
    ];

    public function __construct(
        private readonly KnowledgeDocumentRepository $documents,
    ) {}

    public function store(UploadedFile $file, ?int $uploadedBy = null): KnowledgeDocument
    {
        $this->currentTenant();

        if (! $file->isValid()) {
            throw new KnowledgeDocumentIntakeException('invalid_upload', 'The uploaded file is not valid.');
        }

        $originalFilename = $this->originalFilename($file->getClientOriginalName());
        $extension = $this->extensionFromFilename($originalFilename);
        $maxBytes = (int) config('ai-knowledge.max_bytes', 10 * 1024 * 1024);

        $size = $file->getSize();

        if ($size === false || $size > $maxBytes) {
            throw new KnowledgeDocumentIntakeException('too_large', 'The file exceeds the knowledge upload size limit.');
        }

        $realPath = $file->getRealPath();

        if ($realPath === false) {
            throw new KnowledgeDocumentIntakeException('invalid_upload', 'The uploaded file is not readable.');
        }

        $contents = file_get_contents($realPath);

        if ($contents === false) {
            throw new KnowledgeDocumentIntakeException('invalid_upload', 'The uploaded file could not be read.');
        }

        if (strlen($contents) === 0) {
            throw new KnowledgeDocumentIntakeException('empty', 'The file is empty.');
        }

        if (strlen($contents) > $maxBytes) {
            throw new KnowledgeDocumentIntakeException('too_large', 'The file exceeds the knowledge upload size limit.');
        }

        $mimeType = $this->assertContents($extension, $contents, $realPath);
        $directory = $this->storageDirectory();
        $storagePath = $directory.'/'.Str::uuid()->toString().'.'.$extension;

        if (! $this->isManagedStoragePath($storagePath)) {
            throw new KnowledgeDocumentIntakeException('unsafe_path', 'The storage path is not allowed.');
        }

        $disk = (string) config('ai-knowledge.disk', 'local');
        $written = Storage::disk($disk)->put($storagePath, $contents);

        if ($written === false) {
            throw new KnowledgeDocumentIntakeException('store_failed', 'The file could not be stored.');
        }

        try {
            /** @var KnowledgeDocument $document */
            $document = $this->documents->create([
                'original_filename' => $originalFilename,
                'storage_path' => $storagePath,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'size_bytes' => strlen($contents),
                'sha256' => hash('sha256', $contents),
                'status' => KnowledgeDocument::STATUS_PENDING,
                'uploaded_by' => $uploadedBy,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storagePath);

            throw $exception;
        }

        return $document;
    }

    public function delete(KnowledgeDocument $document): void
    {
        $this->currentTenant();

        if (! $this->isManagedStoragePath((string) $document->storage_path)) {
            throw new KnowledgeDocumentIntakeException('unsafe_path', 'The storage path is not allowed.');
        }

        $matches = $this->documents->findWhere([
            'id' => $document->getKey(),
            'storage_path' => $document->storage_path,
            'sha256' => $document->sha256,
        ]);

        if ($matches->isEmpty()) {
            throw new KnowledgeDocumentIntakeException('not_found', 'The document is not in the current tenant database.');
        }

        $disk = (string) config('ai-knowledge.disk', 'local');
        Storage::disk($disk)->delete($document->storage_path);
        $this->documents->delete($document->getKey());
    }

    private function currentTenant(): IsTenant
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            throw new KnowledgeDocumentIntakeException('no_tenant', 'A current tenant is required before storing knowledge files.');
        }

        return $tenant;
    }

    private function originalFilename(string $name): string
    {
        $name = trim($name);

        if (
            $name === ''
            || strlen($name) > 255
            || str_contains($name, "\0")
            || str_contains($name, '/')
            || str_contains($name, '\\')
            || str_contains($name, '..')
            || str_starts_with($name, '.')
        ) {
            throw new KnowledgeDocumentIntakeException('invalid_name', 'The file name is not allowed.');
        }

        return $name;
    }

    private function extensionFromFilename(string $name): string
    {
        $parts = explode('.', $name);

        if (count($parts) < 2) {
            throw new KnowledgeDocumentIntakeException('unsupported_type', 'The file type is not allowed.');
        }

        $extension = strtolower((string) array_pop($parts));
        $allowed = array_map('strtolower', (array) config('ai-knowledge.allowed_extensions', []));

        if (! in_array($extension, $allowed, true)) {
            throw new KnowledgeDocumentIntakeException('unsupported_type', 'The file type is not allowed.');
        }

        foreach ($parts as $part) {
            if (in_array(strtolower($part), self::DENIED_NAME_PARTS, true)) {
                throw new KnowledgeDocumentIntakeException('unsupported_type', 'The file type is not allowed.');
            }
        }

        return $extension;
    }

    private function assertContents(string $extension, string $contents, string $realPath): string
    {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: '';

        if (in_array($mimeType, ['application/x-php', 'text/x-php', 'application/x-httpd-php'], true)) {
            throw new KnowledgeDocumentIntakeException('invalid_content', 'The file content is not allowed.');
        }

        if (in_array($extension, ['txt', 'md', 'csv', 'html', 'htm'], true)) {
            $this->assertTextContents($extension, $mimeType, $contents);

            return $mimeType;
        }

        if ($extension === 'pdf') {
            if ($mimeType !== 'application/pdf' || ! str_starts_with($contents, '%PDF-')) {
                throw new KnowledgeDocumentIntakeException('invalid_content', 'The file is not a PDF document.');
            }

            return $mimeType;
        }

        if ($extension === 'docx') {
            $this->assertDocx($mimeType, $realPath);

            return $mimeType;
        }

        throw new KnowledgeDocumentIntakeException('unsupported_type', 'The file type is not allowed.');
    }

    private function assertTextContents(string $extension, string $mimeType, string $contents): void
    {
        $allowedMimes = match ($extension) {
            'html', 'htm' => ['text/html', 'text/plain'],
            'csv' => ['text/plain', 'text/csv', 'application/csv', 'text/comma-separated-values'],
            'md' => ['text/plain', 'text/markdown', 'text/x-markdown'],
            default => ['text/plain'],
        };

        if (! in_array($mimeType, $allowedMimes, true)) {
            throw new KnowledgeDocumentIntakeException('invalid_content', 'The file content does not match its extension.');
        }

        if (str_contains($contents, "\0") || preg_match('/<\?(php|=)/i', $contents) === 1) {
            throw new KnowledgeDocumentIntakeException('invalid_content', 'The file content is not allowed.');
        }
    }

    private function assertDocx(string $mimeType, string $realPath): void
    {
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
        ];

        if (! in_array($mimeType, $allowedMimes, true)) {
            throw new KnowledgeDocumentIntakeException('invalid_content', 'The file is not a Word document.');
        }

        $zip = new ZipArchive;

        if ($zip->open($realPath) !== true) {
            throw new KnowledgeDocumentIntakeException('invalid_content', 'The file is not a Word document.');
        }

        $hasTypes = $zip->locateName('[Content_Types].xml') !== false;
        $hasDocument = $zip->locateName('word/document.xml') !== false;
        $zip->close();

        if (! $hasTypes || ! $hasDocument) {
            throw new KnowledgeDocumentIntakeException('invalid_content', 'The file is not a Word document.');
        }
    }

    private function storageDirectory(): string
    {
        $directory = (string) config('ai-knowledge.directory', 'ai-knowledge');

        if (! preg_match('/^[a-z0-9-]+$/', $directory)) {
            throw new KnowledgeDocumentIntakeException('unsafe_path', 'The knowledge storage directory is not allowed.');
        }

        return $directory;
    }

    private function isManagedStoragePath(string $path): bool
    {
        $directory = preg_quote($this->storageDirectory(), '/');

        return preg_match('/^'.$directory.'\/[0-9a-fA-F-]{36}\.(pdf|txt|md|csv|html|htm|docx)$/', $path) === 1;
    }
}
