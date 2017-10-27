<?php return [
    'plugin'         => [
        'name'        => 'Omnipay for Shopaholic',
        'description' => 'Integration with package omnipay',
    ],
    'tab'            => [
        'gateway' => 'Payment gateway',
    ],
    'message'        => [],
    'field' => [
        'gateway_id' => 'Payment gateway',
        'gateway_currency' => 'Gateway currency',
        'payment_page' => 'Payment page',
        'before_status_id' => 'Order status before payment',
        'after_status_id' => 'Order status after payment',
    ],
];