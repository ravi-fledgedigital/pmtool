<?php
namespace OnitsukaTiger\InventorySourceAlgorithm\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use OnitsukaTiger\InventorySourceAlgorithm\Helper\Data;
use OnitsukaTiger\InventorySourceAlgorithm\Model\Algorithms\SourceAlgorithmProcess;
use OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation\isReAllocate;
use OnitsukaTiger\Logger\SourceAlgorithm\Logger;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;

class Allocation
{
    const ALLOCATION_DELAY = 180;

    /**
     * @var CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var isReAllocate
     */
    protected $isReAllocate;

    /**
     * @var SourceAlgorithmProcess
     */
    protected $sourceAlgorithmProcess;

    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var StoreWebsiteRelationInterface
     */
    protected $storeWebsiteRelation;

    /**
     * @var \OnitsukaTiger\PreOrders\Helper\PreOrder
     */
    protected $preOrderHelper;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * ReAllocation constructor.
     * @param Data $helper
     * @param CollectionFactory $_orderCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param isReAllocate $isReAllocate
     * @param SourceAlgorithmProcess $sourceAlgorithmProcess
     * @param Logger $logger
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Data $helper,
        CollectionFactory $_orderCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        isReAllocate $isReAllocate,
        SourceAlgorithmProcess $sourceAlgorithmProcess,
        Logger $logger,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        \OnitsukaTiger\PreOrders\Helper\PreOrder $preOrderHelper,
        ResourceConnection $resourceConnection
    ) {
        $this->helper = $helper;
        $this->_orderCollectionFactory = $_orderCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->isReAllocate = $isReAllocate;
        $this->sourceAlgorithmProcess = $sourceAlgorithmProcess;
        $this->logger = $logger;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->preOrderHelper = $preOrderHelper;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param null $ids
     * @throws LocalizedException
     */
    public function execute($ids = null)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/pre_order_shipment.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $orderColProcessing = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('status', ['in' => [OrderStatus::STATUS_PROCESSING, 'pre_order_processing']]);

        $orderColProcessing->getSelect()->join(
            ['invoice' => 'sales_invoice'],
            'main_table.entity_id = invoice.order_id',
            ['invoice.created_at']
        )->where(
            new \Zend_Db_Expr('TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `invoice`.`created_at`)) >= ' . self::ALLOCATION_DELAY)
        )->group('main_table.entity_id');

        if (!is_null($ids)) {
            $ids = $this->storeWebsiteRelation->getStoreByWebsiteId($ids);
            $orderColProcessing->getSelect()->where('main_table.store_id IN (' . implode(',', $ids) . ')');
        }

        $sum = 0;
        $this->logger->info("================ Start Debug Source Algorithm ================ ");
        $errors = [];


        foreach ($orderColProcessing as $order) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('order_id', $order->getId())->create();

            if (
                !$order->canInvoice()
                && $this->isReAllocate->execute($searchCriteria)
            ) {
                $isPreOrder = false;
                $logger->info("====== auto shipment logger start from inventory allocation ====== ");

                $logger->info("order ID  - ".$order->getIncrementId());

                $orderItems = $order->getAllVisibleItems();
                foreach ($orderItems as $orderItem) {
                    if ($orderItem->getProductType()
                        === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
                    ) {
                        $simpleOrderItem = $this->preOrderHelper->getConfigurableProductChildItem($orderItem);
                        if ($simpleOrderItem) {
                            $storeId = $order->getStoreId();
                            $logger->info("order Store Id  - ".$storeId);
                            $logger->info("order product Id  - ".$simpleOrderItem->getProductId());

                            $isPreOrderProduct = $this->preOrderHelper->checkPreOrderForShipment($simpleOrderItem->getProductId(), $storeId);

                            if($isPreOrderProduct) {
                                $isPreOrder =  true;
                                break;
                            }
                        }
                    }
                }

                $logger->info("auto shipment & is pre order  - ".$isPreOrder);

                if ($isPreOrder) {
                    $logger->info("inside condition called");
                    $logger->info("====== auto shipment logger end from inventory allocation ====== ");
                    continue;
                }

                $this->output(sprintf("<info>Begin generate shipment, order : %s </info>", $order->getIncrementId()));
                $this->logger->info("================ Begin Source Algorithm with order " . $order->getIncrementId() . " =================");
                try {
                    $result = $this->sourceAlgorithmProcess->execute($order);
                } catch (\Throwable $e) {
                    $errors[] = [
                        'id' => $order->getIncrementId(),
                        'msg' => $e->getMessage()
                    ];
                    $result = null;
                }
                if (!is_null($result)) {
                    $sum++;
                    $msg_success = sprintf("Order: %s was generated %s shipment", $order->getIncrementId(), $result);
                    $this->output($msg_success);
                    $this->logger->info($msg_success);
                    $this->logger->info("================ End Source Algorithm with order " . $order->getIncrementId() . " =================");
                }
            }
        }

        if ($sum) {
            $this->output(sprintf("Sum %s orders was allocated", $sum));
            $this->logger->info(sprintf("Sum %s orders was allocated", $sum));
        } else {
            $this->output('No order found');
            $this->logger->info('No order found');
        }

        $this->logger->info("================ End Debug Source Algorithm ================ ");

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->output(sprintf("Error Order %s : %s ", $error['id'], $error['msg']));
            }
        }
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function output(string $msg)
    {
        if ($this->output) {
            $this->output->writeln($msg);
        }
    }
}
