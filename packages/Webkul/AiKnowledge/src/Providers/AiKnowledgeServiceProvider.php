<?php

namespace Webkul\AiKnowledge\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\AiKnowledge\Services\KnowledgeChunkingService;
use Webkul\AiKnowledge\Services\KnowledgeDocumentIntake;
use Webkul\AiKnowledge\Services\KnowledgeRetrievalService;

class AiKnowledgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/ai-knowledge.php', 'ai-knowledge');

        $this->app->singleton(KnowledgeDocumentIntake::class);
        $this->app->singleton(KnowledgeChunkingService::class);
        $this->app->singleton(KnowledgeRetrievalService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
