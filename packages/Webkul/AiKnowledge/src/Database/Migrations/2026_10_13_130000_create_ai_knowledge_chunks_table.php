<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->tenantConnection())->create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->char('content_hash', 64)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
            $table->index(['document_id', 'content_hash']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->tenantConnection())->dropIfExists('ai_knowledge_chunks');
    }

    private function tenantConnection(): string
    {
        return (string) config('multitenancy.tenant_database_connection_name', 'tenant');
    }
};
