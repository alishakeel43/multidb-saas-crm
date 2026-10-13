<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant database only. The connection name is the switched tenant
     * connection, not the landlord database.
     */
    public function up(): void
    {
        Schema::connection($this->tenantConnection())->create('ai_knowledge_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('original_filename');
            $table->string('storage_path')->unique();
            $table->string('mime_type');
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->tenantConnection())->dropIfExists('ai_knowledge_documents');
    }

    private function tenantConnection(): string
    {
        return (string) config('multitenancy.tenant_database_connection_name', 'tenant');
    }
};
