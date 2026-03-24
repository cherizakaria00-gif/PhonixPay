<?php

return [
    'enabled' => (bool) env('STROWALLET_ENABLED', false),
    'base_url' => rtrim((string) env('STROWALLET_BASE_URL', ''), '/'),
    'secret_key' => env('STROWALLET_SECRET_KEY'),
    'public_key' => env('STROWALLET_PUBLIC_KEY'),
    'developer_code' => env('STROWALLET_DEVELOPER_CODE', 'appdevsx'),
    'mode' => env('STROWALLET_MODE', 'sandbox'),
    'webhook_secret' => env('STROWALLET_WEBHOOK_SECRET'),
    'timeout' => (int) env('STROWALLET_TIMEOUT', 30),
    'currency' => env('STROWALLET_DEFAULT_CURRENCY', 'USD'),
    'endpoints' => [
        'create_customer' => env('STROWALLET_ENDPOINT_CREATE_CUSTOMER', '/create-customer'),
        'get_customer' => env('STROWALLET_ENDPOINT_GET_CUSTOMER', '/get-customer'),
        'update_customer' => env('STROWALLET_ENDPOINT_UPDATE_CUSTOMER', '/update-customer'),
        'create_card' => env('STROWALLET_ENDPOINT_CREATE_CARD', '/create-card'),
        'fund_card' => env('STROWALLET_ENDPOINT_FUND_CARD', '/fund-card'),
        'card_details' => env('STROWALLET_ENDPOINT_CARD_DETAILS', '/card-details'),
        'card_transactions' => env('STROWALLET_ENDPOINT_CARD_TRANSACTIONS', '/full-card-history'),
        'full_card_history' => env('STROWALLET_ENDPOINT_FULL_CARD_HISTORY', '/full-card-history'),
        'freeze_unfreeze' => env('STROWALLET_ENDPOINT_FREEZE_UNFREEZE', '/freezeunfreeze'),
        'withdraw_from_card' => env('STROWALLET_ENDPOINT_WITHDRAW_FROM_CARD', '/card_withdraw'),
        'card_withdraw_status' => env('STROWALLET_ENDPOINT_CARD_WITHDRAW_STATUS', '/getcard_withdrawstatus'),
        'upgrade_card_limit' => env('STROWALLET_ENDPOINT_UPGRADE_CARD_LIMIT', '/upgrade-card-limit'),
        'mastercard_details' => env('STROWALLET_ENDPOINT_MASTERCARD_DETAILS', '/get-mastercard-details'),
    ],
];
