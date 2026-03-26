<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\ViewModel\MassAction;

use Magento\Backend\Model\UrlInterface;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Escaper;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Customer Mass Action Config for view model
 */
class CustomerMassActionConfig implements MassActionConfigInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     * @param Escaper $escaper
     * @param UrlInterface $backendUrl
     * @param Filter $filter
     * @param Cache $cache
     * @param array $config
     */
    public function __construct(
        private CollectionFactory $collectionFactory,
        private Escaper $escaper,
        private UrlInterface $backendUrl,
        private Filter $filter,
        private Cache $cache,
        private array $config = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getConfig(string $actionId): array
    {
        $this->completeMassActionConfig($actionId);
        $this->config['redirectUrl'] = $this->escaper->escapeUrl(
            $this->backendUrl->getUrl(
                'adminuisdk/redirect/redirect',
                [
                    'extensionPoint' => 'customerMassAction',
                    'massActionId' => $actionId
                ]
            )
        );
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function getSelectedIds(): array
    {
        return $this->filter->getCollection($this->collectionFactory->create())->getAllIds();
    }

    /**
     * Complete the mass action configuration with the action id, action URL path and page title
     *
     * @param string $actionId
     * @return void
     */
    private function completeMassActionConfig(string $actionId): void
    {
        $massAction = $this->cache->getMassAction(UiGridType::CUSTOMER_GRID, $actionId);
        if ($massAction) {
            $this->config['actionId'] = $actionId;
            $this->config['actionUrlPath'] = $massAction['path'];
            $this->config['pageTitle'] = $massAction['title'] ?? $massAction['label'];
        }
    }
}
