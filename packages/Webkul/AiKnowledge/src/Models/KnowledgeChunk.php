<?php

namespace Webkul\AiKnowledge\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    protected $table = 'ai_knowledge_chunks';

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'content_hash',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'chunk_index' => 'integer',
        'document_id' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    public function getConnectionName(): ?string
    {
        return config('multitenancy.tenant_database_connection_name', 'tenant');
    }
}
