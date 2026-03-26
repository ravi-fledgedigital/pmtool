<?php
namespace OnitsukaTiger\NetSuite\Model;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class SuiteTalk
{
    const SCRIPT_ID_CUSTBODY_MJ_ORDER_ID = 'custbody_mj_order_id';
    const SCRIPT_ID_CUSTITEM_ECOMM_PRODUCT = 'custitem_ecomm_product';
    const SCRIPT_ID_CUSTBODY_ORDER_STATUS = 'custbody_order_status';
    const SCRIPT_ID_CSEG_TXN_BRAND = 'cseg_txn_brand';
    const SCRIPT_ID_CUSTBODY_MJ_RETURN_REQUEST_ID = 'custbody_mj_return_request_id';
    const SCRIPT_ID_CUSTBODY_ORDER_RETURN_DETAILS = 'custbody_order_return_details';
    const SCRIPT_ID_CUSTBODY_MJ_INVOICE_ID = 'custbody_mj_invoice_no';
    const SCRIPT_ID_CUSTBODY_POS_CUSTOMER_NAME = 'custbody_pos_customer_name';
    const SCRIPT_ID_CUSTBODY_POS_CUSTOMER_BILLING_ADDRESS = 'custbody_pos_customer_billing_address';
    const SCRIPT_ID_CSEG_TXN_BRAND_VALUE = '3';
    const SCRIPT_ID_CUSTBODY_MJ_LOCATION_CODE ='custbody_mj_location_code';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;
    /**
     * @var \OnitsukaTiger\Logger\Api\Logger
     */
    protected $logger;
    /**
     * @var \OnitsukaTiger\NetSuite\Model\SourceMapping
     */
    protected $sourceMapping;
    /**
     * @var \OnitsukaTiger\NetsuiteOrderSync\Helper\Data
     */
    protected $helper;

    /**
     * @var \OnitsukaTiger\Shipment\Model\ShipmentStatus
     */
    protected $shipment;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var DirectoryList
     */
    protected $_dir;

    /**
     * @var
     */
    protected $config;

    /**
     * SuiteTalk constructor.
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipment
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \OnitsukaTiger\Logger\Api\Logger $logger
     * @param SourceMapping $sourceMapping
     * @param \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helper
     * @param DirectoryList $dir
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipment,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \OnitsukaTiger\Logger\Api\Logger $logger,
        \OnitsukaTiger\NetSuite\Model\SourceMapping $sourceMapping,
        \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helper,
        DirectoryList $dir
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->shipment = $shipment;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
        $this->sourceMapping = $sourceMapping;
        $this->helper = $helper;
        $this->_dir = $dir;
    }

    protected function getService()
    {
        return new \NetSuite\NetSuiteService($this->getConfig());
    }

    protected function getRecordRef($internalId, $externalId, $type = null)
    {
        $recordRef = new \NetSuite\Classes\RecordRef();
        if($internalId) {
            $recordRef->internalId = $internalId;
        }
        if($externalId) {
            $recordRef->externalId = $externalId;
        }
        if($type) {
            $recordRef->type = $type;
        }
        return $recordRef;
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        if (empty($this->config)) {
            $rootDir = $this->_dir->getRoot();
            $logging = \trim($this->scopeConfig->getValue('netsuite/suitetalk/log'));
            $base = \trim($this->scopeConfig->getValue('netsuite/suitetalk/log_path'));
            $log_path = $rootDir . $base . date('ymd');
            if($logging) {
                if (!file_exists($rootDir . $base)) {
                    mkdir($rootDir . $base);
                }
                if (!file_exists($log_path)) {
                    mkdir($log_path);
                }
            }
            $this->config = [
                "endpoint" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/endpoint')),
                "host"     => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/host')),
                "account"  => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/account')),
                "consumerKey" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/consumerKey')),
                "consumerSecret" => \trim(
                    $this->scopeConfig->getValue('firebear_importexport/netsuite/consumerSecret')
                ),
                "token" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/token')),
                "tokenSecret" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/tokenSecret')),
                "logging" => $logging,
                "log_path" => $log_path,
            ];
        }
        return $this->config;
    }

    /**
     * Get external Id
     * @param $order
     * @param $sourceCode
     * @return string
     */
    protected function getOrderExternalId($order,$sourceCode)
    {
        // T.B.D. need to fix when stock taking logic is fixed
        return sprintf(
            '%s_%s',
            $order->getIncrementId(),
            $this->sourceMapping->getNetSuiteLocation($sourceCode)
        );
    }

    protected function netSuiteFloatNumberFormat(float $number) {
        return number_format(round($number,2,PHP_ROUND_HALF_UP),2, '.', '');
    }

    /**
     * Get NetSuite Internal Id Config
     * @param $key
     * @param $storeId
     * @return mixed
     */
    protected function getNetsuiteInternalIdConfig($key, $storeId)
    {
        return $this->helper->getNetsuiteInternalIdConfig($key, $storeId);
    }

    /**
     * Get SKU item search
     * @param $sku
     * @return \NetSuite\Classes\ItemSearch
     */
    protected function getSkuItemSearch($sku)
    {
        $searchBooleanField = new \NetSuite\Classes\SearchBooleanField();
        $searchBooleanField->searchValue = false;

        $searchStringField = new \NetSuite\Classes\SearchStringField();
        $searchStringField->operator = "is";
        $searchStringField->searchValue = $sku;

        //call item search basic
        $itemSearchBasic = new \NetSuite\Classes\ItemSearchBasic();
        $itemSearchBasic->isInactive = $searchBooleanField;
        $itemSearchBasic->itemId = $searchStringField;

        $itemSearch = new \NetSuite\Classes\ItemSearch();
        $itemSearch->basic = $itemSearchBasic;

        return $itemSearch;
    }

    /**
     * Get Return Sales Order Request
     * @param $externalId
     * @param $storeId
     * @param $sourceCode
     * @return \NetSuite\Classes\SalesOrder
     */
    protected function getReturnSalesOrderRequest($externalId, $storeId, $sourceCode){
        $salesOrder = new \NetSuite\Classes\SalesOrder();
        $salesOrder->externalId = $externalId;
        $salesOrder->customForm = $this->getRecordRef(
            $this->getNetsuiteInternalIdConfig('custom_form_id', $storeId),
            null,
            $this->getNetsuiteInternalIdConfig('custom_form_type', $storeId)
        );
        $salesOrder->entity = $this->getRecordRef(
            $this->getNetsuiteInternalIdConfig('netsuite_entity_id', $storeId),
            null
        );
        $salesOrder->location = $this->getRecordRef(
            null,
            $this->sourceMapping->getNetSuiteLocation($sourceCode)
        );
        return $salesOrder;
    }
}
