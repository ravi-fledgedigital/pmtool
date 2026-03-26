<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPCustomerFileExport\Model\Export;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Mapping
{
    private const DATE_FORMAT = 'Y-m-d\TH:i:s.Z\Z';

    public const MAPPING_TYPE_CUSTOM = 'custom';
    public const MAPPING_TYPE_ATTRIBUTE = 'attribute';
    public const MAPPING_TYPE_BILLING_ATTRIBUTE = 'billing_attribute';
    public const MAPPING_TYPE_SHIPPING_ATTRIBUTE = 'shipping_attribute';

    public const BILLING_ATTRIBUTES_PREFIX = 'billing_';
    public const SHIPPING_ATTRIBUTES_PREFIX = 'shipping_';

    private const GENDER_EMPTY_VALUE = 'Not Specified';
    private const GENDER_ALL_VALUES = [
        self::GENDER_EMPTY_VALUE,
        'Male',
        'Female',
    ];

    private bool $salesOrderJoined = false;
    private bool $salesCreditMemoJoined = false;

    /**
     * @var StoreInterface[]|null
     */
    private ?array $storesByNames = null;

    private ScopeConfigInterface $scopeConfig;
    private DateTime $dateTime;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $dateTime,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
    }

    /**
     * @return string[][]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getMapping(): array
    {
        return [
            'Customer_ID' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'entity_id',
                'data_modification_callback' => 'getCustomerId',
            ],
            'Email' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'attribute' => 'email'],
            'First_Name' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'attribute' => 'firstname'],
            'Last_Name' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'attribute' => 'lastname'],
            'DOB' => ['type' => self::MAPPING_TYPE_ATTRIBUTE, 'attribute' => 'dob'],
            'Gender' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'gender',
                'data_modification_callback' => 'getGender',
            ],
            'Default_Billing_Address_Street' => [
                'type' => self::MAPPING_TYPE_BILLING_ATTRIBUTE,
                'attribute' => 'street',
            ],
            'Default_Billing_Address_City' => [
                'type' => self::MAPPING_TYPE_BILLING_ATTRIBUTE,
                'attribute' => 'city',
            ],
            'Default_Billing_Address_Region' => [
                'type' => self::MAPPING_TYPE_BILLING_ATTRIBUTE,
                'attribute' => 'region',
            ],
            'Default_Billing_Address_Postcode' => [
                'type' => self::MAPPING_TYPE_BILLING_ATTRIBUTE,
                'attribute' => 'postcode',
            ],
            'Default_Billing_Address_Country' => [
                'type' => self::MAPPING_TYPE_BILLING_ATTRIBUTE,
                'attribute' => 'country_id',
            ],
            'Default_Shipping_Address_Street' => [
                'type' => self::MAPPING_TYPE_SHIPPING_ATTRIBUTE,
                'attribute' => 'street',
            ],
            'Default_Shipping_Address_City' => [
                'type' => self::MAPPING_TYPE_SHIPPING_ATTRIBUTE,
                'attribute' => 'city',
            ],
            'Default_Shipping_Address_Region' => [
                'type' => self::MAPPING_TYPE_SHIPPING_ATTRIBUTE,
                'attribute' => 'region',
            ],
            'Default_Shipping_Address_Postcode' => [
                'type' => self::MAPPING_TYPE_SHIPPING_ATTRIBUTE,
                'attribute' => 'postcode',
            ],
            'Default_Shipping_Address_Country' => [
                'type' => self::MAPPING_TYPE_SHIPPING_ATTRIBUTE,
                'attribute' => 'country_id',
            ],
            'First_Order_Date' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'first_order_date',
                'prepare_collection_callback' => 'prepareFirstOrderDateColumn',
                'data_modification_callback' => 'getFirstOrderDate',
            ],
            'Total_Coupons' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'total_coupons',
                'prepare_collection_callback' => 'prepareTotalCouponsColumn',
            ],
            'Total_Orders' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'total_orders',
                'prepare_collection_callback' => 'prepareTotalOrderColumn',
            ],
            'Total_Order_Amount' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'total_order_amount',
                'prepare_collection_callback' => 'prepareTotalOrderAmountColumn',
            ],
            'Lifetime_Value_Amount' => [
                'type' => self::MAPPING_TYPE_CUSTOM,
                'data_modification_callback' => 'getLifetimeValueAmount',
            ],
            'Last_Order_Date' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'last_order_date',
                'prepare_collection_callback' => 'prepareLastOrderDateColumn',
                'data_modification_callback' => 'getLastOrderDate',
            ],
            'Total_Return_Order' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'total_return',
                'prepare_collection_callback' => 'prepareTotalReturnColumn',
            ],
            'Total_Return_Order_Amount' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'total_return_amount',
                'prepare_collection_callback' => 'prepareTotalReturnAmountColumn',
                'data_modification_callback' => 'getTotalReturnOrderAmount',
            ],
            'Customer_Base_Country' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'created_in',
                'data_modification_callback' => 'getBaseCountry',
            ],
            'Marketing Email Consent' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'subscribed_to_newsletter',
                'prepare_collection_callback' => 'joinSubscriptionTables',
                'data_modification_callback' => 'getMarketingEmailConsent',
            ],
            'SMS Consent' => [
                'type' => self::MAPPING_TYPE_CUSTOM,
                'data_modification_callback' => 'getSMSConsent',
            ],
            'StoreSignup' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'storesignup',
            ],
            'Modified_Date' => [
                'type' => self::MAPPING_TYPE_ATTRIBUTE,
                'attribute' => 'updated_at',
                'data_modification_callback' => 'formatUpdatedAtDate',
            ],
        ];
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getCustomerId(array $item): string
    {
        $value = $item['entity_id'];
        $prefix = $this->scopeConfig->getValue('aep/general/customer_id_prefix');

        if ($prefix !== null) {
            $value = $prefix . $value;
        }

        return $value;
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getGender(array $item): string
    {
        $value = $item['gender'];
        if (\in_array($value, self::GENDER_ALL_VALUES)) {
            return $value;
        }

        return self::GENDER_EMPTY_VALUE;
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getMarketingEmailConsent(array $item): string
    {
        return (int) $item['subscribed_to_newsletter'] === Subscriber::STATUS_SUBSCRIBED
            || !empty($item['alert_stock_id']) ? 'TRUE' : 'FALSE';
    }

    /**
     * @param string[] $item
     * @return string
     */
    // phpcs:ignore Vaimo.CodeAnalysis.UnusedFunctionParameter.Found
    public function getSMSConsent(array $item): string
    {
        return 'FALSE';
    }

    public function prepareFirstOrderDateColumn(AbstractCollection $collection): void
    {
        $this->joinSalesOrderTable($collection);
        $collection->getSelect()->columns(['first_order_date' => 'MIN(sales_order.created_at)']);
    }

    public function prepareTotalOrderColumn(AbstractCollection $collection): void
    {
        $this->joinSalesOrderTable($collection);
        $collection->getSelect()->columns(['total_orders' => 'COUNT(DISTINCT(sales_order.entity_id))']);
    }

    public function prepareTotalCouponsColumn(AbstractCollection $collection): void
    {
        $collection->getSelect()->joinLeft(
            ['total_coupons' => $collection->getConnection()->getTableName('sales_order')],
            'e.entity_id = total_coupons.customer_id AND total_coupons.coupon_code IS NOT NULL',
            ['total_coupons' => 'COUNT(DISTINCT(total_coupons.entity_id))']
        );
    }

    public function prepareTotalOrderAmountColumn(AbstractCollection $collection): void
    {
        $this->joinSalesOrderTable($collection);
        $collection->getSelect()->columns(['total_order_amount' => 'SUM(sales_order.base_grand_total)']);
    }

    public function prepareLastOrderDateColumn(AbstractCollection $collection): void
    {
        $this->joinSalesOrderTable($collection);
        $collection->getSelect()->columns(['last_order_date' => 'MAX(sales_order.created_at)']);
    }

    public function prepareTotalReturnColumn(AbstractCollection $collection): void
    {
        $this->joinCreditMemoTable($collection);
        $collection->getSelect()->columns(['total_return' => 'COUNT(DISTINCT(sales_creditmemo.entity_id))']);
    }

    public function prepareTotalReturnAmountColumn(AbstractCollection $collection): void
    {
        $this->joinCreditMemoTable($collection);
        $collection->getSelect()->columns(['total_return_amount' => 'SUM(sales_creditmemo.base_grand_total)']);
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getFirstOrderDate(array $item): string
    {
        if (!empty($item['first_order_date'])) {
            return $this->dateTime->date(self::DATE_FORMAT, $item['first_order_date']);
        }

        return 'NULL';
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function formatUpdatedAtDate(array $item): string
    {
        return $this->dateTime->date(self::DATE_FORMAT, $item['updated_at']);
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getLifetimeValueAmount(array $item): string
    {
        if (empty($item['total_order_amount'])) {
            return '0';
        }

        if (empty($item['total_return_amount'])) {
            return $item['total_order_amount'];
        }

        return (string) ((float) $item['total_order_amount'] - (float) $item['total_return_amount']);
    }

    public function joinSalesOrderTable(AbstractCollection $collection): void
    {
        if ($this->salesOrderJoined === true) {
            return;
        }

        $collection->getSelect()->joinLeft(
            ['sales_order' => $collection->getConnection()->getTableName('sales_order')],
            'e.entity_id = sales_order.customer_id',
            ['order_id' => 'sales_order.entity_id']
        );

        $this->salesOrderJoined = true;
    }

    public function joinCreditMemoTable(AbstractCollection $collection): void
    {
        if ($this->salesCreditMemoJoined === true) {
            return;
        }

        $this->joinSalesOrderTable($collection);
        $collection->getSelect()->joinLeft(
            ['sales_creditmemo' => $collection->getConnection()->getTableName('sales_creditmemo')],
            'sales_order.entity_id = sales_creditmemo.order_id',
            ['creditmemo_id' => 'sales_creditmemo.entity_id']
        );

        $this->salesCreditMemoJoined = true;
    }

    public function joinSubscriptionTables(AbstractCollection $collection): void
    {
        $collection->getSelect()->joinLeft(
            ['subscriber' => $collection->getConnection()->getTableName('newsletter_subscriber')],
            'e.email = subscriber.subscriber_email',
            ['subscribed_to_newsletter' => 'subscriber.subscriber_status']
        );

        $collection->getSelect()->joinLeft(
            ['alert_stock' => $collection->getConnection()->getTableName('product_alert_stock')],
            'e.entity_id = alert_stock.customer_id',
            ['alert_stock_id' => 'alert_stock.alert_stock_id']
        );
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getLastOrderDate(array $item): string
    {
        if (!empty($item['last_order_date'])) {
            return $this->dateTime->date(self::DATE_FORMAT, $item['last_order_date']);
        }

        return 'NULL';
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getTotalReturnOrderAmount(array $item): string
    {
        if (!empty($item['total_return_amount'])) {
            return $item['total_return_amount'];
        }

        return '0';
    }

    /**
     * @param string[] $item
     * @return string
     */
    public function getBaseCountry(array $item): string
    {
        $stores = $this->getStoresByNames();

        $store = $stores[$item['created_in']] ?? null;

        if ($store === null) {
            return '';
        }

        return $store->getCode();
    }

    /**
     * @return StoreInterface[]
     */
    private function getStoresByNames(): array
    {
        if (is_array($this->storesByNames)) {
            return $this->storesByNames;
        }

        foreach ($this->storeManager->getStores() as $store) {
            $this->storesByNames[$store->getName()] = $store;
        }

        return $this->storesByNames;
    }
}
