<?php

namespace OnitsukaTiger\RestockReports\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class RestockData
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var DirectoryList
     */
    protected $directory;

    /**
     * @var $resourceConnection
     */
    protected $resourceConnection;

    /**
     * @var RestockReportFactory
     */
    protected $restockReportFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Filesystem $filesystem
     * @param TimezoneInterface $timezoneInterface
     * @param ResourceConnection $resourceConnection
     * @param RestockReportFactory $restockReportFactory
     * @param ProductFactory $productFactory
     * @param StoreManagerInterface $storeManager
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        TimezoneInterface $timezoneInterface,
        ResourceConnection $resourceConnection,
        RestockReportFactory $restockReportFactory,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->directory = $filesystem->getDirectoryWrite(
            DirectoryList::VAR_DIR
        );
        $this->filesystem = $filesystem;
        $this->timezoneInterface = $timezoneInterface;
        $this->resourceConnection = $resourceConnection;
        $this->restockReportFactory = $restockReportFactory;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Process to get data
     *
     * @param int $id
     * @return void
     * @throws FileSystemException
     */
    public function process($id = null)
    {
        $date = $this->timezoneInterface->date()->format("Y_m_d h:i:s");
        $fileDirectory = $this->filesystem->getDirectoryWrite(
            DirectoryList::VAR_DIR
        );
        $destinationPath = $fileDirectory->getAbsolutePath(
            "import/restockdata/restockData_" . $date . ".csv"
        );
        $filePath = "import/restockdata/restockData_" . $date . ".csv";
        $this->directory->create("export");
        $stream = $this->directory->openFile($destinationPath, "w+");
        $stream->lock();

        $header = ["product_sku", "added_date", "send_date", "send_count","store_view_code","customer_id","opened_date"];
        $stream->writeCsv($header);

        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName("product_alert_stock");
        $customerTableName = $connection->getTableName("customer_entity");

        $queueData = $this->restockReportFactory->create()->getCollection();
        if (!empty($id)) {
            $queueData->addFieldToFilter("queue_id", ["eq" => $id]);
        }
        $queueData->addFieldToFilter("status", ["eq" => 0]);

        foreach ($queueData as $data) {
            $fromDate = date("Y-m-d H:i:s", strtotime($data->getFromDate()));
            $tDate = date("Y-m-d", strtotime($data->getToDate())) . " 23:59:59";
            $toDate = date("Y-m-d H:i:s", strtotime($tDate));
            $storeid_For_KR= "5";
            // phpcs:ignore
            $query = "SELECT
                    send_date,
                    customer_id,
                    SUM(send_count) AS send_count,
                    add_date AS added_date,
                    opened_date,
                    product_id,
                    " . $tableName . ".store_id,
                    " . $customerTableName . ".email
                    FROM
                        " . $tableName . "
                    LEFT JOIN " . $customerTableName . "
                        ON " . $tableName . ".customer_id = " . $customerTableName . ".entity_id
                    WHERE
                        add_date >= '" . $fromDate . "'
                        AND add_date <= '" . $toDate . "'
                        AND " . $tableName . ".store_id = '" . $storeid_For_KR . "'
                    GROUP BY
                        DATE(add_date),
                        product_id,
                        " . $tableName . ".store_id,
                        " . $customerTableName . ".email";

            $results = $this->resourceConnection->getConnection()->fetchAll($query);
            if (!empty($results)) {
                foreach ($results as $restockData) {
                    $restockDetails = [];
                    $id = $restockData["product_id"];
                    $product = $this->productFactory->create()->load($id);
                    if ($product && $product->getId()) {
                        $restockDetails[] = $product->getSku();
                    } else {
                        $restockDetails[] = $restockData["sku"];
                    }
                    $restockDetails[] = $restockData["added_date"];
                    $restockDetails[] = $restockData["send_date"];
                    $restockDetails[] = $restockData["send_count"];

                    $storeId = $restockData["store_id"];
                    $store = $this->storeManager->getStore($storeId);
                    $restockDetails[] = $store->getCode();
                    $restockDetails[] = $restockData["customer_id"];
                    $restockDetails[] = $restockData["opened_date"];

                    $stream->writeCsv($restockDetails);
                }
            }

            $data->setStatus(1);
            $data->setDownloadRestock($filePath);
            $data->save();
        }
    }
}
