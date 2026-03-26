<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Controller\Adminhtml\Singlesends;

use Magento\Backend\App\Action;

class Delete extends Action
{
    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    private $apiWork;

    public function __construct(
        Action\Context $context,
        \Aitoc\SendGrid\Model\ApiWork $apiWork
    ) {
        parent::__construct($context);
        $this->apiWork = $apiWork;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $sendId = $this->getRequest()->getParam('id');
        if ($sendId) {
            try {
                $this->apiWork->deleteSingleSendById($sendId);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
