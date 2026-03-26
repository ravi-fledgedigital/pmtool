<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
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

namespace Magento\CommerceBackendUix\ViewModel;

use Magento\Backend\Model\UrlInterface;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\ViewModel\MassAction\MassActionConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Config for view model
 */
class Config implements ArgumentInterface
{
    /**
     * @param RequestInterface $request
     * @param Escaper $escaper
     * @param UrlInterface $backendUrl
     * @param SerializerInterface $serializer
     * @param Cache $cache
     * @param MassActionConfig $massActionConfig
     * @param OrderViewButtonConfig $orderViewButtonConfig
     * @param array $config
     */
    public function __construct(
        private RequestInterface $request,
        private Escaper $escaper,
        private UrlInterface $backendUrl,
        private SerializerInterface $serializer,
        private Cache $cache,
        private MassActionConfig $massActionConfig,
        private OrderViewButtonConfig $orderViewButtonConfig,
        private array $config = []
    ) {
    }

    /**
     * Get config path
     *
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->escaper->escapeUrl(
            $this->backendUrl->getUrl(
                'adminuisdk/config/config',
                [
                    'extensionId' => $this->request->getParam('extensionId'),
                    'buttonId' => $this->request->getParam('buttonId'),
                    'orderId' => $this->request->getParam('orderId'),
                    'productActionId' => $this->request->getParam('productActionId'),
                    'orderActionId' => $this->request->getParam('orderActionId'),
                    'customerActionId' => $this->request->getParam('customerActionId')
                ]
            )
        );
    }

    /**
     * Get selected ids for mass actions
     *
     * @return string
     */
    public function getSelectedIdsAsJson(): string
    {
        return $this->serializer->serialize($this->massActionConfig->getSelectedIds());
    }

    /**
     * Get registration config
     *
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        $this->config['extensions'] = $this->cache->getRegisteredExtensions();
        if ($selectedExtensionId = $this->request->getParam('extensionId')) {
            $this->config['selectedExtensionId'] = $selectedExtensionId;
        }

        return array_merge(
            $this->config,
            $this->massActionConfig->getConfig(),
            $this->orderViewButtonConfig->getConfig()
        );
    }
}
