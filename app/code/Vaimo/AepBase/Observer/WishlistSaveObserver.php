<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Wishlist\Model\Wishlist;
use Psr\Log\LoggerInterface;
use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepBase\Service\Wishlist as WishlistService;

class WishlistSaveObserver implements ObserverInterface
{
    private ConfigInterface $config;
    private WishlistService $wishlistService;
    private LoggerInterface $logger;

    public function __construct(
        ConfigInterface $config,
        WishlistService $wishlistService,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->wishlistService = $wishlistService;
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isDataAggregationEnabled()) {
            return;
        }

        /** @var Wishlist $wishlist */
        $wishlist = $observer->getData('wishlist');

        if ($wishlist === null) {
            $wishlist = $observer->getData('object');
        }

        try {
            $this->wishlistService->updateCustomer($wishlist);
        } catch (\Throwable $e) {
            $this->logger->critical($e);
        }
    }
}
