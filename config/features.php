<?php

return [
    'leads' => [
        'label' => 'Leads',
        'default_enabled' => true,
        'route_prefixes' => ['admin.leads.'],
        'menu_keys' => ['leads', 'settings.lead', 'settings.lead.pipelines', 'settings.lead.sources', 'settings.lead.types'],
    ],
    'contacts' => [
        'label' => 'Contacts',
        'default_enabled' => true,
        'route_prefixes' => ['admin.contacts.persons.'],
        'menu_keys' => ['contacts.persons'],
        'menu_any_keys' => ['contacts'],
        'menu_any_routes' => ['contacts' => 'admin.contacts.persons.index'],
    ],
    'organizations' => [
        'label' => 'Organizations',
        'default_enabled' => true,
        'route_prefixes' => ['admin.contacts.organizations.'],
        'menu_keys' => ['contacts.organizations'],
        'menu_any_keys' => ['contacts'],
        'menu_any_routes' => ['contacts' => 'admin.contacts.organizations.index'],
    ],
    'products' => [
        'label' => 'Products',
        'default_enabled' => true,
        'route_prefixes' => ['admin.products.'],
        'menu_keys' => ['products'],
    ],
    'quotes' => [
        'label' => 'Quotes',
        'default_enabled' => true,
        'route_prefixes' => ['admin.quotes.', 'admin.leads.quotes.'],
        'menu_keys' => ['quotes'],
    ],
    'webforms' => [
        'label' => 'Web Forms',
        'default_enabled' => true,
        'route_prefixes' => ['admin.settings.web_forms.'],
        'menu_keys' => ['settings.other_settings.web_forms'],
    ],
    'marketing' => [
        'label' => 'Marketing',
        'default_enabled' => true,
        'route_prefixes' => ['admin.settings.marketing.'],
        'menu_keys' => ['settings.automation.events', 'settings.automation.campaigns'],
        'menu_any_keys' => ['settings.automation'],
        'menu_any_routes' => ['settings.automation' => 'admin.settings.marketing.events.index'],
    ],
    'automation' => [
        'label' => 'Automation',
        'default_enabled' => true,
        'route_prefixes' => [
            'admin.settings.attributes.',
            'admin.settings.email_templates.',
            'admin.settings.workflows.',
            'admin.settings.webhooks.',
        ],
        'menu_keys' => [
            'settings.automation.attributes',
            'settings.automation.email_templates',
            'settings.automation.webhooks',
            'settings.automation.workflows',
        ],
        'menu_any_keys' => ['settings.automation'],
        'menu_any_routes' => ['settings.automation' => 'admin.settings.attributes.index'],
    ],
];
