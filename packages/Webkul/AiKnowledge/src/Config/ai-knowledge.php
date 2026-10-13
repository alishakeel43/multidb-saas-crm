<?php

return [
    /*
    | Private disk. SwitchTenantStorageTask roots this disk at
    | storage/tenants/{slug}/app while a tenant is current.
    */
    'disk' => 'local',

    /*
    | Directory name only. Slashes and dots are rejected by intake.
    */
    'directory' => 'ai-knowledge',

    'max_bytes' => 10 * 1024 * 1024,

    'chunk_size' => 250,
    'chunk_overlap' => 50,

    'allowed_extensions' => ['pdf', 'txt', 'md', 'csv', 'html', 'htm', 'docx'],
];
