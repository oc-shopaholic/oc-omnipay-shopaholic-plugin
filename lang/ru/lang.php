<?php return [
    'plugin'         => [
        'name'        => 'Omnipay for Shopaholic',
        'description' => 'Интеграция пакета omnipay',
    ],
    'tab'            => [
        'gateway' => 'Платежная система',
    ],
    'field' => [
        'gateway_id'       => 'Платежная система',
        'gateway_currency' => 'Валюта платежной системы',
        'before_status_id' => 'Статус заказа до оплаты',
        'after_status_id'  => 'Статус заказа после оплаты',
    ],
];