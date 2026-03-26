<?php

namespace OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\Reason;

use OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\AbstractReason;
use Magento\Framework\Controller\ResultFactory;

class Create extends AbstractReason
{
    /**
     * @return void
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

        return $result->forward('edit');
    }
}
