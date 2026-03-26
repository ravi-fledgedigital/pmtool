<?php
namespace OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation;

use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Logger\SourceAlgorithm\Logger;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;

class isReAllocate {

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        Logger $logger
    ){
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
    }

    public function execute($searchCriteria){
        $shipments = $this->shipmentRepository->getList($searchCriteria);
        $this->logger->info('Count shipments: ' . count($shipments));
        if(!count($shipments)){
            return true;
        }

        return false;
    }
}
