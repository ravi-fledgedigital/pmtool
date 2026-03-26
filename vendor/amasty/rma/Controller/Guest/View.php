<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Guest;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class View extends \Magento\Framework\App\Action\Action
{
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
        Registry $registry,
        CustomerRequestRepositoryInterface $customerRequestRepository,
        ConfigProvider $configProvider,
        Context $context
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->customerRequestRepository = $customerRequestRepository;
        $this->configProvider = $configProvider;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        if (!($requestHash = $this->getRequest()->getParam('request'))) {
            $this->messageManager->addWarningMessage(__('Request is not set'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() .'/guest/login')
            );
        }

        try {
            $request = $this->customerRequestRepository->getByHash($requestHash);
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addWarningMessage(__('Request not longer exists'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() .'/guest/login')
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

        return $resultPage;
    }
}
