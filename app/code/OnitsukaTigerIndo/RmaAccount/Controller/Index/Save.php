<?php

namespace OnitsukaTigerIndo\RmaAccount\Controller\Index;

use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory as RmaCollectionFactory;
use Amasty\Rma\Model\Status\ResourceModel\CollectionFactory as RmaStatusCollectionFactory;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTigerIndo\RmaAccount\Model\ResourceModel\RmaAccount\CollectionFactory;
use OnitsukaTigerIndo\RmaAccount\Model\RmaAccountFactory;
use Psr\Log\LoggerInterface;

class Save extends \Magento\Framework\App\Action\Action
{

    public const EMAIL_TEMPLATE ="amrma/email/admin_template_bank_details";
    /**
     * @var RmaAccount
     */
    protected $_rmaAccount;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $resource;

    /**
     * @var OrderInterfaceFactory
     */
    private OrderInterfaceFactory $orderFactory;

    /**
     * @var RmaCollectionFactory
     */
    private RmaCollectionFactory $rmaCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var TransportBuilder
     */
    private TransportBuilder $transportBuilder;

    /**
     * @var StateInterface
     */
    private StateInterface $inlineTranslation;

    /**
     * @var RmaStatusCollectionFactory
     */
    private RmaStatusCollectionFactory $status;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Emulation
     */
    private Emulation $emulation;

    /**
     * @param Context $context
     * @param RmaAccountFactory $rmaAccount
     * @param UploaderFactory $uploaderFactory
     * @param AdapterFactory $adapterFactory
     * @param CollectionFactory $resource
     * @param OrderInterfaceFactory $orderFactory
     * @param Filesystem $filesystem
     * @param RmaCollectionFactory $rmaCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigProvider $configProvider
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param RmaStatusCollectionFactory $status
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     */
    public function __construct(
        Context                    $context,
        RmaAccountFactory          $rmaAccount,
        UploaderFactory            $uploaderFactory,
        AdapterFactory             $adapterFactory,
        CollectionFactory          $resource,
        OrderInterfaceFactory      $orderFactory,
        Filesystem                 $filesystem,
        RmaCollectionFactory       $rmaCollectionFactory,
        ScopeConfigInterface       $scopeConfig,
        ConfigProvider             $configProvider,
        TransportBuilder           $transportBuilder,
        StateInterface             $inlineTranslation,
        RmaStatusCollectionFactory $status,
        LoggerInterface            $logger,
        StoreManagerInterface $storeManager,
        Emulation $emulation
    ) {
        $this->_rmaAccount = $rmaAccount;
        $this->uploaderFactory = $uploaderFactory;
        $this->adapterFactory = $adapterFactory;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->orderFactory = $orderFactory;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->configProvider = $configProvider;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->status = $status;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        parent::__construct($context);
    }

    /**
     * Execute Method
     *
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws Exception
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_number');
        $requestId = $this->getRequest()->getParam('request_id');

        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        $orderCustomerId = $order->getCustomerId();
        $orderIdCustomerName = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();

        $data = $this->resource->create()->getData();
        $orderIncrementId = [];
        foreach ($data as $item) {
            $orderIncrementId[] = $item['order_number'];
        }
        if (in_array($orderId, $orderIncrementId)) {
            $this->messageManager
                ->addErrorMessage(__('Requested Order Number Already Exists For RMA Bank Account Details.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('rma/account/history');
            return $resultRedirect;
        }

        $data = $this->getRequest()->getParams();
        $missingFields = [];
        if (trim($this->getRequest()->getParam('order_number')) === '') {
            $missingFields[] = __('Order Number');
        }
        if (trim($this->getRequest()->getParam('acc_holder_name')) === '') {
            $missingFields[] = __('Account Holder Name');
        }
        if (trim($this->getRequest()->getParam('bank_name')) === '') {
            $missingFields[] = __('Bank Name');
        }
        if (trim($this->getRequest()->getParam('account_number')) === '') {
            $missingFields[] = __('Account Number');
        }
        if (trim($this->getRequest()->getParam('ifsc_code')) === '') {
            $missingFields[] = __('IFSC code');
        }

        if (!empty($missingFields)) {
            foreach ($missingFields as $field) {
                $this->messageManager->addNoticeMessage(__('%1 is missing', $field));
            }
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('rma/account/history');
            return $resultRedirect;
        }
        $rmaAccount = $this->_rmaAccount->create();
        $rmaAccount->setData($data);

        if ($rmaAccount->save()) {
            $this->messageManager->addSuccessMessage(__('You saved the RMA Account Details form.'));
            $bankDeatilsId = $rmaAccount->getRmaId();
            $rmaCollection = $this->rmaCollectionFactory->create()
                ->addFieldToFilter('request_id', $requestId)
                ->addFieldToFilter('customer_id', $orderCustomerId);

            $rmaStatus = $rmaCollection->getFirstItem()->getStatus();
            $rmaStatusLabelCollection = $this->status->create();
            $rmaStatusLabelCollection->addFieldToFilter('status_id', ['eq' => $rmaStatus]);
            $rmaStatusLabel = $rmaStatusLabelCollection->getFirstItem()->getTitle();
            $sendTo = $this->configProvider->getAdminEmails();
            $storeId = $this->storeManager->getStore()->getId();
            $this->emulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
            try {
                $this->inlineTranslation->suspend();
                $sender = [
                    'name' => $this->scopeConfig->getValue(
                        'trans_email/ident_support/name',
                        ScopeInterface::SCOPE_STORE
                    ),
                    'email' => $this->scopeConfig->getValue(
                        'trans_email/ident_support/email',
                        ScopeInterface::SCOPE_STORE
                    ),
                ];

                $template = $this->scopeConfig->getValue(
                    self::EMAIL_TEMPLATE,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
                $transport = $this->transportBuilder
                    ->setTemplateIdentifier($template)
                    ->setTemplateOptions(
                        [
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                        ]
                    )
                    ->setTemplateVars([
                        'customer_id' => $orderCustomerId,
                        'customer_name' => $orderIdCustomerName,
                        'rma_id' => $requestId,
                        'status' => $rmaStatusLabel,
                        'banck_details_id' => $bankDeatilsId
                    ])
                    ->setFrom($sender)
                    ->addTo($sendTo)
                    ->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
            $this->emulation->stopEnvironmentEmulation();

        } else {
            $this->messageManager->addErrorMessage(__('RMA Account Details form was not saved.'));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('rma/account/history');
        return $resultRedirect;
    }
}
