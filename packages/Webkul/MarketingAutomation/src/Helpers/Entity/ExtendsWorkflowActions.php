<?php

namespace Webkul\MarketingAutomation\Helpers\Entity;

use Webkul\MarketingAutomation\Services\CoreWorkflowActions;

trait ExtendsWorkflowActions
{
    public function getActions(): array
    {
        return array_merge(parent::getActions(), CoreWorkflowActions::definitions());
    }

    public function executeActions(mixed $workflow, mixed $entity): void
    {
        app(CoreWorkflowActions::class)->runMatch(
            $workflow,
            $entity,
            fn (array $action) => $this->executeCoreAction($action, $entity),
        );
    }

    public function executeOtherwise(mixed $workflow, mixed $entity): void
    {
        app(CoreWorkflowActions::class)->runElse(
            $workflow,
            $entity,
            fn (array $action) => $this->executeCoreAction($action, $entity),
        );
    }

    public function executeCoreAction(array $action, mixed $entity): void
    {
        parent::executeActions($this->singleActionWorkflow($action), $entity);
    }

    private function singleActionWorkflow(array $action): object
    {
        $workflow = new \stdClass;
        $workflow->actions = [$action];

        return $workflow;
    }
}
