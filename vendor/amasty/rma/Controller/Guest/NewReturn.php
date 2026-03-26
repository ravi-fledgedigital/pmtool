<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Guest;

use Amasty\Rma\Api\CreateReturnProcessorInterface;
use Amasty\Rma\Api\GuestCreateRequestProcessInterface;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Validation\GuestLogin\TypeValidatorProvider;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;

class NewReturn extends \Magento\Framework\App\Action\Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CreateReturnProcessorInterface
     */
    private $createReturnProcessor;

    /**
     * @var GuestCreateRequestProcessInterface
     */
    private $guestCreateRequestProcess;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TypeValidatorProvider
     */
    private $typeValidatorProvider;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Registry $registry,
        ConfigProvider $configProvider,
        GuestCreateRequestProcessInterface $guestCreateRequestProcess,
        CreateReturnProcessorInterface $createReturnProcessor,
        Context $context,
        TypeValidatorProvider $typeValidatorProvider
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
        $this->createReturnProcessor = $createReturnProcessor;
        $this->guestCreateRequestProcess = $guestCreateRequestProcess;
        $this->configProvider = $configProvider;
        $this->typeValidatorProvider = $typeValidatorProvider
            ?? ObjectManager::getInstance()->get(TypeValidatorProvider::class);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        if (!($secretKey = $this->getRequest()->getParam('secret'))) {
            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() .'/guest/login'
            );
        }

        if (!($orderId = $this->guestCreateRequestProcess->getOrderIdBySecretKey($secretKey))) {
            $this->messageManager->addWarningMessage(__('Order Not Found'));

            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() .'/guest/login'
            );
        }

        if (!($returnOrder = $this->createReturnProcessor->process($orderId))) {
            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() .'/guest/login')
            );
        }

        $isReturnAvailable = true;
        $data['order_store_id'] = (int)$returnOrder->getOrder()->getStoreId();
        foreach ($this->typeValidatorProvider->getValidators() as $validator) {
            $isReturnAvailable = $validator->isValid($data);
            if ($isReturnAvailable === false) {
                break;
            }
        }

        if (!$isReturnAvailable) {
            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() .'/guest/login')
            );
        }

        $this->registry->register(
            \Amasty\Rma\Controller\RegistryConstants::CREATE_RETURN_ORDER,
            $returnOrder
        );

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(
            __('New Return for Order #%1', $returnOrder->getOrder()->getIncrementId())
        );

        return $resultPage;
    }
}
