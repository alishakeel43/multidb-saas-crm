<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeRepository;

/**
 * Adds (or removes) the AI-sales extra fields on Leads.
 *
 * The fields are created through the same repository Settings → Attributes uses,
 * as user-defined attributes. They therefore show up in the lead forms, can be
 * relabelled or deleted from Settings → Attributes and need no schema change.
 */
class LeadAiFieldsCommand extends Command
{
    protected $signature = 'crm:lead-ai-fields
        {--remove : Delete these fields (and their saved values) instead of adding them}
        {--database= : Connection to run on (defaults to the application default)}
        {--force : Accepted for tenant wrapper compatibility}';

    protected $description = 'Add or remove the AI-sales extra fields on Leads using the default CRM attribute process';

    private const ENTITY_TYPE = 'leads';

    public function handle(AttributeRepository $attributes): int
    {
        $connection = $this->option('database');
        $previous = DB::getDefaultConnection();

        if ($connection) {
            DB::setDefaultConnection($connection);
        }

        try {
            return $this->option('remove')
                ? $this->removeFields($attributes)
                : $this->addFields($attributes);
        } finally {
            DB::setDefaultConnection($previous);
        }
    }

    private function addFields(AttributeRepository $attributes): int
    {
        foreach ($this->definitions() as $definition) {
            $exists = $attributes->findOneWhere([
                'code' => $definition['code'],
                'entity_type' => self::ENTITY_TYPE,
            ]);

            if ($exists) {
                $this->line("Skipped  {$definition['code']} (already exists)");

                continue;
            }

            $attributes->create(array_merge([
                'entity_type' => self::ENTITY_TYPE,
                'lookup_type' => null,
                'validation' => null,
                'is_required' => 0,
                'is_unique' => 0,
                'quick_add' => 0,
                'is_user_defined' => 1,
            ], $definition));

            $this->info("Added    {$definition['code']} ({$definition['type']})");
        }

        return self::SUCCESS;
    }

    private function removeFields(AttributeRepository $attributes): int
    {
        foreach ($this->definitions() as $definition) {
            $attribute = $attributes->findOneWhere([
                'code' => $definition['code'],
                'entity_type' => self::ENTITY_TYPE,
            ]);

            if (! $attribute) {
                $this->line("Skipped  {$definition['code']} (not found)");

                continue;
            }

            if (! $attribute->is_user_defined) {
                $this->warn("Skipped  {$definition['code']} (system attribute, cannot be deleted)");

                continue;
            }

            $attributes->delete($attribute->id);

            $this->info("Removed  {$definition['code']}");
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            [
                'code' => 'fit_score',
                'name' => 'Fit Score',
                'type' => 'select',
                'options' => $this->optionList(['A', 'B', 'C']),
            ],
            [
                'code' => 'personalization_notes',
                'name' => 'Personalization Notes',
                'type' => 'textarea',
            ],
            [
                'code' => 'ai_batch_id',
                'name' => 'AI Batch ID',
                'type' => 'text',
            ],
            [
                'code' => 'sequence_stage',
                'name' => 'Sequence Stage',
                'type' => 'text',
            ],
            [
                'code' => 'last_ai_action',
                'name' => 'Last AI Action',
                'type' => 'textarea',
            ],
            [
                'code' => 'intent_classification',
                'name' => 'Intent Classification',
                'type' => 'select',
                'options' => $this->optionList(['Interested', 'Objection', 'Not Now', 'Unsubscribe', 'Other']),
            ],
            [
                'code' => 'ai_confidence',
                'name' => 'AI Confidence (0-100)',
                'type' => 'text',
                'validation' => 'numeric',
            ],
            [
                'code' => 'research_summary',
                'name' => 'Research Summary',
                'type' => 'textarea',
            ],
            [
                'code' => 'recommended_product',
                'name' => 'Recommended Product',
                'type' => 'select',
                'options' => $this->optionList([
                    'ZARIS One',
                    'ZARIS Sales',
                    'ZARIS Realty',
                    'ZARIS Support',
                    'ZARIS AI',
                    'ZARIS Edu',
                ]),
            ],
            [
                'code' => 'next_best_action',
                'name' => 'Next Best Action',
                'type' => 'text',
            ],
            [
                'code' => 'human_approved',
                'name' => 'Human Approved',
                'type' => 'boolean',
            ],
        ];
    }

    /**
     * @param  list<string>  $names
     * @return list<array{name: string}>
     */
    private function optionList(array $names): array
    {
        return array_map(fn (string $name) => ['name' => $name], $names);
    }
}
