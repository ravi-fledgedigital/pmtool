<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/* These classes are skipped completely during comparison. */
return [
    // phpcs:disable Generic.Files.LineLength.TooLong
    'updateCustomer' => [
        Magento\LoginAsCustomerLogging\Observer\LogSaveCustomerAddressObserver::class => null,
        Magento\LoginAsCustomerLogging\Observer\LogSaveCustomerObserver::class => null,
        Magento\CustomerSegment\Model\Customer::class => null,
        Magento\CustomerSegment\Observer\ProcessEventGenericObserver::class => null,
        Magento\GiftRegistry\Helper\Data::class => null,
        Magento\GiftRegistry\Observer\AddressDataAfterLoad::class => null,
    ],
    'updateCustomerAddress' => [
        Magento\LoginAsCustomerLogging\Observer\LogSaveCustomerAddressObserver::class => null,
        Magento\LoginAsCustomerLogging\Observer\LogSaveCustomerAddressObserver::class => null,
        Magento\LoginAsCustomerLogging\Observer\LogSaveCustomerObserver::class => null,
        Magento\CustomerSegment\Model\Customer::class => null,
        Magento\CustomerSegment\Observer\ProcessEventGenericObserver::class => null,
        Magento\GiftRegistry\Helper\Data::class => null,
        Magento\GiftRegistry\Observer\AddressDataAfterLoad::class => null,
        Magento\Logging\Model\Handler\Controllers::class => null,

    ],
    'updateCustomerEmail' => [
        Magento\GiftRegistry\Helper\Data::class => null,
        Magento\GiftRegistry\Observer\AddressDataAfterLoad::class => null,
        Magento\CustomerSegment\Model\Customer::class => null,
        Magento\CustomerSegment\Observer\ProcessEventGenericObserver::class => null,
        Magento\Logging\Model\Handler\Controllers::class => null,

    ],
    'createCustomer' => [
        Magento\CustomerSegment\Model\Customer::class => null,
        Magento\CustomerSegment\Observer\ProcessCustomerEventObserver::class => null,
        Magento\Logging\Helper\Data::class => null,
        Magento\Logging\Model\Handler\Controllers::class => null,
        Magento\GiftWrapping\Helper\Data::class => null,
        Magento\GiftCardAccount\Helper\Data::class => null,
        Magento\CustomerBalance\Helper\Data::class => null,
        Magento\Reward\Helper\Data::class => null,
    ],
    '*' => [
        Magento\ApplicationServer\App\RequestProxy::class => null,
        Magento\ApplicationServer\App\CookieManager::class => null,
        Magento\InventorySalesAsyncOrder\Model\ReservationExecution::class => null,
        Magento\InventorySalesAsyncOrder\Plugin\SkipAsyncOrderCheckDataWithNoDeferredStockUpdatePlugin::class => null,
        Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote::class => null,
        Magento\CustomerCustomAttributes\Model\Sales\QuoteFactory::class => null,
        Magento\CustomerCustomAttributes\Model\Quote\Relation::class => null,
        Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory::class => null,
        Magento\CustomerSegment\Model\ResourceModel\Customer::class => null,
        Magento\CustomerSegment\Model\Customer::class => null,
        Magento\CustomerSegment\Observer\ProcessEventGenericObserver::class => null,
        Magento\CustomerSegment\Model\ResourceModel\Helper::class => null,
        Magento\Reward\Model\SalesRule\RewardPointCounter::class => null,
        Magento\Staging\Model\StagingList::class => null, // TODO: does this need anything?
        Magento\ResourceConnections\App\DeploymentConfig::class => null, // FIXME: I believe this can be rewritten to avoid need of "slaveConfig" mutable property
        Magento\ApplicationServer\Eav\Model\Config\ClearWithoutCleaningCache::class => null, // Note: gets cleaned after poison pill
    ],
    '*-fromConstructed' => [
        Magento\CatalogPermissions\Model\Indexer\TableMaintainer::class => null,
        Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote\Address::class => null,
        Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote::class => null,
        Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Order::class => null,
        Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Order\Address::class => null,
        Magento\GiftCard\Model\Attribute\Backend\Giftcard\Amount\Interceptor::class => null,
        Magento\TargetRule\Model\Catalog\Product\Attribute\Backend\Rule\Interceptor::class => null,
        Magento\Reward\Model\Reward::class => null,
        Magento\Reward\Model\Reward\Rate::class => null,
        Magento\CustomerCustomAttributes\Helper\Address::class => null, // FIXME: needs resetSate
        Magento\Logging\Model\Config::class => null, // FIXME: remove this after ACPT-1034 is fixed
        Magento\Staging\Model\VersionManager\Interceptor::class => null, // Has good _resetState
    ],
    'ApplicationServerStateMonitor-fromConstructed' => [
        Magento\ApplicationServerStateMonitorGraphQl\StateMonitor\GraphQlLogger::class => null, // part of StateMonitor
    ],
    'placeOrder' => [
        Magento\Store\Model\ResourceModel\Group\Collection\FetchStrategy::class => null,
    ],
    // phpcs:enable Generic.Files.LineLength.TooLong
];
