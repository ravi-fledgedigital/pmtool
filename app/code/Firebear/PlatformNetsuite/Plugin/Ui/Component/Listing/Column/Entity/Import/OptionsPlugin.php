<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\PlatformNetsuite\Plugin\Ui\Component\Listing\Column\Entity\Import;

/**
 * Class OptionsPlugin
 * @package Firebear\PlatformNetsuite\Plugin\Ui\Component\Listing\Column\Entity\Import
 */
class OptionsPlugin
{
    const COLUMN_ADDRESS_FIRSTNAME = '_address_firstname';

    const COLUMN_ADDRESS_LASTNAME = '_address_lastname';

    const COLUMN_ADDRESS_MIDDLENAME = '_address_middlename';

    const COLUMN_ADDRESS_CITY = '_address_city';

    const COLUMN_ADDRESS_COUNTRY_ID = '_address_country_id';

    const COLUMN_ADDRESS_STREET = '_address_street';

    const COLUMN_ADDRESS_POSTCODE = '_address_postcode';

    const COLUMN_ADDRESS_REGION = '_address_region';

    const COLUMN_ADDRESS_TELEPHONE = '_address_telephone';

    const COLUMN_ADDRESS_NETSUITE_INTERNAL_ID = '_address_netsuite_internal_id';

    const COLUMN_ADDRESS_INCREMENT_ID = '_address_increment_id';

    /**
     * Custom attributes
     *
     * @var string[]
     */
    private $customAttributes = [
        self::COLUMN_ADDRESS_FIRSTNAME,
        self::COLUMN_ADDRESS_LASTNAME,
        self::COLUMN_ADDRESS_MIDDLENAME,
        self::COLUMN_ADDRESS_CITY,
        self::COLUMN_ADDRESS_COUNTRY_ID,
        self::COLUMN_ADDRESS_STREET,
        self::COLUMN_ADDRESS_POSTCODE,
        self::COLUMN_ADDRESS_REGION,
        self::COLUMN_ADDRESS_TELEPHONE,
        self::COLUMN_ADDRESS_NETSUITE_INTERNAL_ID,
        self::COLUMN_ADDRESS_INCREMENT_ID,
    ];

    /**
     * @param \Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Options $model
     * @param $result
     * @return mixed
     */
    public function afterToOptionArray(
        \Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Options $model,
        $result
    ) {
        foreach ($this->customAttributes as $attribute) {
            $customAttribute = [
                'label' => $attribute,
                'value' => $attribute
            ];
            if (!empty($result['customer_composite'])) {
                array_push($result['customer_composite'], $customAttribute);
            }
        }
        return $result;
    }
}
