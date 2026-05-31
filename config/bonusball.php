<?php

return [
    'base_url' => env('BONUSBALL_BASE_URL', 'http://bonusball-api'),
    'token' => env('BONUSBALL_TOKEN'),
    'timeout' => (int) env('BONUSBALL_TIMEOUT', 10),
    'queue' => env('BONUSBALL_QUEUE', 'default'),
];
