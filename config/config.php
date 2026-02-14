<?php

return [
    /*
     * Default paths for generated files.
     */
    'paths' => [
        'models' => app_path('Models'),
        'migrations' => database_path('migrations'),
        'controllers' => app_path('Http/Controllers/Api'),
        'factories' => database_path('factories'),
        'seeders' => database_path('seeders'),
        'services' => app_path('Services'),
    ],

    /*
     * Default namespaces for generated classes.
     */
    'namespaces' => [
        'models' => 'App\\Models',
        'controllers' => 'App\\Http\\Controllers\\Api',
        'services' => 'App\\Services',
    ],
];
