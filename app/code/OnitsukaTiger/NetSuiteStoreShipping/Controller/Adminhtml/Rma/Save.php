<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Rma;

use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Model\Chat\ResourceModel\CollectionFactory as MessageCollectionFactory;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\OptionSource\Grid;
use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Observer\RmaEventNames;
use Amasty\Rma\Utils\Email;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\User\Model\UserFactory;

class Save extends \Amasty\Rma\Controller\Adminhtml\Request\Save
{

    /**
     * @var RequestRepositoryInterface
     */
    private $repository;

    /**
     * @var MessageCollectionFactory
     */
    private $messageCollectionFactory;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var EmailRequest
     */
    private $emailRequest;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var \OnitsukaTiger\Logger\StoreShipping\Logger
     */
    protected $logger;

    public function __construct(
        Context $context,
        RequestRepositoryInterface $repository,
        MessageCollectionFactory $messageCollectionFactory,
        DataPersistorInterface $dataPersistor,
        EmailRequest $emailRequest,
        ConfigProvider $configProvider,
        DataObject $dataObject,
        ScopeConfigInterface $scopeConfig,
        StatusRepositoryInterface $statusRepository,
        Email $email,
        Grid $grid,
        ManagerInterface $eventManager,
        UserFactory $userFactory,
        \OnitsukaTiger\Logger\StoreShipping\Logger $logger
    )
    {
        $this->repository = $repository;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->emailRequest = $emailRequest;
        $this->configProvider = $configProvider;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->userFactory = $userFactory;
        $this->logger = $logger;
        parent::__construct($context, $repository, $messageCollectionFactory, $dataPersistor, $emailRequest, $configProvider, $dataObject, $scopeConfig, $statusRepository, $email, $grid);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('OnitsukaTiger_NetSuiteStoreShipping::manage');
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->getRequest()->getParams()) {
            try {
                if (!($requestId = (int)$this->getRequest()->getParam(RegistryConstants::REQUEST_ID))) {
                    return $this->_redirect('*/*/pending');
                }

                $model = $this->repository->getById($requestId);
                $managerCode = $this->getManagerCode($model);
                $this->processItems($model, $this->getRequest()->getParam('return_items'));
                $originalStatus = $model->getStatus();

                if ($status = $this->getRequest()->getParam(RequestInterface::STATUS)) {
                    $model->setStatus($status);
                }

                $model->setManagerId($this->getRequest()->getParam(RequestInterface::MANAGER_ID));

                if ($note = $this->getRequest()->getParam(RequestInterface::NOTE)) {
                    $model->setNote($note);
                }

                $origStatus = (int)$model->getOrigData(RequestInterface::STATUS);
                $this->repository->save($model);
                $this->eventManager->dispatch(
                    RmaEventNames::RMA_SAVED_BY_MANAGER,
                    ['request' => $model]
                );

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

                $this->messageManager->addSuccessMessage(__('You saved the return request.'));

                if ($this->getRequest()->getParam('back')) {

                    return $this->_redirect('*/*/view', [RegistryConstants::REQUEST_ID => $model->getId()]);
                }
            } catch (LocalizedException $e) {
                $this->logger->error($e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
                //TODO $this->dataPersistor->set(RegistryConstants::REQ, $data);

                return $this->_redirect('*/*/view', [RegistryConstants::REQUEST_ID => $requestId]);
            }
        }

        return $this->_redirect("*/*/manage", ['manager_code' => $managerCode]);
    }

    /**
     * @param $model
     * @return mixed
     */
    public function getManagerCode($model)
    {
        $user = $this->userFactory->create()->load($model->getManagerId());
        return $user->getUserName();
    }

}
