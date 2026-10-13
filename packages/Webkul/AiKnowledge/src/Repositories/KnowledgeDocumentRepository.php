<?php

namespace Webkul\AiKnowledge\Repositories;

use Webkul\Core\Eloquent\Repository;

class KnowledgeDocumentRepository extends Repository
{
    protected $fieldSearchable = [
        'original_filename',
        'storage_path',
        'mime_type',
        'extension',
        'sha256',
        'status',
        'uploaded_by',
    ];

    public function model(): string
    {
        return 'Webkul\AiKnowledge\Contracts\KnowledgeDocument';
    }
}
