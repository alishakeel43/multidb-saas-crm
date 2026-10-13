<?php

namespace Webkul\MarketingAutomation\Helpers\Entity;

use Webkul\Automation\Helpers\Entity\Lead as AutomationLead;

class Lead extends AutomationLead
{
    use ExtendsWorkflowActions;
}
