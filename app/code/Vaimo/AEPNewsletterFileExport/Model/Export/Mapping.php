<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPNewsletterFileExport\Model\Export;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Newsletter\Helper\Data as NewsletterHelper;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\Subscriber;

class Mapping
{
    private const UPDATED_AT_DATE_FORMAT = 'Y-m-d\TH:i:s.Z\Z';
    private DateTime $dateTime;
    private ScopeConfigInterface $scopeConfig;
    private NewsletterHelper $newsletterHelper;

    public function __construct(
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        NewsletterHelper $newsletterHelper
    ) {
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->newsletterHelper = $newsletterHelper;
    }

    /**
     * @return string[][]
     */
    public function getMapping(): array
    {
        return [
            'storeID' => [
                'attribute' => 'store_id',
            ],
            'storeCode' => [
                'attribute' => 'store_code',
                'prepare_collection_callback' => 'prepareStoreCodeValue',
            ],
            'Customer_ID' => [
                'attribute' => 'customer_id',
                'data_modification_callback' => 'getCustomerId',
            ],
            'subscriberEmail' => [
                'attribute' => 'subscriber_email',
            ],
            'subscriberId' => [
                'attribute' => 'subscriber_id',
            ],
            'subscriberStatus' => [
                'attribute' => 'subscriber_status',
                'data_modification_callback' => 'getSubscriptionStatus',
            ],
            'unsubscriptionURL' => [
                'data_modification_callback' => 'getUnsubscribeUrl',
            ],
            'modifiedDate' => [
                'attribute' => 'change_status_at',
                'data_modification_callback' => 'prepareDate',
            ],
        ];
    }

    public function prepareStoreCodeValue(Collection $collection): void
    {
        $collection->getSelect()->joinLeft(
            ['store' => $collection->getConnection()->getTableName('store')],
            'main_table.store_id = store.store_id',
            ['store_code' => 'store.code']
        );
    }

    public function getCustomerId(Subscriber $item): string
    {
        $value = $item->getData('customer_id');
        if ($value === '0') {
            return $value;
        }

        $prefix = $this->scopeConfig->getValue('aep/general/customer_id_prefix');

        if ($prefix !== null) {
            $value = $prefix . $value;
        }

        return $value;
    }

    public function getSubscriptionStatus(Subscriber $item): string
    {
        return $item->getData('subscriber_status') === '1' ? '1' : '0';
    }

    public function prepareDate(Subscriber $item): string
    {
        if (empty($item->getData('change_status_at'))) {
            return '';
        }

        return $this->dateTime->date(self::UPDATED_AT_DATE_FORMAT, $item['change_status_at']);
    }

    public function getUnsubscribeUrl(Subscriber $item): string
    {
        return $this->newsletterHelper->getUnsubscribeUrl($item);
    }
}
