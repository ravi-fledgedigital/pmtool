<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\ViewModel\Order\History;

use Amasty\Rma\Api\CreateReturnProcessorInterface;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Layout;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;

class Grid implements ArgumentInterface
{
    public function __construct(
        private readonly CreateReturnProcessorInterface $createReturnProcessor,
        private readonly Layout $layout,
        private ?ConfigProvider $configProvider = null, // TODO move to not optional
        private ?UrlInterface $urlBuilder = null // TODO move to not optional
    ) {
        $this->configProvider ??= ObjectManager::getInstance()->get(ConfigProvider::class);
        $this->urlBuilder ??= ObjectManager::getInstance()->get(UrlInterface::class);
    }

    public function getReturnableOrderIds(): string
    {
        $orderCollection = $this->layout->getBlock('sales.order.history.pager')->getCollection();

        if (!($orderCollection instanceof Collection)) {
            return '';
        }

        $returnableOrderReadIds = [];

        /** @var Order $order */
        foreach ($orderCollection as $order) {
            $returnOrder = $this->createReturnProcessor->process($order->getId());
            $returnItems = $returnOrder ? $returnOrder->getItems() : [];

            foreach ($returnItems as $returnItem) {
                if ($returnItem->isReturnable()) {
                    $returnableOrderReadIds[] = $order->getRealOrderId() . '-oid' . $order->getId();
                    continue 2;
                }
            }
        }

        return implode(',', $returnableOrderReadIds);
    }

    public function getNewReturnUrl(): string
    {
        return $this->urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/account/newreturn'
        );
    }
}
