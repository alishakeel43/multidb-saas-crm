<?php

use Webkul\Admin\Providers\ModuleServiceProvider as AdminModuleServiceProvider;
use Webkul\AiKnowledge\Providers\ModuleServiceProvider as AiKnowledgeModuleServiceProvider;
use Webkul\Assignment\Providers\ModuleServiceProvider as AssignmentModuleServiceProvider;
use Webkul\Attribute\Providers\ModuleServiceProvider as AttributeModuleServiceProvider;
use Webkul\Automation\Providers\ModuleServiceProvider as AutomationModuleServiceProvider;
use Webkul\Booking\Providers\ModuleServiceProvider as BookingModuleServiceProvider;
use Webkul\Consent\Providers\ModuleServiceProvider as ConsentModuleServiceProvider;
use Webkul\Contact\Providers\ModuleServiceProvider as ContactModuleServiceProvider;
use Webkul\Core\Providers\ModuleServiceProvider as CoreModuleServiceProvider;
use Webkul\DataGrid\Providers\ModuleServiceProvider as DataGridModuleServiceProvider;
use Webkul\DataTransfer\Providers\ModuleServiceProvider as DataTransferModuleServiceProvider;
use Webkul\Email\Providers\ModuleServiceProvider as EmailModuleServiceProvider;
use Webkul\EmailTemplate\Providers\ModuleServiceProvider as EmailTemplateModuleServiceProvider;
use Webkul\Lead\Providers\ModuleServiceProvider as LeadModuleServiceProvider;
use Webkul\LeadScoring\Providers\ModuleServiceProvider as LeadScoringModuleServiceProvider;
use Webkul\MarketingAutomation\Providers\ModuleServiceProvider as MarketingAutomationModuleServiceProvider;
use Webkul\Notification\Providers\ModuleServiceProvider as NotificationModuleServiceProvider;
use Webkul\Product\Providers\ModuleServiceProvider as ProductModuleServiceProvider;
use Webkul\Quote\Providers\ModuleServiceProvider as QuoteModuleServiceProvider;
use Webkul\QuoteApproval\Providers\ModuleServiceProvider as QuoteApprovalModuleServiceProvider;
use Webkul\Tag\Providers\ModuleServiceProvider as TagModuleServiceProvider;
use Webkul\Task\Providers\ModuleServiceProvider as TaskModuleServiceProvider;
use Webkul\UnifiedInbox\Providers\ModuleServiceProvider as UnifiedInboxModuleServiceProvider;
use Webkul\User\Providers\ModuleServiceProvider as UserModuleServiceProvider;
use Webkul\Warehouse\Providers\ModuleServiceProvider as WarehouseModuleServiceProvider;
use Webkul\WebForm\Providers\ModuleServiceProvider as WebFormModuleServiceProvider;

return [
    'modules' => [
        DataTransferModuleServiceProvider::class,
        AdminModuleServiceProvider::class,
        AiKnowledgeModuleServiceProvider::class,
        AssignmentModuleServiceProvider::class,
        AttributeModuleServiceProvider::class,
        AutomationModuleServiceProvider::class,
        BookingModuleServiceProvider::class,
        ConsentModuleServiceProvider::class,
        ContactModuleServiceProvider::class,
        CoreModuleServiceProvider::class,
        DataGridModuleServiceProvider::class,
        EmailTemplateModuleServiceProvider::class,
        EmailModuleServiceProvider::class,
        LeadModuleServiceProvider::class,
        MarketingAutomationModuleServiceProvider::class,
        LeadScoringModuleServiceProvider::class,
        NotificationModuleServiceProvider::class,
        ProductModuleServiceProvider::class,
        QuoteModuleServiceProvider::class,
        QuoteApprovalModuleServiceProvider::class,
        TagModuleServiceProvider::class,
        TaskModuleServiceProvider::class,
        UserModuleServiceProvider::class,
        WarehouseModuleServiceProvider::class,
        WebFormModuleServiceProvider::class,
        UnifiedInboxModuleServiceProvider::class,
    ],

    'register_route_models' => true,
];
