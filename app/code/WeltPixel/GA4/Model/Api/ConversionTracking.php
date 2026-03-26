<?php
namespace WeltPixel\GA4\Model\Api;

/**
 * Class \WeltPixel\GA4\Model\Api\ConversionTracking
 */
class ConversionTracking extends \WeltPixel\GA4\Model\Api
{

    /**
     * Variable names
     */
    const VARIABLE_CONVERSION_TRACKING_CONVERSION_VALUE = 'WP - Conversion Value';
    const VARIABLE_CONVERSION_TRACKING_ORDER_ID = 'WP - Order ID';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_EMAIL = 'WP - GA4 - EC Customer Email';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_PHONE = 'WP - GA4 - EC Customer Phone';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME = 'WP - GA4 - EC Customer First Name';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_LASTNAME = 'WP - GA4 - EC Customer Last Name';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_STREET = 'WP - GA4 - EC Customer Street';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_CITY = 'WP - GA4 - EC Customer City';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_REGION = 'WP - GA4 - EC Customer Region';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_COUNTRY = 'WP - GA4 - EC Customer Country';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_POSTALCODE = 'WP - GA4 - EC Customer Postal Code';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_USER_PROVIDED_DATA = 'WP - GA4 - User Provided Data';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_NEW_CUSTOMER = 'WP - New Customer';
    const VARIABLE_CONVERSION_TRACKING_CUSTOMER_LIFETIME_VALUE = 'WP - Customer Lifetime Value';
    const VARIABLE_CONVERSION_TRACKING_CART_DATA_DISCOUNT = 'WP - GA4 - Cart Data Discount';
    const VARIABLE_CONVERSION_TRACKING_CART_DATA_ITEMS = 'WP - GA4 - Cart Data Items';

    /**
     * Trigger names
     */
    const TRIGGER_CONVERSION_TRACKING_MAGENTO_CHECKOUT_SUCCESS_PAGE = 'WP - Magento Checkout Success Page';

    const TRIGGER_CONVERSION_TRACKING_MAGENTO_CHECKOUT_ADS_PURCHASE = 'WP - Ads Purchase';

    /**
     * Tag names
     */
    const TAG_CONVERSION_TRACKING_ADWORDS_CONVERSION_TRACKING = 'WP - Google Ads Conversion Tracking';
    const TAG_CONVERSION_TRACKING_ADWORDS_USER_PROVIDED_DATA_EVENT = 'WP - Google Ads User-provided Data Event';

    /**
     * Field names used in sending data to dataLayer
     */
    const FIELD_CONVERSION_TRACKING_CONVERSION_VALUE = 'wp_conversion_value';
    const FIELD_CONVERSION_TRACKING_ORDER_ID = 'wp_order_id';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_EMAIL = 'customerEmail';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_PHONE = 'customerPhone';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME = 'customerFirstname';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_LASTNAME = 'customerLastname';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_STREET = 'customerStreet';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_CITY = 'customerCity';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_REGION = 'customerRegion';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_COUNTRY = 'customerCountry';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_POSTALCODE = 'customerPostalcode';
    const FIELD_CONVERSION_TRACKING_NEW_CUSTOMER = 'new_customer';
    const FIELD_CONVERSION_TRACKING_CUSTOMER_LIFETIME_VALUE = 'customer_lifetime_value';
    const FIELD_CONVERSION_TRACKING_CART_DATA_DISCOUNT = 'discount';
    const FIELD_CONVERSION_TRACKING_CART_DATA_ITEMS = 'items';

    /**
     * Return list of variables for conversion tracking
     * @return array
     */
    private function _getConversionVariables()
    {
        $variables = [
            self::VARIABLE_CONVERSION_TRACKING_CONVERSION_VALUE => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CONVERSION_VALUE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CONVERSION_VALUE
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_ORDER_ID => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_ORDER_ID,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_ORDER_ID
                    ]
                ]
            ]
        ];

        return $variables;
    }

    /**
     * Return list of variables for enhanced conversion tracking
     * @return array
     */
    private function _getEnhancedConversionVariables($params = [])
    {
        $attributes = $params['enhanced_conversion_attributes'] ?? [];
        $filterEnabled = $params['enable_enhanced_conversion_filter'] ?? false;

        if (!$filterEnabled || empty($attributes)) {
            $attributes = \WeltPixel\GA4\Model\Config\Source\EnhancedConversionAttributes::getAttributeKeys();
        }

        $attributeVariableMap = [
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_EMAIL => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_EMAIL,
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_PHONE => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_PHONE,
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME,
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_LASTNAME => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_LASTNAME,
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_STREET => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_STREET,
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_CITY => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_CITY,
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_REGION => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_REGION,
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_COUNTRY => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_COUNTRY,
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_POSTALCODE => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_POSTALCODE
        ];

        $attributeKeyMap = [
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_EMAIL => 'email',
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_PHONE => 'phone_number',
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME => 'first_name',
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_LASTNAME => 'last_name',
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_STREET => 'street',
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_CITY => 'city',
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_REGION => 'region',
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_COUNTRY => 'country',
            \WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_POSTALCODE => 'postal_code'
        ];

        $variables = [
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_EMAIL => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_EMAIL,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_EMAIL
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_PHONE => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_PHONE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_PHONE
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_LASTNAME => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_LASTNAME,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_LASTNAME
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_STREET => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_STREET,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_STREET
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_CITY => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_CITY,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_CITY
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_REGION => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_REGION,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_REGION
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_COUNTRY => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_COUNTRY,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_COUNTRY
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_POSTALCODE => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_POSTALCODE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_POSTALCODE
                    ]
                ]
            ]
        ];

        $userProvidedParams = [
            [
                'type' => 'template',
                'key' => 'mode',
                'value' => "MANUAL"
            ]
        ];

        foreach ($attributes as $attribute) {
            if (!isset($attributeKeyMap[$attribute]) || !isset($attributeVariableMap[$attribute])) {
                continue;
            }

            $userProvidedParams[] = [
                'type' => 'template',
                'key' => $attributeKeyMap[$attribute],
                'value' => '{{' . $attributeVariableMap[$attribute] . '}}'
            ];
        }

        $variables[self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_USER_PROVIDED_DATA] = [
            'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_USER_PROVIDED_DATA,
            'type' => self::TYPE_VARIABLE_AWEC,
            'parameter' => $userProvidedParams
        ];

        return $variables;
    }

    /**
     * @return array
     */
    private function _getConversionCustomerAcquisitionVariables()
    {
        $variables = [
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_NEW_CUSTOMER => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_NEW_CUSTOMER,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_NEW_CUSTOMER
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_LIFETIME_VALUE => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_LIFETIME_VALUE,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CUSTOMER_LIFETIME_VALUE
                    ]
                ]
            ]
        ];

        return $variables;
    }


    /**
     * @return array
     */
    private function _getConversionCartDataVariables()
    {
        $variables = [
            self::VARIABLE_CONVERSION_TRACKING_CART_DATA_DISCOUNT => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CART_DATA_DISCOUNT,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CART_DATA_DISCOUNT
                    ]
                ]
            ],
            self::VARIABLE_CONVERSION_TRACKING_CART_DATA_ITEMS => [
                'name' => self::VARIABLE_CONVERSION_TRACKING_CART_DATA_ITEMS,
                'type' => self::TYPE_VARIABLE_DATALAYER,
                'parameter' => [
                    [
                        'type' => 'integer',
                        'key' => 'dataLayerVersion',
                        'value' => "2"
                    ],
                    [
                        'type' => 'boolean',
                        'key' => 'setDefaultValue',
                        'value' => "false"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'name',
                        'value' => self::FIELD_CONVERSION_TRACKING_CART_DATA_ITEMS
                    ]
                ]
            ]
        ];

        return $variables;
    }

    /**
     * Return list of triggers for conversion tracking
     * @return array
     */
    private function _getConversionTriggers()
    {
        $triggers = [
            self::TRIGGER_CONVERSION_TRACKING_MAGENTO_CHECKOUT_SUCCESS_PAGE => [
                'name' => self::TRIGGER_CONVERSION_TRACKING_MAGENTO_CHECKOUT_SUCCESS_PAGE,
                'type' => self::TYPE_TRIGGER_PAGEVIEW,
                'filter' => [
                    [
                        'type' => 'contains',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{Page URL}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => '/checkout/onepage/success'
                            ]
                        ]
                    ]
                ]
            ],
            self::TRIGGER_CONVERSION_TRACKING_MAGENTO_CHECKOUT_ADS_PURCHASE => [
                'name' => self::TRIGGER_CONVERSION_TRACKING_MAGENTO_CHECKOUT_ADS_PURCHASE,
                'type' => self::TYPE_TRIGGER_CUSTOM_EVENT,
                'customEventFilter' => [
                    [
                        'type' => 'equals',
                        'parameter' => [
                            [
                                'type' => 'template',
                                'key' => 'arg0',
                                'value' => '{{_event}}'
                            ],
                            [
                                'type' => 'template',
                                'key' => 'arg1',
                                'value' => 'ads_purchase'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $triggers;
    }

    /**
     * Return a list of tags for conversion tracking
     * @param array $triggers
     * @param array $params
     * @return array
     */
    private function _getConversionTags($triggers, $params)
    {
        $adwordsConversionTrackingTagParameters = [
            [
                'type' => 'boolean',
                'key' => 'enableConversionLinker',
                'value' => "true"
            ],
            [
                'type' => 'template',
                'key' => 'conversionValue',
                'value' => '{{' . self::VARIABLE_CONVERSION_TRACKING_CONVERSION_VALUE . '}}'
            ],
            [
                'type' => 'template',
                'key' => 'orderId',
                'value' => '{{' . self::VARIABLE_CONVERSION_TRACKING_ORDER_ID . '}}'
            ],
            [
                'type' => 'template',
                'key' => 'conversionId',
                'value' => $params['conversion_id']
            ],
            [
                'type' => 'template',
                'key' => 'currencyCode',
                'value' => $params['conversion_currency_code']
            ],
            [
                'type' => 'template',
                'key' => 'conversionLabel',
                'value' => $params['conversion_label']
            ],
            [
                'type' => 'template',
                'key' => 'conversionCookiePrefix',
                'value' => '_gcl'
            ]
        ];

        if ($params['enable_enhanced_conversion']) {
            array_push($adwordsConversionTrackingTagParameters,
                [
                    'type' => 'boolean',
                    'key' => 'enableEnhancedConversion',
                    'value' => 'true'
                ],
                [
                    'type' => 'template',
                    'key' => 'cssProvidedEnhancedConversionValue',
                    'value' => '{{' . self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_USER_PROVIDED_DATA . '}}'
                ],
                [
                    'type' => 'boolean',
                    'key' => 'enableShippingData',
                    'value' => 'false'
                ]);
        }

        if ($params['enable_customer_acquisition']) {
            array_push($adwordsConversionTrackingTagParameters,
                [
                    'type' => 'template',
                    'key' => 'newCustomerReportingDataSource',
                    'value' => 'JSON'
                ],
                [
                    'type' => 'template',
                    'key' => 'awNewCustomer',
                    'value' =>'{{' . self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_NEW_CUSTOMER . '}}'
                ],
                [
                    'type' => 'template',
                    'key' => 'awCustomerLTV',
                    'value' => '{{' . self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_LIFETIME_VALUE . '}}'
                ],
                [
                    'type' => 'boolean',
                    'key' => 'rdp',
                    'value' => 'false'
                ]);
        }
        if ($params['enable_conversion_cart_data']) {
            array_push($adwordsConversionTrackingTagParameters,
                [
                    'type' => 'template',
                    'key' => 'productReportingDataSource',
                    'value' => 'JSON'
                ],
                [
                    'type' => 'template',
                    'key' => 'awMerchantId',
                    'value' => $params['conversion_cart_merchant_center_id']
                ],
                [
                    'type' => 'template',
                    'key' => 'awFeedCountry',
                    'value' => $params['conversion_cart_feed_country']
                ],
                [
                    'type' => 'template',
                    'key' => 'awFeedLanguage',
                    'value' => $params['conversion_cart_feed_language']
                ],
                [
                    'type' => 'template',
                    'key' => 'discount',
                    'value' => '{{' . self::VARIABLE_CONVERSION_TRACKING_CART_DATA_DISCOUNT . '}}'
                ],
                [
                    'type' => 'template',
                    'key' => 'items',
                    'value' => '{{' . self::VARIABLE_CONVERSION_TRACKING_CART_DATA_ITEMS . '}}'
                ]);
        }

        if ($params['enable_enhanced_conversion'] || $params['enable_customer_acquisition'] || $params['enable_conversion_cart_data']) {
            $enableNewCustomerReporting = 'false';
            if ($params['enable_customer_acquisition'] ||  $params['enable_conversion_cart_data']) {
                $enableNewCustomerReporting = 'true';
            }
            $adwordsConversionTrackingTagParameters[] = [
                'type' => 'boolean',
                'key' => 'enableNewCustomerReporting',
                'value' => $enableNewCustomerReporting
            ];
        }

        if ($params['enable_enhanced_conversion'] || $params['enable_conversion_cart_data']) {
            $enableProductReporting = 'false';
            if ($params['enable_conversion_cart_data']) {
                $enableProductReporting = 'true';
            }

            $adwordsConversionTrackingTagParameters[] = [
                'type' => 'boolean',
                'key' => 'enableProductReporting',
                'value' => $enableProductReporting
            ];
        }

        $conversionTrackingTrigger = self::TRIGGER_CONVERSION_TRACKING_MAGENTO_CHECKOUT_SUCCESS_PAGE;
        if ($params['conversion_separate_datalayer_event']) {
            $conversionTrackingTrigger = self::TRIGGER_CONVERSION_TRACKING_MAGENTO_CHECKOUT_ADS_PURCHASE;
        }

        $tags = [
            self::TAG_CONVERSION_TRACKING_ADWORDS_CONVERSION_TRACKING => [
                'name' => self::TAG_CONVERSION_TRACKING_ADWORDS_CONVERSION_TRACKING,
                'firingTriggerId' => [
                    $triggers[$conversionTrackingTrigger]
                ],
                'type' => self::TYPE_TAG_AWCT,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => $adwordsConversionTrackingTagParameters
            ]
        ];

        if (!empty($params['enable_enhanced_conversion_for_leads'])) {
            $tags[self::TAG_CONVERSION_TRACKING_ADWORDS_USER_PROVIDED_DATA_EVENT] = [
                'name' => self::TAG_CONVERSION_TRACKING_ADWORDS_USER_PROVIDED_DATA_EVENT,
                'firingTriggerId' => [
                    $triggers[$conversionTrackingTrigger]
                ],
                'type' => self::TYPE_TAG_AWUD,
                'tagFiringOption' => 'oncePerEvent',
                'parameter' => [
                    [
                        'type' => 'boolean',
                        'key' => 'enableConversionLinker',
                        'value' => "true"
                    ],
                    [
                        'type' => 'template',
                        'key' => 'userDataVariable',
                        'value' => '{{' . self::VARIABLE_CONVERSION_TRACKING_CUSTOMER_USER_PROVIDED_DATA . '}}'
                    ],
                    [
                        'type' => 'template',
                        'key' => 'conversionId',
                        'value' => $params['conversion_id']
                    ],
                    [
                        'type' => 'template',
                        'key' => 'conversionCookiePrefix',
                        'value' => '_gcl'
                    ]
                ],
                'monitoringMetadata' => [
                    'type' => "MAP"
                ]
            ];
        }

        return $tags;
    }

    /**
     * @return array
     */
    public function getConversionVariablesList()
    {
        return $this->_getConversionVariables();
    }


    /**
     * @return array
     */
    public function getEnhancedConversionVariablesList($params = [])
    {
        return $this->_getEnhancedConversionVariables($params);
    }

    /**
     * @return array
     */
    public function getConversionCustomerAcquisitionVariablesList()
    {
        return $this->_getConversionCustomerAcquisitionVariables();
    }

    /**
     * @return array
     */
    public function getConversionCartDataVariablesList()
    {
        return $this->_getConversionCartDataVariables();
    }

    /**
     * @return array
     */
    public function getConversionTriggersList()
    {
        return $this->_getConversionTriggers();
    }

    /**
     * @param array $triggers
     * @param array $params
     * @return array
     */
    public function getConversionTagsList($triggers, $params)
    {
        return $this->_getConversionTags($triggers, $params);
    }
}
