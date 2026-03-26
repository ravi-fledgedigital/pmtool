<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\AdvancedCheckout\Model\ApplyCoupons;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

class ApplyCoupon extends \Magento\AdvancedCheckout\Controller\Adminhtml\Index implements HttpPostActionInterface
{
    /**
     * @var ApplyCoupons
     */
    private $applyCoupons;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CustomerInterfaceFactory $customerFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ApplyCoupons|null $applyCoupons
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CustomerInterfaceFactory $customerFactory,
        DataObjectHelper $dataObjectHelper,
        ApplyCoupons $applyCoupons = null
    ) {
        $this->applyCoupons = $applyCoupons ?: ObjectManager::getInstance()->get(ApplyCoupons::class);
        parent::__construct($context, $registry, $customerFactory, $dataObjectHelper);
    }

    /**
     * Apply/cancel coupon code in quote, ajax
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->_isModificationAllowed();
            $this->_initData();
            if ($this->_redirectFlag) {
                return;
            }
            $code = $this->getRequest()->getPost('code', '');
            $remove = $this->getRequest()->getPost('remove', false);
            $quote = $this->_registry->registry('checkout_current_quote');

            $this->_view->loadLayout();
            try {
                $this->applyCoupons->apply($quote, [$code], $remove);
            } catch (LocalizedException $exception) {
                $this->_view->getLayout()->getBlock('form_coupon')->setCouponErrorMessage($exception->getMessage());
                $this->_view->getLayout()->getBlock('form_coupon')->setInvalidCouponCode($code);
            }
            $this->_view->renderLayout();
        } catch (\Exception $e) {
            $this->_processException($e);
        }
    }
}
