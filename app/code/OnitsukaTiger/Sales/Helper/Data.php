<?php
/** phpcs:ignoreFile */
namespace OnitsukaTiger\Sales\Helper;

use Clickend\Kerry\Model\ResourceModel\TrackingHistory\Collection as ClickendCollection;
use Clickend\Kerry\Model\ResourceModel\TrackingList\Collection;
use Exception;
use Magento\Catalog\Helper\Image as imageHelper;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Ninja\Model\ResourceModel\Order\CollectionFactory as NinjaCollectionFactory;
use OnitsukaTiger\Ninja\Model\ResourceModel\StatusHistory\CollectionFactory;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTiger\Store\Helper\Data as StoreHelper;
use Psr\Log\LoggerInterface;
use Vaimo\OTScene7Integration\Helper\Data as Scene7Helper;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * @var TransportBuilder
     */
    private TransportBuilder $transportBuilder;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logLoggerInterface;
    /**
     * @var Template
     */
    private Template $templateContainer;
    /**
     * @var PaymentHelper
     */
    private PaymentHelper $paymentHelper;
    /**
     * @var StateInterface
     */
    private StateInterface $inlineTranslation;
    /**
     * @var ClickendCollection
     */
    private ClickendCollection $trackingHistory;
    /**
     * @var Collection
     */
    private Collection $trackingList;
    /**
     * @var Context
     */
    private Context $context;
    /**
     * @var SenderResolverInterface
     */
    private SenderResolverInterface $senderResolver;
    /**
     * @var imageHelper
     */
    private imageHelper $catalogHelper;
    /**
     * @var \OnitsukaTiger\Rma\Helper\Data
     */
    private \OnitsukaTiger\Rma\Helper\Data $_helperReturn;
    /**
     * @var ShipmentStatus
     */
    private ShipmentStatus $shipment;
    /**
     * @var ShipmentRepositoryInterface
     */
    private ShipmentRepositoryInterface $shipmentRepository;
    /**
     * @var EncoderInterface
     */
    private EncoderInterface $jsonEncoder;
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $ninjaTrackHistoryFactory;
    /**
     * @var NinjaCollectionFactory
     */
    private NinjaCollectionFactory $ninjaTrackFactory;
    /**
     * @var StoreHelper
     */
    private StoreHelper $helperStore;
    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;
    /**
     * @var Scene7Helper
     */
    private Scene7Helper $scene7;

    /**
     * @param TransportBuilder $transportBuilder
     * @param LoggerInterface $logLoggerInterface
     * @param Template $templateContainer
     * @param PaymentHelper $paymentHelper
     * @param StateInterface $inlineTranslation
     * @param ClickendCollection $trackingHistory
     * @param Collection $trackingList
     * @param Context $context
     * @param SenderResolverInterface $senderResolver
     * @param imageHelper $catalogHelper
     * @param \OnitsukaTiger\Rma\Helper\Data $helperReturn
     * @param ShipmentStatus $shipment
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param EncoderInterface $jsonEncoder
     * @param CollectionFactory $ninjaTrackHistoryFactory
     * @param NinjaCollectionFactory $ninjaTrackFactory
     * @param StoreHelper $helperStore
     * @param ProductRepository $productRepository
     * @param Scene7Helper $scene7
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        LoggerInterface $logLoggerInterface,
        Template $templateContainer,
        PaymentHelper $paymentHelper,
        StateInterface $inlineTranslation,
        ClickendCollection $trackingHistory,
        Collection $trackingList,
        Context $context,
        SenderResolverInterface $senderResolver,
        imageHelper $catalogHelper,
        \OnitsukaTiger\Rma\Helper\Data $helperReturn,
        ShipmentStatus $shipment,
        ShipmentRepositoryInterface $shipmentRepository,
        EncoderInterface $jsonEncoder,
        CollectionFactory $ninjaTrackHistoryFactory,
        NinjaCollectionFactory $ninjaTrackFactory,
        StoreHelper$helperStore,
        ProductRepository $productRepository,
        Scene7Helper $scene7
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->logLoggerInterface = $logLoggerInterface;
        $this->templateContainer = $templateContainer;
        $this->paymentHelper = $paymentHelper;
        $this->inlineTranslation = $inlineTranslation;
        $this->trackingHistory = $trackingHistory;
        $this->trackingList = $trackingList;
        $this->context = $context;
        $this->senderResolver = $senderResolver;
        $this->catalogHelper = $catalogHelper;
        $this->_helperReturn = $helperReturn;
        $this->shipment = $shipment;
        $this->shipmentRepository = $shipmentRepository;
        $this->jsonEncoder = $jsonEncoder;
        $this->ninjaTrackHistoryFactory = $ninjaTrackHistoryFactory;
        $this->ninjaTrackFactory = $ninjaTrackFactory;
        $this->helperStore = $helperStore;
        $this->productRepository = $productRepository;
        $this->scene7 = $scene7;
        parent::__construct($context);
    }

    /**
     * Send Cancel order email template
     * @param Order $order
     */
    public function sendCancellEmailTemplate($order)
    {
        try {
            if ($this->scopeConfig->getValue('sales_email/cancel_order/enabled', ScopeInterface::SCOPE_STORE, $order->getStoreId())) {
                $this->sendEmail($order, 'sales_email/cancel_order');
            }
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * Send ready collection order email template
     * @param Order $order
     */
    public function sendReadyCollectionEmailTemplate($order)
    {
        try {
            if ($this->scopeConfig->getValue('sales_email/ready_collection_order/enabled', ScopeInterface::SCOPE_STORE, $order->getStoreId())) {
                $this->sendEmail($order, 'sales_email/ready_collection_order');
            }
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * Send delivered order email template
     * @param Order\Shipment $shipment
     */
    public function sendDeliveredEmailTemplate($shipment)
    {
        $order = $shipment->getOrder();
        try {
            if ($this->scopeConfig->getValue('sales_email/delivered_order/enabled', ScopeInterface::SCOPE_STORE, $order->getStoreId())) {
                $this->sendEmail($order, 'sales_email/delivered_order', null, $shipment);
            }
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * Send shipped order email template
     * @param Order $order
     */
    public function sendShippedEmailTemplate($order)
    {
        try {
            $this->sendEmail($order, 'sales_email/shipment');
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * Send confirm order email template for Login
     * @param Order $order
     */
    public function sendConfirmEmailTemplate($order)
    {
        try {
            $this->sendEmail($order, 'sales_email/order');
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * Send confirm order email template for Guest
     * @param Order $order
     */
    public function sendConfirmEmailGuestTemplate($order)
    {
        try {
            $this->sendEmail($order, 'sales_email/order', 'guest');
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * Send ready to pickup order email template
     * @param Order $order
     */
    public function sendReadyToPickup($order)
    {
        try {
            if ($order->getCustomerIsGuest()) {
                $this->sendEmail($order, 'sales_email/order_ready_for_pickup', 'guest');
            } else {
                $this->sendEmail($order, 'sales_email/order_ready_for_pickup');
            }
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * Send picked by customer order email template
     * @param Order $order
     */
    public function sendPickedByCustomer($order)
    {
        try {
            if ($order->getCustomerIsGuest()) {
                $this->sendEmail($order, 'sales_email/picked_by_customer', 'guest');
            } else {
                $this->sendEmail($order, 'sales_email/picked_by_customer');
            }
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * @param $shipment
     * @return void
     */
    public function sendReadyToShip($shipment)
    {
        $order = $shipment->getOrder();
        try {
            if ($order->getCustomerIsGuest()) {
                $this->sendEmail($order, 'sales_email/ready_to_ship', 'guest', $shipment);
            } else {
                $this->sendEmail($order, 'sales_email/ready_to_ship', null, $shipment);
            }
        } catch (Exception $e) {
            $this->logLoggerInterface->debug($e->getMessage());
        }
    }

    /**
     * @param $order
     * @param $code
     * @param null $guest
     * @param null $shipment
     * @throws LocalizedException
     * @throws MailException
     */
    protected function sendEmail($order, $code, $guest = null, $shipment = null)
    {
        $templateId = $this->scopeConfig->getValue($code . '/template', ScopeInterface::SCOPE_STORE, $order->getStoreId());
        if ($guest == 'guest') {
            $templateId = $this->scopeConfig->getValue($code . '/guest_template', ScopeInterface::SCOPE_STORE, $order->getStoreId());
        }
        if ($code == 'sales_email/order' || $code == 'sales_email/shipment' || $code == 'sales_email/order_ready_for_pickup' || $code == 'sales_email/picked_by_customer' || $code == 'sales_email/ready_to_ship' || $guest == 'guest') {
            $senderEmail = $this->scopeConfig->getValue($code . '/identity', ScopeInterface::SCOPE_STORE, $order->getStoreId());
            $senderEmail = $this->senderResolver->resolve($senderEmail, $order->getStoreId());
            $ccEmail = $this->scopeConfig->getValue($code . '/copy_to', ScopeInterface::SCOPE_STORE, $order->getStoreId());
            $bccEmail = $this->scopeConfig->getValue($code . '/copy_method', ScopeInterface::SCOPE_STORE, $order->getStoreId());
            $ccEmail = $ccEmail ? explode(',', trim($ccEmail)) : [];
            $bccEmail = ($bccEmail != 'bcc') ? explode(',', trim($bccEmail)) : [];
        } else {
            $senderEmail = $this->scopeConfig->getValue($code . '/senderEmail', ScopeInterface::SCOPE_STORE, $order->getStoreId());
            $senderEmail = $this->senderResolver->resolve($senderEmail, $order->getStoreId());
            $ccEmail = $this->scopeConfig->getValue($code . '/ccTo', ScopeInterface::SCOPE_STORE, $order->getStoreId());
            $bccEmail = $this->scopeConfig->getValue($code . '/bccTo', ScopeInterface::SCOPE_STORE, $order->getStoreId());
            $ccEmail = $ccEmail ? explode(',', trim($ccEmail)) : [];
            $bccEmail = $bccEmail ? explode(',', trim($bccEmail)) : [];
        }

        $this->setEmailTemplateVars($order, $shipment);
        $this->configureEmailTemplate($order, $templateId);
        $customerName = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        $this->transportBuilder->addTo(
            $order->getCustomerEmail(),
            $customerName
        )->setFromByScope($senderEmail)->addCc($ccEmail)->addBcc($bccEmail);

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
    }

    /**
     * @param $order
     * @param $templateId
     * @throws MailException
     */
    protected function configureEmailTemplate($order, $templateId)
    {
        $this->transportBuilder->setTemplateIdentifier($templateId);
        $this->transportBuilder->setTemplateOptions($this->templateContainer->getTemplateOptions());
        $this->transportBuilder->setTemplateVars($this->templateContainer->getTemplateVars());
        $this->transportBuilder->setFromByScope(
            'sales',
            $order->getStoreId()
        );
    }

    /**
     * @param $order
     * @param $shipment
     * @throws Exception
     */
    protected function setEmailTemplateVars($order, $shipment = null)
    {
        $transport = [
            'order' => $order,
            'status' => $order->getStatus(),
            'order_id' => $order ? $order->getEntityId() : '',
            'shipment_id' => $shipment ? $shipment->getEntityId() : '',
            'shipment' => $shipment,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ]
        ];
        $transportObject = new DataObject($transport);
        $this->templateContainer->setTemplateVars($transportObject->getData());
        $this->templateContainer->setTemplateOptions(
            [
                'area' => 'frontend',
                'store' => $order->getStoreId(),
            ]
        );
    }

    /**
     * Returns payment info block as HTML.
     *
     * @param OrderInterface $order
     *
     * @return string
     * @throws Exception
     */
    public function getPaymentHtml(OrderInterface $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $order->getStoreId()
        );
    }

    /**
     * @param Order\Shipment $shipment
     * @return mixed
     */
    public function getKerryTrackHistory($shipment)
    {
        $shipmentTrackKerry = $this->trackingHistory->getByOrderId($shipment->getIncrementId());
        $trackHistoryItems = $shipmentTrackKerry->getItems();

        if (count($trackHistoryItems) > 0) {
            return $trackHistoryItems[array_key_last($trackHistoryItems)];
        }
        return null;
    }

    /**
     * @param Order\Shipment $shipment
     * @return mixed
     */
    public function getKerryTrack($shipment)
    {
        $shipmentTrackKerry = $this->trackingList->getByOrderId($shipment->getIncrementId());
        $trackItems = $shipmentTrackKerry->getItems();

        if (count($trackItems) > 0) {
            return $trackItems[array_key_last($trackItems)];
        }
        return null;
    }

    /**
     * @param $shipment
     * @return array
     */
    public function getTrackDetail($shipment)
    {
        $detail = [
            'shipment_id' => '',
            'shipment_status' => '',
            'con_no' => '',
            'courier-name' => '',
            'service_code' => '',
            'dispatch_date' => '',
            'status_updated_date' => ''
        ];
        if (empty($shipment)) {
            return $detail;
        }
        $shipment = $this->getShipments($shipment->getId());
        $shipmentStatus = $this->displayStatusLabel($shipment->getExtensionAttributes()->getStatus());

        $detail = [
            'shipment_id' => $shipment->getId(),
            'shipment_status' => $shipmentStatus,
            'con_no' => '',
            'courier-name' => '',
            'service_code' => '',
            'dispatch_date' => '',
            'status_updated_date' => ''
        ];

        /** @var Order $_order */
        $_order = $shipment->getOrder();
        if ($_order) {
            $trackItems = $shipment->getTracks();
            if (!empty($trackItems)) {
                $track = $trackItems[array_key_last($trackItems)];
                $courierName = $_order->getShippingMethod() ? $_order->getShippingDescription() : 'JNE';
                $detail['courier-name'] = $courierName;
                if (!is_null($track)) {
                    $detail['con_no'] = $track->getTrackNumber();
                    $detail['service_code'] = $_order->getShippingDescription();
                    $detail['dispatch_date'] = $track->getCreatedAt();
                    $detail['dispatch_date'] = $track->getCreatedAt();
                    $detail['status_updated_date'] = $track->getUpdatedAt();
                }
            }
        } else {
            if ($_order->getShippingMethod() == 'kerryshipping_kerryshipping') {
                $track = $this->getKerryTrack($shipment);
                $trackHistory = $this->getKerryTrackHistory($shipment);
                $detail['courier-name'] = 'Kerry';
                if (!is_null($track)) {
                    $detail['con_no'] = $track->getConNo();
                    $detail['service_code'] = $track->getServiceCode();
                    $detail['dispatch_date'] = $track->getCreateTime();
                }
                if (!is_null($trackHistory)) {
                    $detail['dispatch_date'] = $trackHistory->getCreateTime();
                    $detail['status_updated_date'] = $trackHistory->getUpdateTime();
                }
            } elseif ($_order->getShippingMethod() == 'ninja_ninja') {
                $track = $this->getNinjaTrack($shipment);
                $trackHistory = $this->getNinjaTrackHistory($shipment);
                $detail['courier-name'] = 'Ninja Van';
                if (!is_null($track)) {
                    $detail['con_no'] = $track->getTrackingId();
                    $detail['service_code'] = json_decode($track->getJson(), true)['service_level'];
                    $detail['dispatch_date'] = $track->getCreatedAt();
                    $detail['status_updated_date'] = $track->getUpdatedAt();
                }
                if (!is_null($trackHistory)) {
                    $detail['dispatch_date'] = $trackHistory->getCreatedAt();
                    $detail['status_updated_date'] = $trackHistory->getUpdatedAt();
                }
            } else {
                $trackItems = $shipment->getTracks();
                if (!empty($trackItems)) {
                    $track = $trackItems[array_key_last($trackItems)];
                    $detail['courier-name'] = $_order->getShippingMethod();
                    if (!is_null($track)) {
                        $detail['con_no'] = $track->getTrackNumber();
                        $detail['service_code'] = $track->getCarrierCode();
                        $detail['dispatch_date'] = $track->getCreatedAt();
                        $detail['dispatch_date'] = $track->getCreatedAt();
                        $detail['status_updated_date'] = $track->getUpdatedAt();
                    }
                }
            }
        }

        return $detail;
    }

    /**
     * @param $status
     * @return string
     */
    private function displayStatusLabel($status)
    {
        $statusLabel = str_replace('_', ' ', $status);
        return ucwords($statusLabel);
    }

    /**
     * @param $shipMentId
     * @return \Magento\Sales\Api\Data\ShipmentSearchResultInterface
     */
    public function getShipments($shipmentId)
    {
        return $this->shipmentRepository->get($shipmentId);
    }

    public function getShipmentJson($shipments, $imageDefault)
    {
        $results = [];
        $_order = null;
        foreach ($shipments as $shipment) {
            if (!$_order) {
                $_order = $shipment->getOrder();
            }
            $trackDetail = $this->getTrackDetail($shipment);

            $shipmentItems = [];

            foreach ($shipment->getItems() as $item) {
                /** @var Order\Item $orderItem */
                $orderItem = $item->getOrderItem();

                $productForThumbnail = $this->_helperReturn->getFinalProductThumbnail($orderItem, $_order);
                $imageUrl = $this->scene7->getOrderItemProductImage($productForThumbnail);

                $subTotal = (float)$orderItem->getPriceInclTax() * (int)$item->getQty();
                $shipmentItems[] = [
                    'image' => $imageUrl,
                    'link' => $orderItem->getProduct() ? $orderItem->getProduct()->getProductUrl() : '#',
                    'name' => $orderItem->getName(),
                    'qty' => (int)$item->getQty(),
                    'price' => $_order->formatPrice($orderItem->getPriceInclTax()),
                    'total' => $_order->formatPrice($subTotal),
                ];
            }
            $results[$shipment->getId()] = [
                'data' => [
                    'courier-name' => __($trackDetail['courier-name']),
                    'dispatch-date' => $trackDetail['dispatch_date'] ? $this->helperStore->formatDate($trackDetail['dispatch_date'], $shipment->getStoreId()) : '',
                    'tracking-number' => $trackDetail['con_no'],
                    'service-code' => __($trackDetail['service_code']),
                    'shipment-status' => __($trackDetail['shipment_status']),
                    'updated-date' => $trackDetail['status_updated_date'] ? $this->helperStore->formatDate($trackDetail['status_updated_date'], $shipment->getStoreId()) : ''
                ],
                'items' => $shipmentItems
            ];
        }
        return $this->jsonEncoder->encode($results);
    }

    /**
     * @param $shipment
     * @return DataObject|null
     */
    public function getNinjaTrackHistory($shipment)
    {
        if (!$shipment->getTracks()) {
            return null;
        }
        $track = $shipment->getTracks()[array_key_last($shipment->getTracks())];
        $trackingNumber = $track->getTrackNumber();
        $trackingHistory = $this->ninjaTrackHistoryFactory
            ->create()
            ->addFieldToFilter('tracking_id', $trackingNumber);
        $trackItems = $trackingHistory->getItems();
        if (count($trackItems) > 0) {
            return $trackItems[array_key_last($trackItems)];
        }
        return null;
    }

    /**
     * @param $shipment
     * @return DataObject|null
     */
    public function getNinjaTrack($shipment)
    {
        $track = $this->ninjaTrackFactory->create()
            ->addFieldToFilter('shipment_id', $shipment->getId());
        $trackItems = $track->getItems();
        if (count($trackItems) > 0) {
            return $trackItems[array_key_last($trackItems)];
        }
        return null;
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function defaultCheckedSendMailCreditMemo($storeId)
    {
        return $this->scopeConfig->getValue('onitsukatiger_sales/sales_creditmemo/default_checked_send_mail', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $sku
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductUrlFromSku($item)
    {
        $sku = $item->getProduct() ? $item->getProduct()->getData('sku') : $item->getData('sku');
        $product = $this->productRepository->get($sku);
        return $product->setStoreId($item->getStoreId())->getUrlModel()->getUrlInStore($product, ['_scope' =>$item->getStoreId()]);
    }
    /**
     * @param $id
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductUrlFromId($id)
    {
        $product = $this->productRepository->getById($id);
        return $product->getUrlModel()->getUrl($product, ['_scope' =>$product->getStoreId()]);
    }
}
