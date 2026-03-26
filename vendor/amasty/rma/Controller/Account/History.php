<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Account;

use Amasty\Rma\Model\ConfigProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

class History extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Session $customerSession,
        ConfigProvider $configProvider,
        Context $context
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        if ($customerId = $this->customerSession->getCustomerId()) {
            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $resultPage->getConfig()->getTitle()->set(__('My Returns Requests'));

            $navigationBlock = $resultPage->getLayout()->getBlock(
                'customer_account_navigation'
            );
            if ($navigationBlock) {
                $navigationBlock->setActive($this->configProvider->getUrlPrefix() . '/account/history');
            }

            return $resultPage;
        }

        return $this->resultRedirectFactory->create()->setPath('customer/account/login');
    }
}
