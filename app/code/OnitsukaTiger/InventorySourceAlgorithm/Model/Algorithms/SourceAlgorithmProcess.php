<?php
namespace OnitsukaTiger\InventorySourceAlgorithm\Model\Algorithms;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use OnitsukaTiger\InventorySourceAlgorithm\Helper\Data;
use Magento\InventoryShipping\Model\InventoryRequestFromOrderFactory;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use OnitsukaTiger\InventorySourceAlgorithm\Model\CreateShipment;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use OnitsukaTiger\Logger\SourceAlgorithm\Logger;

class SourceAlgorithmProcess {

    /**
     * @var array
     */
    private $sources = [];

    /**
     * @var InventoryRequestFromOrderFactory
     */
    protected $inventoryRequestFromOrderFactory;

    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * @var CreateShipment
     */
    protected $shipment;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Logger
     */
    protected $logger;


    /**
     * SourceAlgorithmProcess constructor.
     * @param SourceRepositoryInterface $sourceRepository
     * @param InventoryRequestFromOrderFactory $inventoryRequestFromOrderFactory
     * @param SourceSelectionServiceInterface $sourceSelectionService
     * @param CreateShipment $shipment
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository,
        InventoryRequestFromOrderFactory $inventoryRequestFromOrderFactory,
        SourceSelectionServiceInterface $sourceSelectionService,
        CreateShipment $shipment,
        OrderRepositoryInterface $orderRepository,
        Data $helper,
        Logger $logger
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->inventoryRequestFromOrderFactory = $inventoryRequestFromOrderFactory;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->shipment = $shipment;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * @param Order $order
     * @return $this|int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Order $order) {
        $algorithmCode = $this->helper->getGeneralConfig('algorithm_code', $order->getStoreId());

        if(!$this->helper->getGeneralConfig('enabled', $order->getStoreId())){
            return $this;
        }
        $inventoryRequest = $this->inventoryRequestFromOrderFactory->create($order);
        $sourceSelectionResult = $this->sourceSelectionService->execute($inventoryRequest, $algorithmCode);
        if(!$sourceSelectionResult->isShippable()){
            $sourceSelectionResult = $this->sourceSelectionService->execute($inventoryRequest, 'priority');
        }

        if($this->helper->getGeneralConfig('create_shipment', $order->getStoreId())){
            $result = array();
            foreach($order->getAllVisibleItems() as $itemOrder){
                $orderItem = $itemOrder->getItemId();
                foreach ($sourceSelectionResult->getSourceSelectionItems() as $item) {
                    if ($item->getSku() === $itemOrder->getSku()) {
                        $result[$orderItem][] = [
                            'sourceName' => $this->getSourceName($item->getSourceCode()),
                            'sourceCode' => $item->getSourceCode(),
                            'qtyAvailable' => $item->getQtyAvailable(),
                            'qtyToDeduct' => $item->getQtyToDeduct()
                        ];
                    }
                }
            }

            $this->logger->info('SourceSelectionResult', $result);

            $shipments = [];
            foreach($result as $key => $items) {
                foreach($items as $item) {
                    $shipments[$item['sourceCode']][$key] = $item['qtyToDeduct'];
                }
            }

            $this->logger->info('shipments', $shipments);
            $count = 0;
            foreach ($shipments as $sourceCode => $shipmentItems) {
                foreach($shipmentItems as $itemId => $qty) {
                    if($qty == 0) {
                        unset($shipmentItems[$itemId]);
                    }
                }

                if(count($shipmentItems)) {
                    $count++;
                    $this->shipment->createShipment($order, $shipmentItems, $sourceCode);
                }
            }

            if($count > 0) {
                $order->addCommentToStatusHistory(sprintf('Order has created %s shipment.', $count ));
            }else {
                $order->setStatus(\OnitsukaTiger\OrderStatus\Model\OrderStatus::STATUS_STOCK_PENDING)
                    ->addCommentToStatusHistory('Order cannot created any shipment because all products are out of stock.');
            }
            $this->orderRepository->save($order);
            return $count;
        }
        return 0;
    }

    /**
     * Get source name by code
     *
     * @param string $sourceCode
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getSourceName(string $sourceCode): string
    {
        if (!isset($this->sources[$sourceCode])) {
            $this->sources[$sourceCode] = $this->sourceRepository->get($sourceCode)->getName();
        }

        return $this->sources[$sourceCode];
    }
}
