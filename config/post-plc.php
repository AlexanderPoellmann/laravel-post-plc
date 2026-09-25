<?php

declare(strict_types=1);

return [
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
