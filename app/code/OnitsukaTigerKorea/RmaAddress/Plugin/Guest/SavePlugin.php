<?php

namespace OnitsukaTigerKorea\RmaAddress\Plugin\Guest;

use Amasty\Rma\Controller\Guest\Save;
use Closure;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface;

class SavePlugin
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var OnitsukaTigerKorea\RmaAddress\Helper\Data
     */
    private $dataHelper;

    /**
     * SavePlugin constructor.
     * @param ManagerInterface $messageManager
     * @param RedirectInterface $redirect
     */
    public function __construct(
        ManagerInterface $messageManager,
        \OnitsukaTigerKorea\RmaAddress\Helper\Data $dataHelper,
        RedirectInterface $redirect
    ) {
        $this->messageManager = $messageManager;
        $this->dataHelper = $dataHelper;
        $this->redirect = $redirect;
    }

    /**
     * @param Save $subject
     * @param Closure $proceed
     * @return mixed
     */
    public function aroundExecute(Save $subject, Closure $proceed)
    {
        if($this->dataHelper->enableShowAddressRMA()){
            $rmaAddress = $subject->getRequest()->getParam('rma_address');
            if (!$rmaAddress) {
                $this->messageManager->addErrorMessage(__('Please select RMA address.'));
                return $subject->getResponse()->setRedirect($this->redirect->getRefererUrl());
            }
        }
        return $proceed();
    }

}
