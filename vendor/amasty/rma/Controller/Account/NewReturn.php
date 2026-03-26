<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Account;

use Amasty\Rma\Api\CreateReturnProcessorInterface;
use Amasty\Rma\Controller\RegistryConstants;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Validation\Customer\CustomerIdValidator;
use Magento\Backend\Model\View\Result\Page;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;

class NewReturn extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        private readonly Session $customerSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Registry $registry,
        private readonly ConfigProvider $configProvider,
        private readonly CreateReturnProcessorInterface $createReturnProcessor,
        Context $context,
        private ?CustomerIdValidator $customerIdValidator = null
    ) {
        parent::__construct($context);
        $this->customerIdValidator ??= ObjectManager::getInstance()->get(CustomerIdValidator::class);
    }

    public function execute(): ResultInterface
    {
        if (!($customerId = $this->customerSession->getCustomerId())) {
            return $this->resultRedirectFactory->create()->setPath('customer/account/login');
        }

        $orderId = (int)$this->getRequest()->getParam('order');
        if (!$orderId) {
            $this->messageManager->addWarningMessage(__('Order is not set'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() . '/account/history')
            );
        }

        $order = $this->orderRepository->get((int)$orderId);
        if (!$this->customerIdValidator->isValid(
            (int)$order->getCustomerId(),
            (int)$customerId
        )) {
            $this->messageManager->addWarningMessage(__('Wrong Order'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() . '/account/history')
            );
        }

        if (!($returnOrder = $this->createReturnProcessor->process($orderId))) {
            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() . '/account/history')
            );
        }

        $this->registry->register(
            RegistryConstants::CREATE_RETURN_ORDER,
            $returnOrder
        );

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('New Return for Order #%1', $order->getIncrementId()));

        if ($navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive($this->configProvider->getUrlPrefix() . '/account/history');
        }

        return $resultPage;
    }
}
