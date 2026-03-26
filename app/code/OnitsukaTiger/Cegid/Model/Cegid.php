<?php

namespace OnitsukaTiger\Cegid\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Pdf\Invoice;
use Magento\Sales\Model\Order\Pdf\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use OnitsukaTiger\Cegid\Api\CegidInterface;
use OnitsukaTiger\Cegid\Api\Response\ProductEanCodeResponseInterface;
use OnitsukaTiger\Cegid\Logger\Logger;
use OnitsukaTiger\Cegid\Model\Config as ModelCegidConfig;
use OnitsukaTiger\Cegid\Model\Response\ProductEanCodeResponse;

class Cegid implements CegidInterface
{
    public const SHIPMENT_STATUS_PROCESSING = "processing";
    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var Http
     */
    protected Http $httpRequest;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var ModelCegidConfig
     */
    protected ModelCegidConfig $modelConfig;

    /**
     * @var Invoice
     */
    protected Invoice $pdfInvoice;

    /**
     * @var Shipment
     */
    protected Shipment $pdfShipment;

    /**
     * @var FileFactory
     */
    protected FileFactory $fileFactory;

    /**
     * @var ShipmentCollectionFactory
     */
    protected ShipmentCollectionFactory $shipmentCollectionFactory;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $requestApi;

    /**
     * @param Logger $logger
     * @param Http $httpRequest
     * @param ProductRepositoryInterface $productRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Config $modelConfig
     * @param Invoice $pdfInvoice
     * @param Shipment $pdfShipment
     * @param FileFactory $fileFactory
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param RequestInterface $requestApi
     */
    public function __construct(
        Logger $logger,
        Http $httpRequest,
        ProductRepositoryInterface $productRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ModelCegidConfig $modelConfig,
        Invoice $pdfInvoice,
        Shipment $pdfShipment,
        FileFactory $fileFactory,
        ShipmentCollectionFactory $shipmentCollectionFactory,
        RequestInterface $requestApi
    ) {
        $this->logger = $logger;
        $this->httpRequest = $httpRequest;
        $this->productRepository = $productRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->modelConfig = $modelConfig;
        $this->pdfInvoice = $pdfInvoice;
        $this->pdfShipment = $pdfShipment;
        $this->fileFactory = $fileFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->requestApi = $requestApi;
    }

    /**
     * Product Sync Ean Code From Cegid
     *
     * @throws Exception
     */
    public function productEanCode(): ProductEanCodeResponseInterface
    {
        $json = $this->httpRequest->getContent();
        $data = $this->jsonDecode($json);

        $this->logger->info('----- EAN Code sync - start ----- data: ' . $json);

        $updated = 0;
        $noSku = 0;
        $sameEanCode = 0;

        foreach ($data->products->product as $productData) {
            try {
                $product = $this->productRepository->get($productData->sku, true, null, true);

                if ($product->getEanCode() == $productData->ean_code) {
                    $this->logger->info(__(
                        "Do not need update EAN Code [%1] for product [%2]",
                        $productData->ean_code,
                        $productData->sku
                    ));
                    $sameEanCode++;
                    continue;
                }

                $product->setData('ean_code', $productData->ean_code);
                $this->productRepository->save($product);
                $this->logger->info(__(
                    "Updated EAN Code for product [%1] to [%2]",
                    $productData->sku,
                    $productData->ean_code
                ));
                $updated++;
            } catch (NoSuchEntityException $e) {
                $this->logger->warning(__(
                    "The product [%1] that was requested doesn't exist",
                    $productData->sku
                ));
                $noSku++;
                continue;
            } catch (CouldNotSaveException|StateException|InputException $e) {
                $this->logger->warning(__(
                    "Can't update EAN Code for product [%1] : [%2]",
                    $productData->sku,
                    $e->getMessage()
                ));
                continue;
            }
        }

        $response = new ProductEanCodeResponse(
            true,
            $updated,
            $noSku,
            $sameEanCode
        );

        $this->logger->info('----- EAN Code sync - end ----- return: ' . $response->toString());
        return $response;
    }

    /**
     * Check and decode json string
     *
     * @param string $string
     * @return mixed
     * @throws Exception
     */
    private function jsonDecode(string $string): mixed
    {
        $data = json_decode($string);
        if (!$data) {
            $this->throwWebApiException('invalid json format', Exception::HTTP_BAD_REQUEST);
        }

        if (!property_exists($data, 'products')) {
            $this->throwWebApiException('invalid json format', Exception::HTTP_BAD_REQUEST);
        }

        if (!property_exists($data->products, 'product')) {
            $this->throwWebApiException('invalid json format', Exception::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    /**
     * Throw Web API exception and add it to log
     *
     * @param string $msg
     * @param int $status
     * @throws Exception
     */
    public function throwWebApiException(string $msg, int $status)
    {
        $exception = new Exception(__($msg), $status);
        $this->logger->critical($exception);
        throw $exception;
    }

    /**
     * Get Invoice Pdf
     *
     * @return false|string|void
     * @throws Exception
     * @throws \Zend_Pdf_Exception
     */
    public function getInvoicePdf(): string
    {
        $paramsApi = $this->requestApi->getParams();

        $this->logger->info('----- Get Content Invoice Pdf - start ----- data: ' . json_encode($paramsApi));

        $this->validateParamsGetPdf($paramsApi);

        $storeId = $paramsApi['store_id'];
        if (!$this->modelConfig->isEnableGetInvoicePdf($storeId)) {
            $this->throwWebApiException(__("Get Invoice is disabled in store id %1", $storeId), Exception::HTTP_BAD_REQUEST);
        }

        $shipment = $this->getShipmentByIncrementId($paramsApi['shipment_increment_id'], $storeId);
        try {
            $invoice = $shipment->getOrder()->getInvoiceCollection()->getFirstItem();
            $pdfContent = $this->pdfInvoice->getPdf([$invoice])->render();

            $this->logger->info('----- Get Content Invoice Pdf - end ----- return: ' . mb_convert_encoding($pdfContent, 'UTF-8', 'ISO-8859-1'));
            return json_encode(['content_invoice' => mb_convert_encoding($pdfContent, 'UTF-8', 'ISO-8859-1')]);
        } catch (Exception $e) {
            $this->logger->error(__("Get Invoice Pdf Error: %1", $e->getMessage()));
            $this->throwWebApiException('Get Invoice Pdf Error: ' . $e->getMessage(), Exception::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get AWBFile Pdf
     *
     * @return false|string|void
     * @throws Exception
     * @throws \Zend_Pdf_Exception
     */
    public function getAWBFilePdf(): string
    {
        $paramsApi = $this->requestApi->getParams();

        $this->logger->info('----- Get Content AWB Pdf - start ----- data: ' . json_encode($paramsApi));

        $this->validateParamsGetPdf($paramsApi);

        $shipmentIncrementId = $paramsApi['shipment_increment_id'];
        $storeId = $paramsApi['store_id'];

        if (!$this->modelConfig->isEnableGetAWBPdf($storeId)) {
            $this->throwWebApiException("Get AWB is disabled in store id " . $storeId, Exception::HTTP_BAD_REQUEST);
        }

        try {
            $shipment = $this->getShipmentByIncrementId($shipmentIncrementId, $storeId);
            $this->validateShipment($shipmentIncrementId, $storeId);
            $pdfContent = $this->pdfShipment->getPdf([$shipment])->render();
            $this->logger->info('----- Get Content AWB Pdf - end ----- return: ' . mb_convert_encoding($pdfContent, 'UTF-8', 'ISO-8859-1'));
            return json_encode(['content_awb' => mb_convert_encoding($pdfContent, 'UTF-8', 'ISO-8859-1')]);
        } catch (Exception $e) {
            $this->logger->error(__("Get AWB Pdf Error: %1", $e->getMessage()));
            $this->throwWebApiException('Get AWB Pdf Error: ' . $e->getMessage(), Exception::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Validate Params GetPdf
     *
     * @param mixed $data
     * @return void
     * @throws Exception
     */
    private function validateParamsGetPdf(mixed $data): void
    {
        if (!$data ||
            !isset($data['shipment_increment_id']) ||
            !isset($data['store_id'])
        ) {
            $this->throwWebApiException('invalid json format', Exception::HTTP_BAD_REQUEST);
        }

        if (!is_string($data['shipment_increment_id'])) {
            $this->throwWebApiException('shipment_increment_id must be string', Exception::HTTP_BAD_REQUEST);
        }
        if (!is_numeric($data['store_id'])) {
            $this->throwWebApiException('store_id must be int', Exception::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get Shipment By IncrementId
     *
     * @param string $id
     * @param int $storeId
     * @return mixed|void
     * @throws Exception
     */
    public function getShipmentByIncrementId(string $id, int $storeId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $id)->create();
        $shipments = $this->shipmentRepository->getList($searchCriteria)->getItems();

        if (count($shipments) == 0) {
            $this->throwWebApiException(__(
                "The entity that was requested doesn't exist [%1]",
                $id
            ), Exception::HTTP_BAD_REQUEST);
        }

        $shipment = array_values($shipments)[0];
        if ($shipment->getStoreId() != $storeId) {
            $this->throwWebApiException(__(
                "The shipment %1 does not exist in the store with store id %2",
                $id,
                $storeId
            ), Exception::HTTP_BAD_REQUEST);
        }

        return $shipment;
    }

    /**
     * @param $shipmentIncrementId
     * @param $storeId
     * @return void
     * @throws Exception
     */
    private function validateShipment($shipmentIncrementId, $storeId)
    {
        try {
            $shipmentCollection = $this->shipmentCollectionFactory->create();
            $shipmentCollection->addFieldToFilter('increment_id', $shipmentIncrementId);
            $shipmentCollection->join(
                'inventory_shipment_source',
                'main_table.' . 'entity_id' . ' = inventory_shipment_source.shipment_id',
            );
            $shipment = $shipmentCollection->getFirstItem();
            $tracksCollection = $shipment->getTracksCollection();
            $trackNumber = $tracksCollection->getFirstItem()->getTrackNumber();
            if ($trackNumber == null) {
                $this->throwWebApiException("This shipment doesn't have tracking number", Exception::HTTP_BAD_REQUEST);
            }
            if ($storeId != $shipment->getStoreId()) {
                $this->throwWebApiException("You don’t have access for this store", Exception::HTTP_BAD_REQUEST);
            }
            if (!strpos($shipment->getSourceCode(), 'ps') && $shipment->getSourceCode() != 'VOS') {
                $this->throwWebApiException("Please check inventory source is Store again", Exception::HTTP_BAD_REQUEST);
            }
        } catch (Exception $e) {
            $this->logger->error(__("Shipment Increment Id " . $shipmentIncrementId, $e->getMessage()));
            $this->throwWebApiException($e->getMessage(), Exception::HTTP_BAD_REQUEST);
        }
    }
}
