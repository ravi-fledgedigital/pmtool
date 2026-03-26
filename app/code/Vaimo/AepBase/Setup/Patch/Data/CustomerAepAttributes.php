<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CustomerAepAttributes implements DataPatchInterface
{
    public const WISHLIST_MODIFIED_DATE_TIME = 'aep_wishlist_modified_datetime';
    public const CART_MODIFIED_DATE_TIME = 'aep_cart_modified_datetime';

    public const ATTRIBUTE_CODES = [
        self::WISHLIST_MODIFIED_DATE_TIME,
        self::CART_MODIFIED_DATE_TIME
    ];

    private const ATTRIBUTES_DATA = [
        self::WISHLIST_MODIFIED_DATE_TIME => [
            'type' => 'datetime',
            'label' => 'Wishlist modified date time',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 380,
            'sort_order' => 380,
        ],
        self::CART_MODIFIED_DATE_TIME => [
            'type' => 'datetime',
            'label' => 'Cart modified date time',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 390,
            'sort_order' => 390,
        ]
    ];

    private EavSetupFactory $eavSetupFactory;
    private EavConfig $eavConfig;

    public function __construct(
        EavConfig $eavConfig,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create();

        foreach (self::ATTRIBUTES_DATA as $attributeCode => $attributeData) {
            $eavSetup->addAttribute(
                Customer::ENTITY,
                $attributeCode,
                $attributeData
            );

            $attribute = $this->eavConfig->getAttribute(
                Customer::ENTITY,
                $attributeCode
            );

            $attribute->setData(
                'used_in_forms',
                ['adminhtml_customer']
            );
            $attribute->save();
        }
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
