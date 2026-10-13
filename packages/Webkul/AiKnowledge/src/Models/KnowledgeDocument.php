<?php

namespace Webkul\AiKnowledge\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\AiKnowledge\Contracts\KnowledgeDocument as KnowledgeDocumentContract;

class KnowledgeDocument extends Model implements KnowledgeDocumentContract
{
    public const STATUS_PENDING = 'pending';

    protected $table = 'ai_knowledge_documents';

    protected $fillable = [
        'original_filename',
        'storage_path',
        'mime_type',
        'extension',
        'size_bytes',
        'sha256',
        'status',
        'uploaded_by',
    ];

    /**
     * Knowledge rows follow the switched tenant connection, never landlord.
     */
    public function getConnectionName(): ?string
    {
        return config('multitenancy.tenant_database_connection_name', 'tenant');
    }

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'uploaded_by' => 'integer',
        ];
    }
}
