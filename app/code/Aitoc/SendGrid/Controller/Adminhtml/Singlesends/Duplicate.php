<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Controller\Adminhtml\Singlesends;

use Magento\Backend\App\Action;

class Duplicate extends Action
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
                $this->apiWork->createSingleSend($this->prepareSingleSendData($sendId));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    /**
     * @param $sendId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareSingleSendData($sendId)
    {
        $singleSend = $this->apiWork->getSingleSendById($sendId);

        if ($singleSend) {
            $singleSend['name'] = $singleSend['name'] . '-duplicate';
            unset($singleSend['id']);
            unset($singleSend['updated_at']);
            unset($singleSend['created_at']);
        }

        return $singleSend;
    }
}
