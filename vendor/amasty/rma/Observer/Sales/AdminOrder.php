<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Backend\Model\Session;

class AdminOrder implements ObserverInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var Session\Quote
     */
    private $quoteSession;

    public function __construct(
        Session $session,
        \Magento\Backend\Model\Session\Quote $quoteSession
    ) {
        $this->session = $session;
        $this->quoteSession = $quoteSession;
    }

    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getData('order');
        $reorderedId = (int)$this->quoteSession->getData('reordered');
        $backToRmaFlow = $this->session->getBackToRmaFlow() ?? [];

        if ($reorderedId && isset($backToRmaFlow[$reorderedId])) {
            $flowPart = $backToRmaFlow[$reorderedId];
            // re-assign flow item to new order id
            unset($backToRmaFlow[$reorderedId]);
            $flowPart['complete'] = true;
            $backToRmaFlow[(int)$order->getId()] = $flowPart;
            $this->session->setBackToRmaFlow($backToRmaFlow);
        }
    }
}
