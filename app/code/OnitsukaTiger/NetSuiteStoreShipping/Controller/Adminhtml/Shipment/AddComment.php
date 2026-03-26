<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class AddComment extends AbstractAddComment
{
    /**
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    protected function _isAllowed()
    {
        $shipment = $this->shipmentRepository->get($this->getRequest()->getParam('id'));
        $resource = 'OnitsukaTiger_NetSuiteStoreShipping::' . $shipment->getExtensionAttributes()->getSourceCode();
        return $this->_authorization->isAllowed($resource);
    }
}
