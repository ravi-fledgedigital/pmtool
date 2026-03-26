<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\Checkout\Plugin\Block\Checkout;

use OnitsukaTiger\Checkout\Block\Onepage\Success\Info as CheckoutInfo;
use OnitsukaTigerKorea\Checkout\Helper\Data;

/**
 * Class Info
 * @package OnitsukaTigerKorea\Checkout\Plugin\Block\Checkout
 */
class Info
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * LayoutProcessor constructor.
     * @param Data $dataHelper
     */
    public function __construct(
        Data $dataHelper
    )
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param CheckoutInfo $subject
     * @param $result
     * @return string
     */
    public function afterGetTemplate(CheckoutInfo $subject, $result) {
        if ($this->dataHelper->isCheckoutEnabled()) {
            $subject->setTemplate('OnitsukaTigerKorea_Checkout::checkout/onepage/order_info.phtml');
        }
        return $result;
    }
}
