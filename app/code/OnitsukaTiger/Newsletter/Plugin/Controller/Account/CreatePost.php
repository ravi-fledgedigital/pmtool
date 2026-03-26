<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Newsletter\Plugin\Controller\Account;

use Magento\Framework\Controller\ResultFactory;

class CreatePost
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    /**
     * @var \OnitsukaTiger\Newsletter\Helper\Data
     */
    private $_helper;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Element\Template\Context $context,
        ResultFactory $resultFactory,
        \OnitsukaTiger\Newsletter\Helper\Data $helper,
        \Magento\Framework\App\Action\Context $contextAction
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $contextAction->getMessageManager();
        $this->_storeManager = $context->getStoreManager();
        $this->resultFactory = $resultFactory;
        $this->_helper = $helper;
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param $result
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function afterExecute(\Magento\Customer\Controller\Account\CreatePost $subject,  $result)
    {

        $error = 0;
        if($errorMessage = $this->messageManager->getMessages()->getErrors()){
            $error = 1;
        }
        if(!$error) {
            if($subject->getRequest()->getParam('is_subscribed')){
                $this->_helper->sendDiscountCode($subject->getRequest()->getParam('email'));
            }
        }
        return $result;
    }
}
