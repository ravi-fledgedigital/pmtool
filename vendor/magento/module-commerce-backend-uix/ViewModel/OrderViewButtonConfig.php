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

namespace Magento\CommerceBackendUix\ViewModel;

use Magento\Backend\Model\UrlInterface;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;

/**
 * Order View Button Config for view model
 */
class OrderViewButtonConfig
{
    /**
     * @param RequestInterface $request
     * @param Escaper $escaper
     * @param UrlInterface $backendUrl
     * @param Cache $cache
     * @param array $config
     */
    public function __construct(
        private RequestInterface $request,
        private Escaper $escaper,
        private UrlInterface $backendUrl,
        private Cache $cache,
        private array $config = []
    ) {
    }

    /**
     * Get order view buttons config
     *
     * @return array
     */
    public function getConfig(): array
    {
        $buttonId = $this->request->getParam('buttonId');
        if ($buttonId) {
            $this->completeOrderViewButtonConfig($buttonId);
            $orderId = $this->request->getParam('orderId');
            $this->config['orderViewButton']['orderId'] = $orderId;
            $this->config['orderViewButton']['redirectUrl'] = $this->escaper->escapeUrl(
                $this->backendUrl->getUrl(
                    'adminuisdk/redirect/redirect',
                    [
                        'extensionPoint' => 'orderViewButton',
                        'orderId' => $orderId,
                        'orderViewButtonId' => $buttonId
                    ]
                )
            );
        }
        return $this->config;
    }

    /**
     * Complete order view button config
     *
     * @param string $buttonId
     * @return void
     */
    private function completeOrderViewButtonConfig(string $buttonId): void
    {
        $viewButton = $this->cache->getOrderViewButton($buttonId);
        if (!$viewButton) {
            return;
        }
        $this->config['orderViewButton']['buttonPath'] = $viewButton['path'];
        $this->config['orderViewButton']['pageTitle'] = $viewButton['title'] ?? $viewButton['label'];
    }
}
