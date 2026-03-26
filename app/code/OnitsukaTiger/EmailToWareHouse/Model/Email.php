<?php
namespace OnitsukaTiger\EmailToWareHouse\Model;

use Exception;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\NetSuiteStoreShipping\Model\Pdf\PackingList;

/**
 * Class Email
 * @package OnitsukaTiger\EmailToWareHouse\Model
 */
class Email
{

    /**
     * Invoice and dispatch file name
     */
    const INVOICE_PDF_NAME = 'Invoice.pdf';
    const DISPATCH_PDF_NAME = 'Dispatch.pdf';
    const PACKING_LIST_PDF_NAME = 'PackingList.pdf';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    protected $senderResolver;

    /**
     * @var \OnitsukaTiger\EmailToWareHouse\Model\Email\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\Invoice
     */
    protected $pdfInvoiceModel;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\Shipment
     */
    protected $pdfShipmentModel;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var \OnitsukaTiger\Shipment\Model\ShipmentAttributes
     */
    protected $shipmentAttributesFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var PackingList
     */
    protected $packingListPdf;

    /**
     * @var \OnitsukaTiger\Logger\EmailToWareHouse\Logger
     */
    protected $logger;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $orderRepository
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver
     * @param \OnitsukaTiger\EmailToWareHouse\Model\Email\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Framework\Escaper $escaper
     * @param \Amasty\PDFCustom\Model\Order\Pdf\Invoice $pdfInvoiceModel
     * @param \Amasty\PDFCustom\Model\Order\Pdf\Shipment $pdfShipmentModel
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \OnitsukaTiger\Shipment\Model\ShipmentAttributes $shipmentAttributesFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PackingList $packingListPdf
     * @param \OnitsukaTiger\Logger\EmailToWareHouse\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $orderRepository,
        \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver,
        \OnitsukaTiger\EmailToWareHouse\Model\Email\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Escaper $escaper,
        \Amasty\PDFCustom\Model\Order\Pdf\Invoice $pdfInvoiceModel,
        \Amasty\PDFCustom\Model\Order\Pdf\Shipment $pdfShipmentModel,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \OnitsukaTiger\Shipment\Model\ShipmentAttributes $shipmentAttributesFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        PackingList $packingListPdf,
        \OnitsukaTiger\Logger\EmailToWareHouse\Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->senderResolver = $senderResolver;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->fileSystem = $fileSystem;
        $this->escaper = $escaper;
        $this->pdfInvoiceModel = $pdfInvoiceModel;
        $this->pdfShipmentModel = $pdfShipmentModel;
        $this->shipmentRepository = $shipmentRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->shipmentAttributesFactory = $shipmentAttributesFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->packingListPdf = $packingListPdf;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     * @throws \Zend_Pdf_Exception
     */
    public function sendEmailToWareHouse($shipment)
    {
        $logger = $this->logger;

        $order = $shipment->getOrder();
        $orderId = (int) $order->getId();

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/EmailWareHouseCustom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('-----Logger Start-----');

        $websiteId = (int) $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
        $websiteName = $this->storeManager->getWebsite($websiteId)->getName();
        if (!$this->isEnabled(ScopeInterface::SCOPE_WEBSITE, $websiteId)) {
            $logger->info('Module disabled from backend.');
            $logger->debug('Module OnitsukaTiger_EmailToWareHouse is disabled on '. $websiteName . ' website!');
            return false;
        }
        $logger->info('Module enabled from backend.');
        $invoice = $this->getInvoiceDataByOrderId($orderId) ? $this->getInvoiceDataByOrderId($orderId) : [];
        $logger->info('Order ID: ' . $orderId);
        $logger->info('Shipment ID: ' . $shipment->getId());
        $pdfInvoiceContent = $this->pdfInvoiceModel->getPdf($invoice)->render();

        $shipments = [$shipment];
        $pdfShipmentContent = $this->pdfShipmentModel->getPdf($shipments)->render();
        $pdfPackingListContent = $this->packingListPdf->getPdf($shipments)->render();

        $sender = $this->getSender(ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $recipient = $this->getRecipient(ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $ccEmail = $this->getCcEmail(ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $bccEmail = $this->getBccEmail(ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $subject = $this->getSubject(ScopeInterface::SCOPE_WEBSITE, $websiteId);

        $logger->info('Sender Details: ' . json_encode($sender));
        $logger->info('Recipient Details: ' . json_encode($recipient));
        $logger->info('Cc Email: ' . json_encode($ccEmail));
        $logger->info('Bcc Email: ' . json_encode($bccEmail));
        $logger->info('Subject: ' . json_encode($subject));


        $templateVars = ['order' => $order, 'invoice' => $invoice];
        if ($this->getFulfillmentId($shipment) != ''){
            $fulfillmentId = $this->getFulfillmentId($shipment);
            $templateVars['fulfillment_id'] = $fulfillmentId;
            $templateVars['subject'] = $subject . ' - Order #' . $order->getIncrementId() . ' Fullfillment #' .$fulfillmentId . ' Dispatch and Invoice';
        } else{
            $templateVars['subject'] = $subject . ' - Order #' . $order->getIncrementId() . ' Dispatch and Invoice';
        }
        $logger->info('Template Vars: ' . json_encode($templateVars));
        $templateIdentifier = $this->getTemplateIdentifier(ScopeInterface::SCOPE_WEBSITE, $websiteId);

        try {
            $transport = $this->transportBuilder->setTemplateIdentifier($templateIdentifier)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($recipient)
                ->addCc($ccEmail)
                ->addBcc($bccEmail)
                ->addAttachment($pdfInvoiceContent, self::INVOICE_PDF_NAME, 'application/pdf')
                ->addAttachment($pdfShipmentContent, self::DISPATCH_PDF_NAME, 'application/pdf')
                ->addAttachment($pdfPackingListContent, self::PACKING_LIST_PDF_NAME, 'application/pdf')
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $logger->info('-----Logger End Success');
            return true;
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $logger->info($e);
            $logger->info('-----Logger End  Failure');
            return false;
        }
    }

    /**
     * Check status enabled/disabled of module
     * @param $scope
     * @param $scopeId
     * @return bool
     */
    public function isEnabled($scope, $scopeId)
    {
        if (!$this->scopeConfig->getValue('sendEmailWareHouse/general/enabled', $scope, $scopeId)) {
            return false;
        }
        return true;
    }

    /**
     * Get Sender function
     * @param $scope
     * @param $scopeId
     * @return array
     * @throws \Magento\Framework\Exception\MailException
     */
    public function getSender($scope, $scopeId)
    {
        $senderEmail = $this->scopeConfig->getValue('sendEmailWareHouse/general/senderEmail', $scope, $scopeId);
        return $this->senderResolver->resolve($senderEmail);
    }

    /**
     * Get Recipient function
     * @param $scope
     * @param $scopeId
     * @return array
     */
    public function getRecipient($scope, $scopeId)
    {
        $recipientMail = $this->scopeConfig->getValue('sendEmailWareHouse/general/recipientEmail', $scope, $scopeId);
        return $recipientMail ? explode(',', trim($recipientMail)) : [];
    }

    /**
     * Get Cc Email function
     * @param $scope
     * @param $scopeId
     * @return array
     */
    public function getCcEmail($scope, $scopeId)
    {
        $ccTo = $this->scopeConfig->getValue('sendEmailWareHouse/general/ccTo', $scope, $scopeId);
        return $ccTo ? explode(',', trim($ccTo)) : [];
    }

    /**
     * Get Bcc Email function
     * @param $scope
     * @param $scopeId
     * @return array
     */
    public function getBccEmail($scope, $scopeId)
    {
        $bccTo = $this->scopeConfig->getValue('sendEmailWareHouse/general/bccTo', $scope, $scopeId);
        return $bccTo ? explode(',', trim($bccTo)) : [];
    }

    /**
     * Get Subject function
     * @param $scope
     * @param $scopeId
     * @return string
     */
    public function getSubject($scope, $scopeId)
    {
        $subjectEmail = $this->scopeConfig->getValue('sendEmailWareHouse/general/subject', $scope, $scopeId);
        return $subjectEmail ? strtoupper($subjectEmail) : 'ASICS';
    }

    /**
     * Get Template Identifier function
     * @param $scope
     * @param $scopeId
     * @return mixed
     */
    public function getTemplateIdentifier($scope, $scopeId)
    {
        return $this->scopeConfig->getValue('sendEmailWareHouse/general/template', $scope, $scopeId);
    }

    /**
     * Shipment by Order id
     *
     * @param int $orderId
     * @return ShipmentInterface[]|null |null
     */
    public function getShipmentDataByOrderId($orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
        try {
            $shipments = $this->shipmentRepository->getList($searchCriteria);
            $shipmentRecords = $shipments->getItems();
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $shipmentRecords = null;
        }
        return $shipmentRecords;
    }

    /**
     * Get Invoice data by Order Id
     *
     * @param int $orderId
     * @return InvoiceInterface[]|null
     */
    public function getInvoiceDataByOrderId($orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
        try {
            $invoices = $this->invoiceRepository->getList($searchCriteria);
            $invoiceRecords = $invoices->getItems();
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $invoiceRecords = null;
        }
        return $invoiceRecords;
    }

    /**
     * Get Fulfillment Id By Shipment
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string|int
     */
    public function getFulfillmentId($shipment)
    {
        $shipmentId = $shipment->getId();
        $shipmentAttributesModel = $this->shipmentAttributesFactory->load($shipmentId, 'shipment_id');

        $fulfillmentId = '';
        if (!empty($shipmentAttributesModel)) {
            $fulfillmentId = $shipmentAttributesModel->getNetsuiteFulfillmentId();
        }
        return $fulfillmentId;
    }
}
