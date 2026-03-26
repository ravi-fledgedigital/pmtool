<?php
declare(strict_types=1);

namespace OnitsukaTiger\CancelShipment\Plugin\Shipment;

use Magento\Backend\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Shipping\Block\Adminhtml\View;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTigerKorea\Shipping\Model\PartialCancel\IsPartialCancel;

/**
 * Class PluginAddCancelButton
 * @package OnitsukaTiger\CancelShipment\Plugin\Shipment
 */
class PluginAddCancelButton
{
    /**
     * Show button config path
     */
    const SHOW_BUTTON_CONFIG = 'cancel_shipment/general/show_button';

    /**
     * @var Data
     */
    private $data;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * PluginAfter constructor.
     * @param Data $data
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data $data,
        ShipmentRepositoryInterface $shipmentRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->data = $data;
        $this->shipmentRepository = $shipmentRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param View $subject
     * @param $result
     * @return mixed
     */
    public function afterGetBackUrl(View $subject, $result)
    {
        $shipmentId = $subject->getRequest()->getParam('shipment_id');
        $shipment = $this->shipmentRepository->get($shipmentId);
        $message = __('Are you sure you want to cancel this shipment?');

        if ($this->isShowButton($shipment->getOrder()->getStoreId()) && ShipmentStatus::STATUS_PROCESSING === $shipment->getExtensionAttributes()->getStatus()) {
            $subject->addButton(
                'cancel-shipment',
                ['label' => __('Delete'), 'onclick' => 'confirmSetLocation(\'' . $message . '\',\'' . $this->getCancelUrl($shipmentId, $subject) . '\')', 'class' => 'cancel-shipment'],
                -1
            );
            if($shipment->getOrder()->getCancelXmlSynced() == IsPartialCancel::NUMBER_DELETE){
                $subject->removeButton('cancel-shipment');
            }
        }

        return $result;
    }

    /**
     * @param $shipmentId
     * @param View $subject
     * @return string
     */
    private function getCancelUrl($shipmentId, View $subject)
    {
        return $this->data->getUrl(
            'cancel_shipment/shipment/cancel',
            [
                'shipment_id' => $shipmentId,
                'come_from' => $subject->getRequest()->getParam('come_from')
            ]
        );
    }

    /**
     * @param $storeId
     * @return mixed
     */
    private function isShowButton($storeId)
    {
        return $this->scopeConfig->getValue(self::SHOW_BUTTON_CONFIG, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
