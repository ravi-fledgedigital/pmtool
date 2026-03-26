<?php

namespace OnitsukaTigerIndo\RmaAddress\Plugin\Account;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Controller\Account\Save;
use Amasty\Rma\Controller\FrontendRma;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory as RequestModel;
use Closure;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTigerKorea\RmaAddress\Block\Returns\AddressOption;
use OnitsukaTigerKorea\RmaAddress\Helper\Data;
use OnitsukaTigerKorea\RmaAddress\Plugin\Account\RequestFactory;

class SavePlugin extends Save
{
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var Registry
     */
    private Registry $registry;

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
     * @var RequestFactory
     */
    private RequestFactory $requestFactory;

    protected $requestData;

    protected $addressOption;
    /**
     * @var Context
     */
    private Context $context;

    protected $resourceConnection;

    protected $resultRedirectFactory;

    protected $messageManager;

    /**
     * @param Session $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Registry $registry
     * @param ConfigProvider $configProvider
     * @param CustomerRequestRepositoryInterface $requestRepository
     * @param FrontendRma $frontendRma
     * @param RequestModel $requestData
     * @param ManagerInterface $messageManager
     * @param Data $dataHelper
     * @param RedirectInterface $redirect
     * @param ResourceConnection $resourceConnection
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param AddressOption $addressOption
     * @param Context $context
     */
    public function __construct(
        Session                            $customerSession,
        OrderRepositoryInterface           $orderRepository,
        Registry                           $registry,
        ConfigProvider                     $configProvider,
        CustomerRequestRepositoryInterface $requestRepository,
        FrontendRma                        $frontendRma,
        RequestModel                       $requestData,
        ManagerInterface                   $messageManager,
        Data                               $dataHelper,
        RedirectInterface                  $redirect,
        ResourceConnection                 $resourceConnection,
        RequestInterface                   $request,
        RedirectFactory                    $resultRedirectFactory,
        AddressOption $addressOption,
        Context                            $context
    ) {
        $this->dataHelper = $dataHelper;
        $this->messageManager = $messageManager;
        $this->redirect = $redirect;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
        $this->configProvider = $configProvider;
        $this->requestRepository = $requestRepository;
        $this->frontendRma = $frontendRma;
        $this->requestData = $requestData;
        $this->resourceConnection = $resourceConnection;
        $this->request = $request;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->addressOption = $addressOption;
        parent::__construct($customerSession, $orderRepository, $registry, $configProvider, $requestRepository, $frontendRma, $context);
    }

    /**
     * Around Plugin
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
        $rmaAddress = $subject->getRequest()->getParam('rma_address');
        if ($rmaAddress) {
            $address = $this->addressOption->getAddressOptions();
            if (!in_array((int)$rmaAddress, array_keys($address))) {
                $this->messageManager->addErrorMessage(__('Please select Valid address.'));
                return $subject->getResponse()->setRedirect($this->redirect->getRefererUrl());
            }
        }

        if (!($customerId = $this->customerSession->getCustomerId())) {
            return $this->resultRedirectFactory->create()->setPath('customer/account/login');
        }

        $orderId = (int)$this->request->getParam('order');

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Exception $exception) {
            $orderId = false;
        }

        if (!$orderId) {
            $this->messageManager->addWarningMessage(__('Order is not set'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl($this->configProvider->getUrlPrefix() . '/account/history')
            );
        }

        $items = $this->request->getParam('items');
        if (!is_array($items) || !$items) {
            $this->messageManager->addWarningMessage(__('Items were not selected'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl(
                    $this->configProvider->getUrlPrefix() . '/account/newreturn/order/' . $orderId
                )
            );
        }

        if ($this->configProvider->isReturnPolicyEnabled() && !$this->request->getParam('rmapolicy')) {
            $this->messageManager->addWarningMessage(__('You didn\'t agree to Privacy policy'));

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_url->getUrl(
                    $this->configProvider->getUrlPrefix() . '/account/newreturn/order/' . $orderId
                )
            );
        }

        $request = $this->requestRepository->create(
            $this->frontendRma->processNewRequest(
                $this->requestRepository,
                $order,
                $this->request
            )
        );

        $requestData = $this->requestData->create()->load();
        $collection = $requestData->addFieldToSelect(['request_id', 'customer_name'])
            ->addFieldToFilter('order_id', $orderId);
        $requestData = $collection->getData();
        $files = [];
        if ($jsonFiles = $this->request->getParam('attach-files')) {
            $files = json_decode($jsonFiles, true);
        }
        if (!empty($requestData)) {
            $lastRequestData = end($requestData);
            if (!empty($comment = $this->request->getParam('comment')) || !empty($files)) {
                $this->frontendRma->saveNewReturnMessage($lastRequestData, $comment, $files);
            }
            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/account/view',
                ['request' => $lastRequestData['request_id']]
            );
        }
    }
}