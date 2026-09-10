<?php

return [
    'driver' => env('SMS_DRIVER', 'beem'),

    'beem' => [
        'endpoint' => env('SMS_BEEM_ENDPOINT', 'https://apisms.beem.africa/v1/send'),
    ],

    'defaults' => [
        'cake_point_template' => 'Hello {staff_name}, new order {sale_number} at cake point. Customer: {customer_name}. Items: {items_summary}. Total: {total}.',
    ],
];
