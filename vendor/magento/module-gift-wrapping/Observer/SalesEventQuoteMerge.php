<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;

class SalesEventQuoteMerge implements ObserverInterface
{
    /**
     * Sets gift wrapping to customer quote from guest quote.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var  Quote $targetQuote */
        $targetQuote = $observer->getData('quote');
        /** @var  Quote $sourceQuote */
        $sourceQuote = $observer->getData('source');

        $giftWrappingId = $sourceQuote->getGwId();
        if ($giftWrappingId) {
            $targetQuote->setGwId($giftWrappingId);
        }

        return $this;
    }
}
