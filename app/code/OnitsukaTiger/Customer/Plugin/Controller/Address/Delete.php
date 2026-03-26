<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Customer\Plugin\Controller\Address;

use Magento\Framework\Controller\ResultFactory;

/**
 * Handles store switching url and makes redirect.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Delete
{

    /**
     * @var \Magento\Framework\App\ViewInterface
     */
    protected $_view;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $helperCheckout;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;


    /**
     * FormPost constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \OnitsukaTiger\Checkout\Helper\Data $helper,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->_view = $context->getView();
        $this->messageManager = $context->getMessageManager();
        $this->helperCheckout = $helper;
        $this->resultFactory = $context->getResultFactory();
    }

    /**
     * @param \Magento\Customer\Controller\Address\Delete $subject
     * @param $result
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function afterExecute(\Magento\Customer\Controller\Address\Delete $subject,  $result)
    {
        if($subject->getRequest()->getParam('isAjax')){
            $this->_view->loadLayout();

            /** @var \Magento\Customer\Block\Address\Grid $block */
            $block = $this->_view->getLayout()->createBlock('Magento\Customer\Block\Address\Grid')->setTemplate('Magento_Customer::address/grid-popup.phtml');
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $customerData = $this->helperCheckout->getCustomerData();
            $response = [
                'address' => $block->toHtml(),
                'customerData' => $customerData,
                'success' => 1
                ];
            $resultJson->setData($response);
            $this->messageManager->getMessages(true);
            return $resultJson;
        }
        return $result;
    }
}
