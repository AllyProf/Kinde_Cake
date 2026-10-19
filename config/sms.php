<?php

return [
    'driver' => env('SMS_DRIVER', 'mauzo_link'),
    'enabled' => env('SMS_ENABLED'),

    'mauzo_link' => [
        // POST + Bearer (recommended)
        'api_endpoint' => env('SMS_MAUZO_LINK_API_ENDPOINT', 'https://messaging-service.co.tz/api/sms/v2/text/single'),
        // GET + token query (link API v2)
        'link_endpoint' => env('SMS_MAUZO_LINK_LINK_ENDPOINT', 'https://messaging-service.co.tz/link/sms/v2/text/single'),
        'transport' => env('SMS_MAUZO_LINK_TRANSPORT', 'api'),
        'token' => env('SMS_MAUZO_LINK_TOKEN'),
        'sender_id' => env('SMS_SENDER_ID', 'Kinde Cake'),
    ],

    'beem' => [
        'endpoint' => env('SMS_BEEM_ENDPOINT', 'https://apisms.beem.africa/v1/send'),
    ],

    'defaults' => [
        'cake_point_template' => 'Hello {staff_name}, new order {sale_number} at cake point. Customer: {customer_name}. Items: {items_summary}. Total: {total}.',
    ],
];
