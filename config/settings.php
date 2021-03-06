<?php
return [
    'system_party_id' => 1,
    'defaults' => [
        'currency' => 'PHP',
        'currency_id' => 113,
        'currency_symbol' => '₱',
        'shipping_type' => 'land',
        'contract' => [
            'shipping_fee' => [
                'manila' => 100,
                'provincial' => 150
            ],
            'insurance_fee' => [
                'type' => 'percent',
                'value' => 0.01,
                'max' => 5
            ],
            'transaction_fee' => [
                'type' => 'percent',
                'value' => 0.03,
                'max' => 20
            ],
            'pickup_retries' => 3,
            // Within 7 days of delivery.
            'claim_period' => 7,
            // If set to true, F3 creates and unpaid charge object even for prepaid orders.
            // Otherwise, F3 creates a charge object for COD orders only.
            'fuse_client' => false,
            'disbursal' => [
                // http://php.net/manual/en/function.date.php
                // 0 - Sun.
                // 6 - Sat.
                'every' => [
                    // Day of week to process the disbursement.
                    'day' => 1,
                ],
                'period' => [
                    // Period covered.
                    'from' => 1,
                    'to' => 0
                ]
            ],
        ],
        'tracker' => [
            'failures' => 0,
            'last_error' => [],
            'callback_url' => null,
            'status_updated_at' => null
        ],
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'max_limit' => null,
                'credit_limit' => 0
            ],
            'settlement' => [
                'currency' => 'PHP',
                'max_limit' => null,
                'credit_limit' => 0
            ]
        ]
    ],
    'awb' => [
        'template' => 'https://s3-us-west-1.amazonaws.com/assets.lbcx.ph/awb/awb.htm'
    ],
    'jwt' => [
        // Maximum time() and iat difference in seconds.
        'max_iat' => 10,

        // Default algorithm and token type.
        'alg' => 'HS256',
        'typ' => 'JWT'
    ],
    'cors' => [
        'allowed_origins' => []
    ],
    'cache' => [
        // In minutes.
        'expires_in' => 1440
    ],
    'couriers' => [
        'lbc' => [
            'product_id' => 4,
            'area_codes' => [
                1 => 'Metro Manila',
                2 => 'North Luzon',
                3 => 'South Luzon',
                4 => 'Visayas',
                5 => 'Mindanao',
            ],
        ]
    ],
    // List of order statuses and their display names.
    'order_statuses' => [
        'pending' => 'Pending',
        'for_pickup' => 'Ready for pickup',
        'picked_up' => 'Picked up',
        'failed_pickup' => 'Failed pickup',
        'failed_delivery' => 'Failed delivery',
        'in_transit' => 'In tansit',
        'claimed' => 'Claimed',
        'delivered' => 'Delivered',
        'return_in_transit' => 'Returned - in transit',
        'returned' => 'Returned',
        'failed_return' => 'Failed return',
        'out_for_delivery' => 'Out for delivery',
        'confirmed' => 'Confirmed',
        'canceled' => 'Canceled',
    ],
    // List of transaction matching statuses.
    'match_statuses' => [
        'match' => 'Matched',
        'over_remit' => 'Over Remit',
        'under_remit' => 'Under Remit'
    ],
    // List of claim statuses and their display names.
    'claim_statuses' => [
        'pending' => 'Pending',
        'verified' => 'Verfied',
        'settled' => 'Settled',
        'declined' => 'Declined'
    ],
    // List of ledger entry statuses.
    'ledger_entry_statuses' => [
        'pending' => 'Pending',
        'settled' => 'Settled'
    ],
    // List of ledger entry types.
    'ledger_entry_types' => [
        'payable' => 'Payable',
        'receivable' => 'Receivable'
    ],
    // List of payment methods and their display names.
    'payment_methods' => [
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Cart',
        'otc' => 'Bank Deposit / OTC',
        'cod' => 'Cash on Delivery'
    ],
    // List of payment providers and their display names.
    'payment_providers' => [
        'asiapay' => 'Asiapay',
        'dragonpay' => 'Dragonpay',
        'lbc' => 'LBC Express',
        'lbcx' => 'LBCX'
    ],
    // List of local delivery areas.
    'local_areas' => ['Manila', 'Metro Manila', 'NCR', 'National Capital Region'],
    // DB - library barcode format mapping.
    'barcode_formats' => [
        'code_128' => 'C128',
        'qr' => 'QRCODE',
    ],
    'pdf' => array(
        'enabled' => true,
        'binary'  => '/usr/local/bin/wkhtmltopdf --print-media-type --lowquality --margin-top 3mm --margin-right 0mm --margin-bottom 0mm --margin-left 3mm',
        'timeout' => false,
        //'options' => array('margin-top'=> '3mm', 'margin-right'=> '0mm', 'margin-bottom'=> '0mm', 'margin-left'=> '3mm'),
        'env'     => array(),
    ),
    'image' => array(
        'enabled' => true,
        'binary'  => '/usr/local/bin/wkhtmltoimage',
        'timeout' => false,
        'options' => array(),
        'env'     => array(),
    ),
];
