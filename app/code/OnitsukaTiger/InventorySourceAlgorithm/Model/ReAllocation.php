<?php
namespace OnitsukaTiger\InventorySourceAlgorithm\Model;

use Magento\Checkout\Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use OnitsukaTiger\InventorySourceAlgorithm\Helper\Data;
use OnitsukaTiger\InventorySourceAlgorithm\Model\Algorithms\SourceAlgorithmProcess;
use OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation\isReAllocate;
use OnitsukaTiger\Logger\SourceAlgorithm\Logger;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use Symfony\Component\Console\Output\OutputInterface;

class ReAllocation {

    const REALLOCATED = 1;
    const WAIT_REALLOCATED = 0;

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
     * @var DateTime
     */
    protected $date;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var StoreWebsiteRelationInterface
     */
    protected $storeWebsiteRelation;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * ReAllocation constructor.
     * @param Data $helper
     * @param DateTime $date
     * @param CollectionFactory $_orderCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param isReAllocate $isReAllocate
     * @param SourceAlgorithmProcess $sourceAlgorithmProcess
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param Logger $logger
     */
    public function __construct(
        Data $helper,
        DateTime $date,
        CollectionFactory $_orderCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        isReAllocate $isReAllocate,
        SourceAlgorithmProcess $sourceAlgorithmProcess,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        Logger $logger
    ) {
        $this->helper = $helper;
        $this->date = $date;
        $this->_orderCollectionFactory = $_orderCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->isReAllocate = $isReAllocate;
        $this->sourceAlgorithmProcess = $sourceAlgorithmProcess;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->logger = $logger;
    }


    /**
     * @param null $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($ids = null){
        $timeOrderReallocation = $this->helper->getGeneralConfig('time_order_reallocation');
        $dateTime = $this->date->gmtDate();
        // subtract 30 minutes from the current time
        $new = strtotime($dateTime) - $timeOrderReallocation*60;
        $lastTimeReject = date('Y-m-d H:i:s',$new);

        $orderColStockPending = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('status',['eq' => OrderStatus::STATUS_STOCK_PENDING])
            ->addFieldToFilter('order_verify_reallocate', ['eq' => self::WAIT_REALLOCATED])
            ->addFieldToFilter('last_time_reject', ['lt' => $lastTimeReject]);

        if(!is_null($ids)){
            $ids = $this->storeWebsiteRelation->getStoreByWebsiteId($ids);
            $orderColStockPending ->addFieldToFilter('store_id', ['in' => $ids]);
        }
        $sum = 0;
        $errors = [];

        foreach ($orderColStockPending as $order) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('order_id', $order->getId())->create();

            if($this->isReAllocate->execute($searchCriteria)){
                $this->output(sprintf("<info>Begin generate shipment, order : %s </info>", $order->getIncrementId()));
                $this->logger->info(sprintf("Begin generate shipment, order : %s", $order->getIncrementId()));
                try {
                    $result = $this->sourceAlgorithmProcess->execute($order);
                } catch (\Throwable $e){
                    $errors[] = [
                        'id' => $order->getIncrementId(),
                        'msg' => $e->getMessage()
                    ];
                    $result = null;
                }
                if(!is_null($result)) {
                    $sum++;
                    $this->output(sprintf("Order: %s was generated %s shipment", $order->getIncrementId(), $result));
                    $this->logger->info(sprintf("Order: %s was generated %s shipment", $order->getIncrementId(), $result));
                    $order->setData('order_verify_reallocate', self::REALLOCATED)->save();
                }
            }
        }

        if($sum){
            $this->output(sprintf("Sum %s orders was reallocated", $sum));
            $this->logger->info(sprintf("Sum %s orders was allocated", $sum));
        }else {
            $this->output('No order found');
            $this->logger->info('No order found');
        }

        if (!empty($errors)) {
            foreach ($errors as $error){
                $this->output(sprintf("Error Order %s : %s ",$error['id'], $error['msg']));
            }
        }
    }

    public function setOutput(OutputInterface $output) {
        $this->output = $output;
    }

    public function output(string $msg) {
        if($this->output) {
            $this->output->writeln($msg);
        }
    }
}
