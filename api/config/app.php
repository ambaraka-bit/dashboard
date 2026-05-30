<?php
// ============================================================
//  config/app.php
// ============================================================
return [
    // Allowed origins for CORS (* = any)
    'cors_origin'  => '*',

    // Cache lifetime in seconds (0 = disabled)
    'cache_ttl'    => 60,

    // Default row limit for list endpoints
    'default_limit' => 10,
    'max_limit'     => 100,

    // App timezone
    'timezone'     => 'Asia/Kolkata',
];
