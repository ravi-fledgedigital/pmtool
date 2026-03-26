<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\Customer\Plugin\Controller\Address;

use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Handles store switching url and makes redirect.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FormPost extends \OnitsukaTiger\Customer\Plugin\Controller\Address\FormPost
{

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $helperCheckout;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|mixed
     */
    private $serializer;

    /**
     * FormPost constructor.
     * @param \OnitsukaTiger\Checkout\Helper\Data $helper
     * @param Data $dataHelper
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \OnitsukaTiger\Checkout\Helper\Data $helper,
        \Magento\Framework\App\Action\Context $context,
        Data $dataHelper,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->helperCheckout = $helper;
        $this->dataHelper = $dataHelper;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        parent::__construct($helper, $context);
    }

    /**
     * @param \Magento\Customer\Controller\Address\FormPost $subject
     * @param $result
     * @return \Magento\Customer\Block\Address\Grid
     */
    public function afterExecute(\Magento\Customer\Controller\Address\FormPost $subject,  $result)
    {
        $resultJson = parent::afterExecute($subject,$result);
        if ($this->dataHelper->isCustomerEnabled()) {
            if ($subject->getRequest()->getParam('isAjax')) {
                $block = $this->_view->getLayout()->getBlock('address\grid_0')->setTemplate('OnitsukaTigerKorea_Customer::address/grid-popup.phtml');
                $customerData = $this->helperCheckout->getCustomerData();
                $response = [
                    'address' => $block->toHtml(),
                    'customerData' => $customerData,
                    'success' => 1
                ];
                $resultJson->setJsonData($this->serializer->serialize($response));
            }
        }
        return $resultJson;
    }
}
