<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\SftpImportExport\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Psr\Log\LoggerInterface;

class SftpImport {

    /**
     * Event : send email delivered to customer
     */
    const EVENT_SEND_EMAIL_DELIVERED = 'netsuite_update_order_status_delivered';

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * ShipmentStatus constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepositoryInterface $shipmentRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * @param ShipmentInterface $shipment
     * @param array $status
     * @return bool
     */
    public function validateShipment(ShipmentInterface $shipment, array $status): bool
    {
        $ext = $shipment->getExtensionAttributes();
        if (!in_array($ext->getStatus(), $status)) {
            return false;
        }
        return true;
    }

    /**
     * @param $qty
     * @return bool
     */
    public function validateProductQtyNegative($qty): bool
    {
        if($qty < 0) {
            return false;
        }
        return true;
    }

    /**
     * @param $value
     * @return string
     */
    public function removePrefix($value): string
    {
        $value = substr($value, 1);
        return ltrim($value, '0');
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function getShipmentByIdWithSearchCriteria($id){
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('entity_id', $id)->create();
        $shipments = $this->shipmentRepository->getList($searchCriteria)->getItems();

        if(count($shipments)) {
            return array_values($shipments)[0];
        }
        return null;
    }
}
