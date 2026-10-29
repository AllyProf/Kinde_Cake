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
        'sale_confirmation_template' => 'Hello {customer_name}, your order {sale_number} at {business_name} is confirmed. Items: {items_summary}. Total: {total}.{ready_at_line} Thank you!',
        'cake_ready_template' => 'Hello {customer_name}, your cake order {sale_number} at {business_name} is ready for collection.{ready_at_line} Thank you!',
        'birthday_wish_template' => 'Happy Birthday {customer_name}! Wishing you a sweet day from {business_name}.',
        'birthday_reminder_template' => 'Hello {customer_name}, birthday on {birthday_date} is coming soon. Order a cake from {business_name}. Call us!',
        'birthday_reminder_days' => 7,
        'day_close_owner_template' => '{business_name} day close {business_date}: Collected {total_collected}, Expenses {expenses_total}, Net {net_in_hand}. Cash {cash_collected}, Mobile {mobile_collected}. Sales {sales_count}.',
        'pickup_reminder_days' => 1,
        'pickup_reminder_template' => 'Hello {customer_name}, reminder from {business_name}: your cake order {sale_number} is due for collection on {ready_at}. Items: {items_summary}. Balance: {balance_due}. Call {business_phone}.',
        'low_stock_owner_template' => '{business_name} stock alert: {low_stock_count} ingredient(s) need attention. {low_stock_list}',
    ],
];
