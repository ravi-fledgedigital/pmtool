<?php
namespace OnitsukaTiger\Rma\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;

/**
 * @codeCoverageIgnore
 * phpcs:ignoreFile
 */

class RMANetSuiteSyncStatus extends \Magento\Backend\Block\Template
{
    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = "OnitsukaTiger_Rma::rmanetsuitesyncstatus.phtml";

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ResourceConnection $resourceConnection
     * @param RequestInterface $request
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection,
        RequestInterface $request,
        OrderItemRepositoryInterface $orderItemRepository,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->request = $request;
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    /**
     * Method to get data based on request_id
     *
     * @return string
     */
    public function getRmaNetSuiteSyncStatus()
    {
        // Get request parameter
        $requestId = (int) $this->request->getParam("request_id");

        // Get connection
        $connection = $this->resourceConnection->getConnection();

        // Get table name
        $tableName = $connection->getTableName("onitsukatiger_cegid_returnaction");

        // Prepare query
        $query = "SELECT * FROM " . $tableName . " WHERE request_id = :request_id";

        // Execute query and fetch result
        $result = $connection->fetchRow($query, ["request_id" => $requestId]);

        // Check if result is empty and return appropriate message
        if (empty($result)) {
            $orderItems = $this->getOrderItemsByRequestId($requestId);
            $productDetails = $this->getProductDetailsByOrderItemIds(array_column($orderItems, 'order_item_id'));

            $skusWithEmptyEan = []; // Array to store SKUs with empty EAN codes
            $errorMessages = $this->getCegidSyncedErrorMessage($requestId);

            // Check if any EAN code is empty
            foreach ($productDetails as $details) {
                if (!empty($details) && empty($details["ean_code"]) && !empty($details["sku"])) {
                    $skusWithEmptyEan[] = $details["sku"]; // Add SKU to the array
                }
            }

            // Return appropriate message with list of SKUs with empty EAN codes
            if (!empty($skusWithEmptyEan)) {
                $skuList = implode(", ", $skusWithEmptyEan);
                return "RMA not Synced with Cegid </br> Reason : EAN number not found for the following SKUs: <strong>" . $skuList . "</strong>";
            } else {
                return !empty($errorMessages) ? $errorMessages : "Unknown error during sync.";
            }
        } else {
            return "RMA Synced with Cegid";
        }
    }

    public function getStoreIdByRequestId()
    {
        $requestId = (int) $this->request->getParam("request_id");

        // Get connection
        $connection = $this->resourceConnection->getConnection();

        // Get table name
        $tableName = $connection->getTableName("amasty_rma_request");

        // Prepare query
        $query = "SELECT store_id FROM " . $tableName . " WHERE request_id = :request_id";
        // Execute query and fetch result
        $result = $connection->fetchRow($query, ["request_id" => $requestId]);

        if (!empty($result) && isset($result['store_id'])) {
            return $result['store_id'];
        }

        return '';
    }

    /**
     * Method to get order item IDs based on request ID
     *
     * @param int $requestId
     * @return array
     */
    public function getOrderItemsByRequestId($requestId)
    {
        // Get connection
        $connection = $this->resourceConnection->getConnection();

        // Get table name
        $tableName = $connection->getTableName("amasty_rma_request_item");

        // Prepare query
        $query = "SELECT order_item_id FROM " . $tableName . " WHERE request_id = :request_id";

        // Execute query and fetch result
        $orderItems = $connection->fetchAll($query, ["request_id" => $requestId]);

        // Return the result set
        return $orderItems;
    }

    /**
     * Method to get Cegid synced error message based on request ID
     *
     * @param int $requestId
     * @return string|null
     */
    public function getCegidSyncedErrorMessage($requestId)
    {
        // Get connection
        $connection = $this->resourceConnection->getConnection();

        // Get table name
        $tableName = $connection->getTableName("amasty_rma_request");

        // Prepare query
        $query = "SELECT cegid_synced_error_message FROM " . $tableName . " WHERE request_id = :request_id";

        // Execute query and fetch result
        $result = $connection->fetchOne($query, ["request_id" => $requestId]);

        // Return the error message
        return $result;
    }

    /**
     * Get product details by array of order item IDs
     *
     * @param array $orderItemIds
     * @return array
     */
    public function getProductDetailsByOrderItemIds($orderItemIds)
    {
        $productDetails = [];
        foreach ($orderItemIds as $orderItemId) {
            try {
                $orderItem = $this->orderItemRepository->get($orderItemId);
                $sku = $orderItem->getSku();
                $productDetails[$orderItemId] = $this->getProductBySku($sku);
            } catch (NoSuchEntityException $e) {
                $productDetails[$orderItemId] = null;
            }
        }
        return $productDetails;
    }

    /**
     * Get product details by SKU
     *
     * @param string $sku
     * @return array|null
     */
    public function getProductBySku($sku)
    {
        try {
            // Retrieve product by SKU
            $product = $this->productRepository->get($sku);

            return [
                'sku' => $product->getSku(),
                'ean_code' => $product->getCustomAttribute('ean_code') ? $product->getCustomAttribute('ean_code')->getValue() : null,
            ];
        } catch (NoSuchEntityException $e) {
            // Handle the case when the product with the given SKU does not exist
            return null;
        }
    }
}
