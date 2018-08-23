<?php

return [
    'solr' => [
        'host' => env('SOLR_HOST', 'localhost'),
        'port' => intval(env('SOLR_PORT', 8983)),
        'path' => env('SOLR_PATH', '/solr/'),
        'core' => env('SOLR_CORE', 'default'),
    ]
];
