<?php

namespace OnitsukaTiger\Cegid\Cron;

use OnitsukaTiger\Cegid\Model\SourceDeductionWhenUpdateOrderInformationSuccess;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Cegid\Model\ShipmentUpdate;

class UpdateShipmentAndCreateReservation
{
    /**
     * @var SourceDeductionWhenUpdateOrderInformationSuccess
     */
    protected $sourceDeduction;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param SourceDeductionWhenUpdateOrderInformationSuccess $sourceDeduction
     * @param ResourceConnection $resourceConnection
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        SourceDeductionWhenUpdateOrderInformationSuccess $sourceDeduction,
        ResourceConnection $resourceConnection,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepositoryInterface $shipmentRepository
    ) {
        $this->sourceDeduction = $sourceDeduction;
        $this->resourceConnection = $resourceConnection;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * Execute
     *
     * @return $this
     */
    public function execute(): static
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('cegid_shipment_status', ShipmentUpdate::CEGID_SHIPMENT_NO_SYNC)->create();
        $shipments = $this->shipmentRepository->getList($searchCriteria)->getItems();

        if (count($shipments)) {
            $ids = [];
            foreach ($shipments as $shipment) {
                $this->sourceDeduction->execute($shipment);
                $ids[] = $shipment->getEntityId();
            }

            $connection = $this->resourceConnection->getConnection();
            $table = $connection->getTableName('sales_shipment');
            $where = "`entity_id` IN (".implode(',', $ids).")";
            $connection->update(
                $table,
                ['cegid_shipment_status' => ShipmentUpdate::CEGID_SHIPMENT_SYNC],
                $where
            );
        }
        return $this;
    }
}
