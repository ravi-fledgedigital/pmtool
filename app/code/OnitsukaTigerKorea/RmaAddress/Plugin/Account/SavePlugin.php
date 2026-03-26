<?php

namespace OnitsukaTigerKorea\RmaAddress\Plugin\Account;

use Amasty\Rma\Controller\Account\Save;
use Closure;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface;
use OnitsukaTigerKorea\RmaAddress\Helper\Data;

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
     * @var Data
     */
    private $dataHelper;

    /**
     * SavePlugin constructor.
     * @param ManagerInterface $messageManager
     * @param RedirectInterface $redirect
     */
    public function __construct(
        ManagerInterface $messageManager,
        Data $dataHelper,
        RedirectInterface $redirect
    ) {
        $this->dataHelper = $dataHelper;
        $this->messageManager = $messageManager;
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
