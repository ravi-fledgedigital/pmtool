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

class CancelReturn {

    const PATH_EXPORT_RETURN = '/var/shared/sftp/export/return/cancel/';

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
    protected OrderRepositoryInterface $orderRepository;

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
        Data $rmaAddressHelper
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
        try {
            $data = $this->prepareData($request );
            $timeZoneDatetimeString = $this->exportXml->getTimeZoneDatetimeString('YmdHisv', $request->getStoreId());
            $fileName = 'OKR_RTNCANCEL_'.$timeZoneDatetimeString.'.xml';
            $this->setFileName($fileName);
            $rootDir = $this->_dir->getRoot();
            if (!file_exists($rootDir . self::PATH_EXPORT_RETURN)) {
                mkdir($rootDir . self::PATH_EXPORT_RETURN, 0777, true);
            }
            $path = $rootDir . self::PATH_EXPORT_RETURN;
            $this->exportXml->exportToFileXml($data, $path . $this->getFileName());
            $this->logger->info('exported : ' . $this->getFileName());
            return 'Export RMA successfully';
        }catch (Exception $e) {
            $this->logger->error(sprintf('Export RMA Id [%s] has something went wrong. Message: [%s]', $request->getRequestId(), $e->getMessage()));
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t update RMA. Connect ERP has problem. Please check log to check detail error.')
            );
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
        $data['Rtncancel'] = array();
        $index = 1;
        foreach ($requestItems as $requestItem){
            $orderItem = $this->orderItemRepository->get($requestItem->getOrderItemId());
            $orderItemConfig = $this->orderItemRepository->get($orderItem->getParentItemId());
            $productAmt = $orderItemConfig->getPriceInclTax() * $requestItem->getRequestQty();
            $product = $this->productRepository->get($orderItem->getSku());
            $cancel_date = $this->localeDate->scopeDate($request->getStoreId(),null,true)->format('Y-m-d H:i:s');
            $dataItem = array(
                "rtn_order_no" => $this->exportXml->addPrefix($requestItem->getRequestId(),ExportXml::PREFIX_RETURN),
                "origin_order_no" => $this->exportXml->addPrefix($request->getOrderId(),ExportXml::PREFIX_ORDER),
                "return_type" => 'return',
                "product_sku" => $product->getSkuWms(),
                "product_qty" => $requestItem->getRequestQty(),
                "product_unit_price" => $this->formatNumber((float) $orderItemConfig->getPriceInclTax()),
                "product_amt" => $this->formatNumber((float) $productAmt),
                "order_cancel_date" => $cancel_date,
                "remark1" => "",
                "remark2" => "",
            );
            $index++;
            $data['Rtncancel'][] = $dataItem;
        }
        return $data;
    }

    /**
     * format number
     * @param float $number
     * @return float
     */
    private function formatNumber(float $number): float
    {
        return round($number,0,PHP_ROUND_HALF_UP);
    }

}
