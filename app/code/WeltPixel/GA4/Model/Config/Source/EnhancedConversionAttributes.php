<?php

namespace WeltPixel\GA4\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use WeltPixel\GA4\Model\Api\ConversionTracking;

class EnhancedConversionAttributes implements ArrayInterface
{
    private const ATTRIBUTES = [
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_EMAIL => 'Customer Email',
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_PHONE => 'Customer Phone',
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME => 'Customer First Name',
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_LASTNAME => 'Customer Last Name',
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_STREET => 'Customer Street',
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_CITY => 'Customer City',
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_REGION => 'Customer Region',
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_COUNTRY => 'Customer Country',
        ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_POSTALCODE => 'Customer Postal Code',
    ];

    /**
     * @return array
     */
    public static function getAttributeKeys()
    {
        return array_keys(self::ATTRIBUTES);
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $options = [];

        foreach (self::ATTRIBUTES as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => __($label)
            ];
        }

        return $options;
    }
}


