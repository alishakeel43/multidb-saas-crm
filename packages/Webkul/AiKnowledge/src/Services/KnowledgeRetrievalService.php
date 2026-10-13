<?php

namespace Webkul\AiKnowledge\Services;

use Webkul\AiKnowledge\Models\KnowledgeChunk;

class KnowledgeRetrievalService
{
    /**
     * @return array<int, array{content: string, score: float, document_id: int|null}>
     */
    public function search(string $query, int $limit = 5): array
    {
        $query = trim((string) $query);

        if ($query === '') {
            return [];
        }

        $terms = array_values(array_filter(preg_split('/\s+/u', strtolower($query) ?: '') ?: [], fn ($term) => $term !== ''));

        if ($terms === []) {
            return [];
        }

        $chunks = KnowledgeChunk::query()
            ->with('document:id,original_filename')
            ->get();

        $scored = $chunks->map(function (KnowledgeChunk $chunk) use ($terms) {
            $content = strtolower((string) $chunk->content);
            $score = 0.0;

            foreach ($terms as $term) {
                if ($term === '') {
                    continue;
                }

                $score += substr_count($content, $term) * 1.5;
                if (str_contains($content, $term)) {
                    $score += 1.0;
                }
            }

            return [
                'content' => $chunk->content,
                'score' => $score,
                'document_id' => $chunk->document_id,
                'document_name' => $chunk->document?->original_filename,
            ];
        })->filter(fn ($item) => (float) $item['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();

        return array_map(function (array $item) {
            unset($item['document_name']);

            return $item;
        }, $scored);
    }
}
