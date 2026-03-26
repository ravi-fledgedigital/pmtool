<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\Rma\Controller\Adminhtml\Customer;
use Magento\Customer\Controller\Adminhtml\Index;
use Magento\Framework\View\Result\Layout;

class Memo extends Index
{
    /**
     * Customer orders memo grid
     *
     * @return Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        return $this->resultLayoutFactory->create();
    }
}

