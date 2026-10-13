<?php

namespace Webkul\MarketingAutomation\Services;

class MarketingAutomationRunner
{
    public function __construct(
        private WorkflowEngine $workflows,
        private SequenceRunner $sequences,
    ) {}

    /**
     * @return array{workflows: int, emails: int}
     */
    public function run(): array
    {
        return [
            'workflows' => $this->workflows->processDue() + app(CoreWorkflowActions::class)->processDue(),
            'emails' => $this->sequences->processDue(),
        ];
    }
}
