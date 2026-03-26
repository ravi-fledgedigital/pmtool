<?php
/**
 * Copy my Magento
 */
namespace OnitsukaTiger\Shipment\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Status
 * @package OnitsukaTiger\Shipment\Ui\Component\Listing\Column
 */
class Status extends Column
{
    /**
     * @var \OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes\CollectionFactory
     */
    protected $shipmentAttributesCollectionFactory;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $shipmentModel;

    /**
     * Status constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes\CollectionFactory $shipmentAttributesCollectionFactory
     * @param \Magento\Sales\Model\Order\Shipment $shipmentModel
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes\CollectionFactory $shipmentAttributesCollectionFactory,
        \Magento\Sales\Model\Order\Shipment $shipmentModel,
        array $components = [],
        array $data = []
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentAttributesCollectionFactory = $shipmentAttributesCollectionFactory;
        $this->shipmentModel = $shipmentModel;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $shipment = $this->shipmentModel->loadByIncrementId($item['increment_id']);
                $shipmentStatus = $this->getShipmentStatus($shipment->getId());
                if (!empty($shipmentStatus)) {
                    $item['status'] = $this->displayStatusLabel($shipmentStatus);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get shipment status function
     * @param $shipmentId
     * @return array
     */
    public function getShipmentStatus($shipmentId)
    {
        $shipmentAttributesCollection = $this->shipmentAttributesCollectionFactory->create()->addFieldToFilter('shipment_id', $shipmentId);
        return $shipmentAttributesCollection->getColumnValues('status');
    }

    /**
     * @param array $status
     * @return array
     */
    public function displayStatusLabel($status)
    {
        $label = [];
        foreach ($status as $sts) {
            if (!empty($sts)) {
                $sts = str_replace('_', ' ', $sts);
                $label[] = ucwords($sts);
            }
        }
        return $label;
    }
}
