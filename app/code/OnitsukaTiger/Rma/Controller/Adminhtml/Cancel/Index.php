<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Rma\Controller\Adminhtml\Cancel;

use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Controller\Adminhtml\Request\Save;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Model\Chat\ResourceModel\CollectionFactory as MessageCollectionFactory;
use Amasty\Rma\Model\OptionSource\Grid;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Utils\Email;
use Magento\Backend\App\Action\Context;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Observer\RmaEventNames;
use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use \OnitsukaTiger\Rma\Helper\Data as HelperRma;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\CancelReturn;
use OnitsukaTigerKorea\Rma\Model\ReturnInfoFactory;
use OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo;
use Magento\Sales\Model\Order\ItemRepository;

class Index extends Save
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var RequestRepositoryInterface
     */
    protected $repository;

    /**
     * @var CancelReturn
     */
    protected $cancelReturn;
    /**
     * @var \OnitsukaTigerKorea\Rma\Model\ReturnInfoFactory
     */
    private $returnInfoFactory;
    /**
     * @var \OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo
     */
    private $returnInfoResource;
    /**
     * @var \Magento\Sales\Model\Order\ItemRepository
     */
    private $itemRepository;

    protected $dataPersistor;

    protected $statusRepository;

    protected $messageCollectionFactory;

    protected $email;

    protected $emailRequest;

    protected $configProvider;

    protected $scopeConfig;

    protected $dataObject;


    /**
     * @param Context $context
     * @param RequestRepositoryInterface $repository
     * @param MessageCollectionFactory $messageCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param EmailRequest $emailRequest
     * @param ConfigProvider $configProvider
     * @param DataObject $dataObject
     * @param ScopeConfigInterface $scopeConfig
     * @param StatusRepositoryInterface $statusRepository
     * @param Email $email
     * @param Grid $grid
     * @param CancelReturn $cancelReturn
     * @param ReturnInfoFactory $returnInfoFactory
     * @param ReturnInfo $returnInfoResource
     * @param ItemRepository $itemRepository
     */
    public function __construct(
        Context                    $context,
        RequestRepositoryInterface $repository,
        MessageCollectionFactory   $messageCollectionFactory,
        DataPersistorInterface     $dataPersistor,
        EmailRequest               $emailRequest,
        ConfigProvider             $configProvider,
        DataObject                 $dataObject,
        ScopeConfigInterface       $scopeConfig,
        StatusRepositoryInterface  $statusRepository,
        Email                      $email,
        Grid                       $grid,
        CancelReturn               $cancelReturn,
        ReturnInfoFactory          $returnInfoFactory,
        ReturnInfo                 $returnInfoResource,
        ItemRepository             $itemRepository
    ) {
        parent::__construct(
            $context,
            $repository,
            $messageCollectionFactory,
            $dataPersistor,
            $emailRequest,
            $configProvider,
            $dataObject,
            $scopeConfig,
            $statusRepository,
            $email,
            $grid
        );
        $this->dataPersistor = $dataPersistor;
        $this->repository = $repository;
        $this->statusRepository = $statusRepository;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->email = $email;
        $this->emailRequest = $emailRequest;
        $this->configProvider = $configProvider;
        $this->scopeConfig = $scopeConfig;
        $this->dataObject = $dataObject;
        $this->cancelReturn = $cancelReturn;
        $this->eventManager = $context->getEventManager() ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Event\ManagerInterface::class);
        $this->returnInfoFactory = $returnInfoFactory;
        $this->returnInfoResource = $returnInfoResource;
        $this->itemRepository = $itemRepository;
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {

        if ($this->getRequest()->getParams()) {
            try {
                $requestId = $this->getRequest()->getParam(RegistryConstants::REQUEST_ID);
                if (!$requestId) {
                    return $this->_redirect('*/*/pending');
                }
                $this->saveReturnCancelInfo($requestId);
                $model = $this->repository->getById($requestId);

                //Set Cancel By Admin
                $cancelStatusByAdmin = $this->scopeConfig->getValue('amrma/general/admin_canceled_status');
                $model->setStatus($cancelStatusByAdmin);

                $model->setManagerId($this->getRequest()->getParam(RequestInterface::MANAGER_ID));
                $note = __('Cancelled by Admin (Magento)');
                $model->setNote($note);

                $origStatus = (int)$model->getOrigData(RequestInterface::STATUS);
                $this->repository->save($model);

                /*$this->eventManager->dispatch(
                    RmaEventNames::RMA_SAVED_BY_MANAGER,
                    ['request' => $model]
                );*/
                if ($origStatus === $model->getStatus()
                    && $this->configProvider->isNotifyCustomerAboutNewMessage($model->getStoreId())
                ) {
                    $messageCollection = $this->messageCollectionFactory->create();
                    $messagesCount = $messageCollection
                        ->addFieldToFilter(MessageInterface::REQUEST_ID, $model->getRequestId())
                        ->addFieldToFilter(
                            MessageInterface::MESSAGE_ID,
                            ['gt' => $this->getRequest()->getParam('last_message_id', 0)]
                        )->addFieldToFilter(MessageInterface::IS_MANAGER, 1)
                        ->addFieldToFilter(MessageInterface::IS_READ, 0)
                        ->getSize();

                    if ($messagesCount) {
                        $emailRequest = $this->emailRequest->parseRequest($model);
                        $storeId = $model->getStoreId();
                        $this->email->sendEmail(
                            $emailRequest->getCustomerEmail(),
                            $storeId,
                            $this->scopeConfig->getValue(
                                ConfigProvider::XPATH_NEW_MESSAGE_TEMPLATE,
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $storeId
                            ),
                            ['email_request' => $emailRequest],
                            \Magento\Framework\App\Area::AREA_FRONTEND,
                            $this->configProvider->getChatSender($storeId)
                        );
                    }
                }

                $this->messageManager->addSuccessMessage(__('You canceled the return request.'));
                if ($model->getStoreId() == \OnitsukaTiger\Store\Model\Store::KO_KR) {
                    $this->cancelReturn->execute($model);
                }
                return $this->_redirect('amrma/request/view', [RegistryConstants::REQUEST_ID => $requestId]);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                //TODO $this->dataPersistor->set(RegistryConstants::REQ, $data);

                return $this->_redirect('*/*/view', [RegistryConstants::REQUEST_ID => $requestId]);
            }
        }
    }

    /**
     * @param $requestId
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveReturnCancelInfo($requestId)
    {
        $model = $this->repository->getById($requestId);
        $trackingNoId  = 0;
        foreach ($model->getTrackingNumbers() as $no) {
            $trackingNoId = $no->getTrackingNumber();
        }
        $productInfo = [];
        foreach ($model->getRequestItems() as $requestItem) {
            $orderItem = $this->itemRepository->get($requestItem->getOrderItemId());
            $qty = $requestItem->getQty();
            $productInfo[] = [
                'qty' => $qty,
                'sku' => $orderItem->getSku()
            ];
        }
        $returnInfo = $this->returnInfoFactory->create();
        $returnInfo->setData('order_id', $model->getOrderId());
        $returnInfo->setData('return_id', $requestId);
        $returnInfo->setData('tracking_no', $trackingNoId);
        $returnInfo->setData('product_info', json_encode($productInfo));
        $this->returnInfoResource->save($returnInfo);
    }
}
