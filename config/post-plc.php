<?php

declare(strict_types=1);

return [
    'default_profile' => env('PLC_PROFILE'),

    'client_id' => env('PLC_CLIENT_ID'),
    'org_unit_id' => env('PLC_ORG_UNIT_ID'),
    'org_unit_guid' => env('PLC_ORG_UNIT_GUID'),
    'identifier' => env('PLC_IDENTIFIER'),
    'sandbox' => env('PLC_SANDBOX'),

    'endpoints' => [
        'production' => env('PLC_ENDPOINT', 'https://plc.post.at/Post.Webservice/ShippingService.svc?wsdl'),
        'sandbox' => env('PLC_SANDBOX_ENDPOINT', 'https://abn-plc.post.at/DataService/Post.Webservice/ShippingService.svc?wsdl'),
    ],

    /*
     * Optional named profiles for multi-store, multi-tenant or multi-contract
     * applications. Profile values override the legacy top-level settings.
     */
    'profiles' => [
        // 'warehouse-at' => [
        //     'client_id' => env('PLC_WAREHOUSE_AT_CLIENT_ID'),
        //     'org_unit_id' => env('PLC_WAREHOUSE_AT_ORG_UNIT_ID'),
        //     'org_unit_guid' => env('PLC_WAREHOUSE_AT_ORG_UNIT_GUID'),
        //     'identifier' => env('PLC_WAREHOUSE_AT_IDENTIFIER', 'Laravel-Post-PLC'),
        //     'sandbox' => env('PLC_WAREHOUSE_AT_SANDBOX', false),
        // ],
    ],

    'capabilities' => [
        'cache' => [
            'enabled' => env('PLC_CAPABILITIES_CACHE', true),
            'ttl' => (int) env('PLC_CAPABILITIES_CACHE_TTL', 21600),
        ],

        /*
         * Merchant policy layered on top of GetAllowedServicesForCountry.
         * Empty enabled_products means: use every product returned by PLC.
         */
        'policy' => [
            'enabled_products' => [],
            'disabled_products' => [],
            'disabled_features' => [
                // '*' => ['006'],
                // '45' => ['054'],
            ],
            'preferred_products' => [
                // 'AT' => '10',
                // 'DE' => '45',
                // '*' => '70',
            ],
            'fallback_product' => null,
        ],
    ],

    /*
     * Country-level customs preflight. Special customs territories can differ from
     * their ISO country, so applications may replace the CustomsRequirementResolver
     * binding when postcode/territory-level precision is required.
     */
    'customs' => [
        'eu_country_codes' => [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR',
            'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO',
            'SE', 'SI', 'SK',
        ],
    ],
];
