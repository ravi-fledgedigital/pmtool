<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Special Promotions Base for Magento 2
 */

namespace Amasty\Rules\Plugin\SalesRule\Model\Quote\Discount;

use Amasty\Base\Model\MagentoVersion;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\SalesRule\Model\Quote\Discount as RuleDiscount;

class NullifyAppliedRuleIds
{
    public function __construct(
        private readonly MagentoVersion $magentoVersion
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCollect(
        RuleDiscount $subject,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): array {
        if (version_compare($this->magentoVersion->get(), '2.4.7', '<')) {
            foreach ($quote->getAllItems() as $item) {
                $item->setAppliedRuleIds(null);
            }
        }

        return [$quote, $shippingAssignment, $total];
    }
}
