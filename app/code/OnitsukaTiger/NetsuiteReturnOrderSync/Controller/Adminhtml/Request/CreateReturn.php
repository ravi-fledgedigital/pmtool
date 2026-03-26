<?php
declare(strict_types=1);
namespace OnitsukaTiger\NetsuiteReturnOrderSync\Controller\Adminhtml\Request;

use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Api\CreateReturnProcessorInterface;
use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestCustomFieldInterfaceFactory;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Observer\RmaEventNames;
use Amasty\Rma\Utils\FileUpload;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth\Session;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data;
use Psr\Log\LoggerInterface;

class CreateReturn extends \Amasty\Rma\Controller\Adminhtml\Request\CreateReturn {

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var CreateReturnProcessorInterface
     */
    private $createReturnProcessor;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var ChatRepositoryInterface
     */
    private $chatRepository;

    /**
     * @var Session
     */
    private $adminSession;

    /**
     * @var Data
     */
    protected $rmaHelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $rmaHelper
     * @param RequestRepositoryInterface $requestRepository
     * @param ConfigProvider $configProvider
     * @param CreateReturnProcessorInterface $createReturnProcessor
     * @param LoggerInterface $logger
     * @param ChatRepositoryInterface $chatRepository
     * @param Session $adminSession
     * @param Action\Context $context
     * @param RequestCustomFieldInterfaceFactory $customFieldFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Data $rmaHelper,
        RequestRepositoryInterface $requestRepository,
        ConfigProvider $configProvider,
        CreateReturnProcessorInterface $createReturnProcessor,
        LoggerInterface $logger,
        ChatRepositoryInterface $chatRepository,
        Session $adminSession,
        Action\Context $context,
        RequestCustomFieldInterfaceFactory $customFieldFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->rmaHelper = $rmaHelper;
        $this->requestRepository = $requestRepository;
        $this->createReturnProcessor = $createReturnProcessor;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->eventManager = $context->getEventManager() ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Event\ManagerInterface::class);
        $this->chatRepository = $chatRepository;
        $this->adminSession = $adminSession;
        parent::__construct(
            $requestRepository,
            $configProvider,
            $createReturnProcessor,
            $logger,
            $chatRepository,
            $adminSession,
            $context,
            $customFieldFactory
        );
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam(RequestInterface::ORDER_ID);
        $items = $this->getRequest()->getParam('return_items');
        $jsonFiles = $this->getRequest()->getParam('files');

        if ($this->getRequest()->getParams() && $orderId && $items) {
            if ($returnOrder = $this->createReturnProcessor->process($orderId, true)) {
                $request = $this->requestRepository->getEmptyRequestModel();
                $request->setNote($this->getRequest()->getParam(RequestInterface::NOTE, ''))
                    ->setStatus($this->getRequest()->getParam(RequestInterface::STATUS))
                    ->setCustomerId($returnOrder->getOrder()->getCustomerId())
                    ->setManagerId($this->getRequest()->getParam(RequestInterface::MANAGER_ID))
                    ->setOrderId($orderId)
                    ->setStoreId($returnOrder->getOrder()->getStoreId())
                    ->setCustomerName(
                        $returnOrder->getOrder()->getBillingAddress()->getFirstname()
                        . ' ' . $returnOrder->getOrder()->getBillingAddress()->getLastname()
                    );

                if ($customFields = $this->configProvider->getCustomFields($request->getStoreId())) {
                    $customFieldsData = [];
                    $formCustomFields = $this->getRequest()->getParam(RequestInterface::CUSTOM_FIELDS, []);

                    foreach ($customFields as $code => $label) {
                        if (!empty($formCustomFields[$code])) {
                            $customFieldsData[$code] = $formCustomFields[$code];
                        }
                    }

                    $request->setCustomFields($customFieldsData);
                }

                $items = $this->processItems($returnOrder->getItems(), $items);

                if ($items) {
                    $request->setRequestItems($items);

                    try {
                        $this->eventManager->dispatch(
                            RmaEventNames::BEFORE_CREATE_RMA_BY_MANAGER,
                            ['request' => $request]
                        );

                        $message = $this->getRequest()->getParam(MessageInterface::MESSAGE);

                        if (!empty($message) || $jsonFiles) {
                            $this->sendReturnMessage($request, $message, $jsonFiles);
                        }

                        $rmaAlgorithmEnabled = $this->rmaHelper->getRmaAlgorithmConfig('enabled', $request->getStoreId());
                        if (!$rmaAlgorithmEnabled) {
                            $order = $this->orderRepository->get($orderId);
                            $request->setShipmentIncrementId($order->getShipmentsCollection()->getFirstItem()->getData('increment_id'));
                            $this->requestRepository->save($request);
                            $this->eventManager->dispatch(
                                RmaEventNames::RMA_CREATED_BY_MANAGER,
                                ['request' => $request]
                            );
                        }

                        return $this->_redirect(
                            'amrma/request/manage'
                        );
                    } catch (\Exception $e) {
                        $this->logger->critical($e->getMessage());
                    }
                }
            }
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    /**
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @param string $comment
     */
    private function sendReturnMessage($request, $comment, $jsonFiles)
    {
        $message = $this->chatRepository->getEmptyMessageModel();
        $message->setIsRead(0)
            ->setMessage($comment)
            ->setCustomerId(0)
            ->setName($this->adminSession->getName())
            ->setRequestId($request->getRequestId())
            ->setIsManager(true);

        if ($jsonFiles) {
            $files = json_decode($jsonFiles, true);
            $messageFiles = [];
            foreach ($files as $file) {
                $messageFile = $this->chatRepository->getEmptyMessageFileModel();
                $messageFile->setFilepath($file[FileUpload::FILEHASH])
                    ->setFilename($file[FileUpload::FILENAME]);
                $messageFiles[] = $messageFile;
            }
            $message->setMessageFiles($messageFiles);
        }

        try {
            $this->chatRepository->save($message, false);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            null;
        }
    }
}
