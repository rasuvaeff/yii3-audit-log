<?php

declare(strict_types=1);

return [
    'rasuvaeff/yii3-audit-log' => [
        'sensitiveKeys' => [
            'password',
            'secret',
            'token',
            'api_key',
            'credit_card',
        ],
        'skipEmptyChangeSets' => true,
    ],
];
