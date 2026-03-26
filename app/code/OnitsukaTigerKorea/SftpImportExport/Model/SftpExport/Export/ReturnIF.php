<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Api\ConditionRepositoryInterface;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use OnitsukaTigerKorea\RmaAddress\Helper\Data;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;
use OnitsukaTiger\Logger\Api\Logger;

class ReturnIF {

    const PATH_EXPORT_RETURN = '/var/shared/sftp/export/return/';

    const PATH_EXPORT_RETURN_BCK = '/var/shared/sftp/export/return_bck/';

    const REMARK_RESOlUTION = [
        'Return' => 1,
        'Return No Collection' => 2
    ];

    protected $fileName;

    /**
     * @var ExportXml
     */
    protected $exportXml;

    /**
     * @var ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * @var ResolutionRepositoryInterface
     */
    protected $resolutionRepository;

    /**
     * @var ConditionRepositoryInterface
     */
    protected $conditionRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var DirectoryList
     */
    protected $_dir;

    /**
     * @var Data
     */
    protected $rmaAddressHelper;
    /**
     * @var \OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo\Collection
     */
    private $returnCollection;
    /**
     * @var \OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo
     */
    private $returnInfoResource;

    /**
     * ReturnIF constructor.
     * @param DirectoryList $dir
     * @param TimezoneInterface $localeDate
     * @param ProductRepositoryInterface $productRepository
     * @param ExportXml $exportXml
     * @param ReasonRepositoryInterface $reasonRepository
     * @param ResolutionRepositoryInterface $resolutionRepository
     * @param ConditionRepositoryInterface $conditionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ManagerInterface $messageManager
     * @param Logger $logger
     * @param Data $rmaAddressHelper
     * @param \OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo\Collection $returnCollection
     * @param \OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo $returnInfoResource
     */
    public function __construct(
        DirectoryList $dir,
        TimezoneInterface $localeDate,
        ProductRepositoryInterface $productRepository,
        ExportXml $exportXml,
        ReasonRepositoryInterface $reasonRepository,
        ResolutionRepositoryInterface $resolutionRepository,
        ConditionRepositoryInterface $conditionRepository,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ManagerInterface $messageManager,
        Logger $logger,
        Data $rmaAddressHelper,
        \OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo\Collection $returnCollection,
        \OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo $returnInfoResource
    ){
        $this->_dir = $dir;
        $this->localeDate = $localeDate;
        $this->productRepository = $productRepository;
        $this->exportXml = $exportXml;
        $this->reasonRepository = $reasonRepository;
        $this->resolutionRepository = $resolutionRepository;
        $this->conditionRepository = $conditionRepository;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->rmaAddressHelper = $rmaAddressHelper;
        $this->returnCollection = $returnCollection;
        $this->returnInfoResource = $returnInfoResource;
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param RequestInterface $request
     * @return string
     */
    public function execute(RequestInterface $request): string
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/okrReturnLog.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Return XML Log Start============================');

        try {
            $data = $this->prepareData($request );
            $logger->info('Request Data: ' . json_encode($data));
            $timeZoneDatetimeString = $this->exportXml->getTimeZoneDatetimeString('YmdHisv', $request->getStoreId());
            $fileName = 'OKR_Return_'.$timeZoneDatetimeString.'.xml';
            $logger->info('Filename: ' . $fileName);
            $this->setFileName($fileName);
            $rootDir = $this->_dir->getRoot();
            if (!file_exists($rootDir . self::PATH_EXPORT_RETURN)) {
                $logger->info('Create new XML file.');
                mkdir($rootDir . self::PATH_EXPORT_RETURN, 0777, true);
            }
            $path = $rootDir . self::PATH_EXPORT_RETURN;
            $logger->info('Export file path: ' . $path);
            $this->exportXml->exportToFileXml($data, $path . $this->getFileName());
            $this->logger->info('exported : ' . $this->getFileName());

            if (!file_exists($rootDir . self::PATH_EXPORT_RETURN_BCK)) {
                mkdir($rootDir . self::PATH_EXPORT_RETURN_BCK, 0777, true);
            }
            $pathBCK = $rootDir . self::PATH_EXPORT_RETURN_BCK;
            $this->exportXml->exportToFileXml($data, $pathBCK . $this->getFileName());

            $logger->info('==========================Return XML Log END============================\n\n\n');
            return 'success';
        }catch (Exception $e) {
            $logger->info('Getting exception while generated the XML file.');
            $logger->info('Exception: ' . $e->getMessage());
            $logger->info('Request ID: ' . $request->getRequestId());
            $this->logger->error(sprintf('Export RMA Id [%s] has something wrong. Message: [%s]', $request->getRequestId(), $e->getMessage()));
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t update RMA. Connect ERP has problem. Please check log to check detail error.')
            );
            $logger->info('==========================Return XML Log END============================\n\n\n');
            return $e->getMessage();
        }
    }

    /**
     * @param RequestInterface $request
     * @return array
     * @throws NoSuchEntityException
     */
    public function prepareData(RequestInterface $request): array
    {
        $requestItems = $request->getRequestItems();
        $data['Return'] = array();
        $index = 1;
        $rmaAddress = $this->rmaAddressHelper->getRmaAddress($request);
        $returnInfoCollection  = $this->getReturnInfoCollection($request->getOrderId());
        $trackingNo = "";
        if ($returnInfoCollection != null) {
            $trackingNo = $returnInfoCollection->getTrackingNo();
        }
        $flag = false;
        foreach($requestItems as $requestItem){
            $returnReason = $this->reasonRepository->getById($requestItem->getReasonId());
            $returnCondition  = $this->conditionRepository->getById($requestItem->getConditionId());
            $returnResolution = $this->resolutionRepository->getById($requestItem->getResolutionId());
            $remarkResolution =  isset(self::REMARK_RESOlUTION[$returnResolution->getTitle()]) ?
                self::REMARK_RESOlUTION[$returnResolution->getTitle()] : "";
            $order = $this->orderRepository->get($request->getOrderId());
            $orderItem = $this->orderItemRepository->get($requestItem->getOrderItemId());
            $orderItemConfig = $this->orderItemRepository->get($orderItem->getParentItemId());
            $productAmt = $orderItemConfig->getPriceInclTax() * $requestItem->getRequestQty();
            $product = $this->productRepository->get($orderItem->getSku());
            $regist_date = $this->localeDate->scopeDate($request->getStoreId(),null,true)->format('Y-m-d H:i:s');
            $remark = $remarkResolution ?? "";
            $no = '';
            if ( $remarkResolution == self::REMARK_RESOlUTION["Return No Collection"]) {
                $flag = true;
                $no = ';'.$trackingNo;
            }
            $orderAddress = implode(' ', $order->getShippingAddress()->getStreet());
            $dataItem = array(
                "rtn_order_no" => $this->exportXml->addPrefix($requestItem->getRequestId(),ExportXml::PREFIX_RETURN),
                "origin_order_no" => $this->exportXml->addPrefix($request->getOrderId(),ExportXml::PREFIX_ORDER),
                "return_type" => 'return',
                "return_reason" => $returnReason->getStores()[$request->getStoreId()]->getLabel() ? $returnReason->getStores()[$request->getStoreId()]->getLabel() : $returnReason->getTitle() ,
                "reason_desc" => $request->getNote(),
                "order_line_no" => $index,
                "product_sku" => $product->getSkuWms(),
                "product_qty" => $requestItem->getRequestQty(),
                "regist_date" => $regist_date,
                "name" => $rmaAddress->getFirstname() ?? $order->getShippingAddress()->getFirstname(),
                "address_zipcode" => $rmaAddress->getPostcode() ?? $order->getShippingAddress()->getPostcode(),
                "address" => $rmaAddress->getStreet() ? $rmaAddress->getStreet() : $orderAddress,
                "address_phone" => '', // based on request ticket OT_DEV_KR-83
                "address_cellphone" => $rmaAddress->getTelephone() ?? $order->getShippingAddress()->getTelephone(),
                "product_unit_price" => $this->formatNumber($orderItemConfig->getPriceInclTax()),
                "product_amt" => $this->formatNumber($productAmt),
                "delivery_charge" => '0',   // Based on Phase 1
                "remark1" => "",
                "remark2" => $remark . $no,
                "remark3" => $order->getTracksCollection()->getFirstItem()->getTrackNumber() ?? "",
            );
            $index++;
            $data['Return'][] = $dataItem;
        }
        if ($returnInfoCollection != null && $flag == true) {
            $this->returnInfoResource->delete($returnInfoCollection);
        }
        return $data;
    }

    /**
     * format number
     * @param $number
     * @return float
     */
    private function formatNumber($number): float
    {
        return round((float)$number,0,PHP_ROUND_HALF_UP);
    }
    /**
     * @param $orderId
     * @return \Magento\Framework\DataObject|null
     */
    public function getReturnInfoCollection($orderId)
    {
        $returnCollection = $this->returnCollection->addFieldToFilter("order_id",$orderId)
            ->getLastItem();
        if ($returnCollection->getTrackingNo() != null) {
            return $returnCollection;
        }
        return null;
    }

}
