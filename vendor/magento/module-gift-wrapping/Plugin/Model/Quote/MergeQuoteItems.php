<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Plugin\Model\Quote;

use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Processor;

class MergeQuoteItems
{
    /**
     * Resolves gift wrapping to be applied to merged quote items.
     *
     * @param Processor $subject
     * @param Item $result
     * @param Item $source
     * @return Item
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterMerge(Processor $subject, Item $result, Item $source): Item
    {
        /** @var \Magento\Quote\Model\Quote $source */
        $giftWrappingId = $source->getGwId();

        if ($giftWrappingId) {
            $result->setGwId($giftWrappingId);
        }

        return $result;
    }
}
