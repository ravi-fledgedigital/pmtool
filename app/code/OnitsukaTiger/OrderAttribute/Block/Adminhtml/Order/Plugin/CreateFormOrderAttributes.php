<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Order\Plugin;

use OnitsukaTiger\OrderAttribute\Block\Adminhtml\Order\Create\Form\Attributes;

class CreateFormOrderAttributes
{
    public function afterToHtml(\Magento\Sales\Block\Adminhtml\Order\Create\Form\Account $subject, $result)
    {
        $orderAttributesForm = $subject->getLayout()->createBlock(
            Attributes::class,
            '',
            ['orderStoreId' => $subject->getStore()->getId()]
        );
        $orderAttributesForm->setQuote($subject->getQuote());

        return $result . $orderAttributesForm->toHtml();
    }
}
