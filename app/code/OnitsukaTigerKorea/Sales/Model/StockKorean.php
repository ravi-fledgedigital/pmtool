<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Sales\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;

/**
 * Web API return order status shipped from ERP
 */
class StockKorean {

    const EVENT_AFTER_ORDER_STATUS_SHIPPED  = 'sales_order_status_after_shipped';

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $commonLogger;

    /**
     * @var \OnitsukaTiger\Logger\Api\Logger
     */
    protected $logger;

    /**
     * StockKorean constructor.
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Psr\Log\LoggerInterface $commonLogger
     * @param \OnitsukaTiger\Logger\Api\Logger $logger
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Psr\Log\LoggerInterface $commonLogger,
        \OnitsukaTiger\Logger\Api\Logger $logger
    ){
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->eventManager = $eventManager;
        $this->commonLogger = $commonLogger;
        $this->logger = $logger;
    }

    /**
     * @param string $id
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function orderShipped($id){
        $this->logger->info('----- orderShipped() start ----- id : ' . $id);
        $shipment = $this->getShipmentByIncrementId($id);
        if($shipment->getOrder()->getStatus() == OrderStatus::STATUS_SHIPPED) {
            $this->logger->error(sprintf('Order %s has status was shipped. Don\'t change order status exist ', $id));
            $this->throwWebApiException(sprintf('Order Status was shipped. Don\'t change order status exist '), 400);
        }
        $this->eventManager->dispatch(self::EVENT_AFTER_ORDER_STATUS_SHIPPED,['shipment' => $shipment]);
    }

    /**
     * @param $id
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function getShipmentByIncrementId($id){
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $id)->create();
        $shipments = $this->shipmentRepository->getList($searchCriteria)->getItems();

        if(count($shipments)) {
            return array_values($shipments)[0];
        }else {
            $this->logger->error(sprintf('external id format is wrong [%s]', $id));
            $this->throwWebApiException(sprintf('external id format is wrong [%s]', $id), 400);
        }
    }

    /**
     * Throw Web API exception and add it to log
     * @param $msg
     * @param $status
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function throwWebApiException($msg, $status)
    {
        $exception = new \Magento\Framework\Webapi\Exception(__($msg), $status);
        $this->commonLogger->critical($exception);
        throw $exception;
    }
}
