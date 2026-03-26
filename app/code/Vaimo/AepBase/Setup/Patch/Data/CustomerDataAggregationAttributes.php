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

class CustomerDataAggregationAttributes implements DataPatchInterface
{
    public const SHOE_FAV_SIZE = 'aep_shoe_fav_size';
    public const CLOTHS_FAVORITE_SIZE = 'aep_cloths_favorite_size';
    public const ACCESSORIES_FAVORITE_SIZE = 'aep_accessories_favorite_size';
    public const TOTAL_COUPON_COUNT = 'aep_total_coupon_count';
    public const TOTAL_ORDER_AMT = 'aep_total_order_amt';
    public const TOTAL_ORDER_CNT = 'aep_total_order_cnt';
    public const WHISHLIST_PRODUCTS = 'aep_whishlist_products';
    public const CART_ABANDONED_PRODUCTS = 'aep_cart_abandoned_products';
    public const LIFETIME_VALUE_AMT = 'aep_lifetime_value_amt';
    public const FIRST_ORDER_DATE = 'aep_first_order_date';
    public const LAST_ORDER_DATE = 'aep_last_order_date';
    public const TOTAL_RETURN_ORDER_AMT = 'aep_total_return_order_amt';
    public const TOTAL_RETURN_ORDER_CNT = 'aep_total_return_order_cnt';
    public const WISHLIST_MODIFIED_DATE_TIME = 'aep_wishlist_modified_datetime';
    public const CART_MODIFIED_DATE_TIME = 'aep_cart_modified_datetime';

    public const ATTRIBUTE_CODES = [
        self::SHOE_FAV_SIZE,
        self::CLOTHS_FAVORITE_SIZE,
        self::ACCESSORIES_FAVORITE_SIZE,
        self::TOTAL_COUPON_COUNT,
        self::TOTAL_ORDER_AMT,
        self::TOTAL_ORDER_CNT,
        self::WHISHLIST_PRODUCTS,
        self::CART_ABANDONED_PRODUCTS,
        self::LIFETIME_VALUE_AMT,
        self::FIRST_ORDER_DATE,
        self::LAST_ORDER_DATE,
        self::TOTAL_RETURN_ORDER_AMT,
        self::TOTAL_RETURN_ORDER_CNT,
        self::WISHLIST_MODIFIED_DATE_TIME,
        self::CART_MODIFIED_DATE_TIME
    ];

    private const ATTRIBUTES_DATA = [
        self::SHOE_FAV_SIZE => [
            'type' => 'varchar',
            'label' => 'Favorite Size (Shoes)',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 250,
            'sort_order' => 250,
        ],
        self::CLOTHS_FAVORITE_SIZE => [
            'type' => 'varchar',
            'label' => 'Favorite Size (Cloths)',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 260,
            'sort_order' => 260,
        ],
        self::ACCESSORIES_FAVORITE_SIZE => [
            'type' => 'varchar',
            'label' => 'Favorite Size (Accessories)',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 270,
            'sort_order' => 270,
        ],
        self::TOTAL_COUPON_COUNT => [
            'type' => 'int',
            'label' => 'Total number of coupons applied to all orders placed by this customer',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 280,
            'sort_order' => 280,
        ],
        self::TOTAL_ORDER_AMT => [
            'type' => 'decimal',
            'label' => 'Sum total of revenue for all orders placed by this customer',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 290,
            'sort_order' => 290,
        ],
        self::TOTAL_ORDER_CNT => [
            'type' => 'int',
            'label' => 'Total count of orders placed by this customer',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 300,
            'sort_order' => 300,
        ],
        self::LIFETIME_VALUE_AMT => [
            'type' => 'decimal',
            'label' => 'Sum total of revenue for all orders placed by this customer.',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 310,
            'sort_order' => 310,
        ],
        self::WHISHLIST_PRODUCTS => [
            'type' => 'varchar',
            'label' => 'Whishlist products',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 320,
            'sort_order' => 320,
        ],
        self::CART_ABANDONED_PRODUCTS => [
            'type' => 'varchar',
            'label' => 'Cart abandoned products',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 330,
            'sort_order' => 330,
        ],
        self::FIRST_ORDER_DATE => [
            'type' => 'datetime',
            'label' => 'First order date',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 340,
            'sort_order' => 340,
        ],
        self::LAST_ORDER_DATE => [
            'type' => 'datetime',
            'label' => 'Last order date',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 350,
            'sort_order' => 350,
        ],
        self::TOTAL_RETURN_ORDER_CNT => [
            'type' => 'int',
            'label' => 'Total count of customer RMAs',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 360,
            'sort_order' => 360,
        ],
        self::TOTAL_RETURN_ORDER_AMT => [
            'type' => 'decimal',
            'label' => 'Sum total customer RMA values.',
            'required' => '0',
            'visible' => '1',
            'user_defined' => '0',
            'is_unique' => '0',
            'system' => '1',
            'position'   => 370,
            'sort_order' => 370,
        ],
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
