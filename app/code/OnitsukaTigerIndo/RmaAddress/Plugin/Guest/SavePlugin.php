<?php

namespace OnitsukaTigerIndo\RmaAddress\Plugin\Guest;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Api\GuestCreateRequestProcessInterface;
use Amasty\Rma\Controller\FrontendRma;
use Amasty\Rma\Controller\Guest\Save;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Cookie\HashChecker;
use Closure;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTigerKorea\RmaAddress\Helper\Data;

class SavePlugin extends Save
{
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var GuestCreateRequestProcessInterface
     */
    private GuestCreateRequestProcessInterface $guestCreateRequestProcess;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var CustomerRequestRepositoryInterface
     */
    private CustomerRequestRepositoryInterface $requestRepository;

    /**
     * @var FrontendRma
     */
    private FrontendRma $frontendRma;

    /**
     * @var HashChecker
     */
    private HashChecker $hashChecker;

    /**
     * @var Context
     */
    private Context $context;

    protected $redirect;

    protected $resourceConnection;

    /**
     * @param ManagerInterface $messageManager
     * @param Data $dataHelper
     * @param RedirectInterface $redirect
     * @param OrderRepositoryInterface $orderRepository
     * @param Registry $registry
     * @param GuestCreateRequestProcessInterface $guestCreateRequestProcess
     * @param ConfigProvider $configProvider
     * @param CustomerRequestRepositoryInterface $requestRepository
     * @param FrontendRma $frontendRma
     * @param HashChecker $hashChecker
     * @param ResourceConnection $resourceConnection
     * @param Context $context
     */
    public function __construct(
        ManagerInterface                   $messageManager,
        Data                               $dataHelper,
        RedirectInterface                  $redirect,
        OrderRepositoryInterface           $orderRepository,
        Registry                           $registry,
        GuestCreateRequestProcessInterface $guestCreateRequestProcess,
        ConfigProvider                     $configProvider,
        CustomerRequestRepositoryInterface $requestRepository,
        FrontendRma                        $frontendRma,
        HashChecker                        $hashChecker,
        ResourceConnection                 $resourceConnection,
        Context                            $context
    ) {
        parent::__construct(
            $orderRepository,
            $registry,
            $guestCreateRequestProcess,
            $configProvider,
            $requestRepository,
            $frontendRma,
            $hashChecker,
            $context
        );
        $this->messageManager = $messageManager;
        $this->dataHelper = $dataHelper;
        $this->redirect = $redirect;
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
        $this->guestCreateRequestProcess = $guestCreateRequestProcess;
        $this->configProvider = $configProvider;
        $this->requestRepository = $requestRepository;
        $this->frontendRma = $frontendRma;
        $this->hashChecker = $hashChecker;
        $this->resourceConnection = $resourceConnection;
        $this->context = $context;
    }

    /**
     * Around Plugin For Execute
     *
     * @param Save $subject
     * @param Closure $proceed
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundExecute(Save $subject, Closure $proceed)
    {
        if ($this->dataHelper->enableShowAddressRMA()) {
            $rmaAddress = $subject->getRequest()->getParam('rma_address');
            if (!$rmaAddress) {
                $this->messageManager->addErrorMessage(__('Please select RMA address.'));
                return $subject->getResponse()->setRedirect($this->redirect->getRefererUrl());
            }
        }

        if (!($secretKey = $this->getRequest()->getParam('secret'))) {
            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/guest/login'
            );
        }

        $orderId = $this->guestCreateRequestProcess->getOrderIdBySecretKey($secretKey);

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Exception $exception) {
            $orderId = false;
        }

        if (!$orderId) {
            $this->messageManager->addWarningMessage('Order Not Found');

            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/guest/login'
            );
        }

        $items = $this->getRequest()->getParam('items');
        if (!is_array($items) || !$items) {
            $this->messageManager->addWarningMessage(__('Items were not selected'));

            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/guest/newreturn',
                ['secret' => $secretKey]
            );
        }

        if ($this->configProvider->isReturnPolicyEnabled() && !$this->getRequest()->getParam('rmapolicy')) {
            $this->messageManager->addWarningMessage(__('You didn\'t agree to Privacy policy'));

            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/guest/newreturn',
                ['secret' => $secretKey]
            );
        }

        $request = $this->requestRepository->create(
            $this->frontendRma->processNewRequest(
                $this->requestRepository,
                $order,
                $this->getRequest()
            ),
            $secretKey
        );

        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('amasty_rma_request');
        $select = $connection->select()
            ->from($tableName, ['request_id', 'customer_name', 'url_hash'])
            ->where('order_id = ?', $orderId);
        $result = $connection->fetchAll($select);

        $files = [];
        if ($jsonFiles = $this->getRequest()->getParam('attach-files')) {
            $files = json_decode($jsonFiles, true);
        }
        if (!empty($comment = $this->getRequest()->getParam('comment')) || !(empty($files))) {
            $this->frontendRma->saveNewReturnMessage($result[0], $comment, $files);
        }

        $this->hashChecker->setHash($result[0]['url_hash']);
        $this->guestCreateRequestProcess->deleteBySecretKey($secretKey);

        return $this->resultRedirectFactory->create()->setPath(
            $this->configProvider->getUrlPrefix() . '/guest/view',
            ['request' => $result[0]['url_hash']]
        );
    }
}
