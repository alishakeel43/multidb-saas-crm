<?php

namespace Webkul\AiKnowledge\Services;

use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Webkul\AiKnowledge\Models\KnowledgeChunk;
use Webkul\AiKnowledge\Models\KnowledgeDocument;

class KnowledgeChunkingService
{
    public function indexDocument(KnowledgeDocument $document): int
    {
        $text = $this->extractText($document);

        if (trim($text) === '') {
            $document->update(['status' => KnowledgeDocument::STATUS_FAILED]);

            return 0;
        }

        $chunks = $this->chunkText($text);

        KnowledgeChunk::query()->where('document_id', $document->getKey())->delete();

        foreach ($chunks as $index => $chunk) {
            KnowledgeChunk::query()->create([
                'document_id' => $document->getKey(),
                'chunk_index' => $index,
                'content' => $chunk,
                'content_hash' => hash('sha256', $chunk),
                'metadata' => [
                    'document_id' => $document->getKey(),
                    'source' => $document->original_filename,
                ],
            ]);
        }

        $document->update(['status' => KnowledgeDocument::STATUS_READY]);

        return count($chunks);
    }

    public function extractText(KnowledgeDocument $document): string
    {
        $disk = (string) config('ai-knowledge.disk', 'local');
        $storagePath = $document->storage_path;

        if ($storagePath === '' || ! Storage::disk($disk)->exists($storagePath)) {
            return '';
        }

        $extension = strtolower((string) $document->extension);
        $path = Storage::disk($disk)->path($storagePath);

        return match ($extension) {
            'pdf' => $this->extractPdfText($path),
            'docx' => $this->extractDocxText($path),
            'txt', 'md', 'csv', 'html', 'htm' => $this->normalizeText((string) file_get_contents($path)),
            default => '',
        };
    }

    protected function extractPdfText(string $path): string
    {
        if (! file_exists($path)) {
            return '';
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);

            return $this->normalizeText((string) $pdf->getText());
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function extractDocxText(string $path): string
    {
        if (! class_exists('ZipArchive') || ! file_exists($path)) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            return '';
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();

        $text = $dom->textContent ?? '';

        return $this->normalizeText($text);
    }

    protected function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<[^>]+>/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    protected function chunkText(string $text, int $size = 250, int $overlap = 50): array
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text));

        if (! is_string($normalized) || trim($normalized) === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || count($words) === 0) {
            return [];
        }

        if (count($words) <= $size && count($words) > 1) {
            $midpoint = (int) floor(count($words) / 2);
            $firstWords = array_slice($words, 0, $midpoint);
            $secondWords = array_slice($words, max(0, $midpoint - $overlap));

            $chunks = [
                implode(' ', $firstWords),
                implode(' ', $secondWords),
            ];

            return array_values(array_filter($chunks, fn ($chunk) => trim($chunk) !== ''));
        }

        $step = max(1, $size - $overlap);
        $chunks = [];

        for ($i = 0; $i < count($words); $i += $step) {
            $slice = array_slice($words, $i, $size);

            if (empty($slice)) {
                break;
            }

            $chunks[] = implode(' ', $slice);

            if ($i + $size >= count($words)) {
                break;
            }
        }

        return array_values(array_filter($chunks, fn ($chunk) => trim($chunk) !== ''));
    }
}
