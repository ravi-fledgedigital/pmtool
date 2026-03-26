<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\OrderTracking\Controller\Guest;

use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\ResultInterface;

/**
 * Guest order view action.
 */
class View extends \Magento\Sales\Controller\Guest\View
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->guestHelper->loadValidOrder($this->getRequest());
        if ($result == false) {
            $resultRaw = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
            $resultRaw->setContents('false');
            return $resultRaw;
        }
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->guestHelper->getBreadcrumbs($resultPage);
        return $resultPage;
    }
}
