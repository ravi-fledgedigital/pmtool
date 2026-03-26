<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Account;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CustomerRequestRepositoryInterface
     */
    private $customerRequestRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Session $customerSession,
        Registry $registry,
        ConfigProvider $configProvider,
        CustomerRequestRepositoryInterface $customerRequestRepository,
        Context $context
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        $this->customerRequestRepository = $customerRequestRepository;
        $this->configProvider = $configProvider;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        if (!($customerId = $this->customerSession->getCustomerId())) {
            return $this->resultRedirectFactory->create()->setPath('customer/account/login');
        }

        $requestId = (int)$this->getRequest()->getParam('request');
        if (!$requestId) {
            $this->messageManager->addWarningMessage(__('Request Id is not set'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() . '/account/history')
            );
        }

        try {
            $request = $this->customerRequestRepository->getById($requestId, $customerId);
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addWarningMessage(__('Request not longer exists'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() . '/account/history')
            );
        }

        $this->registry->register(
            \Amasty\Rma\Controller\RegistryConstants::REQUEST_VIEW,
            $request
        );

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(
            __('Return #%1', $request->getIncrementId())
        );

        if ($navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive($this->configProvider->getUrlPrefix() . '/account/history');
        }

        return $resultPage;
    }
}
