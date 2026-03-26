<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\PartialCancel\Plugin\Shipment;

use Magento\Backend\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Shipping\Block\Adminhtml\View;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;

/**
 * Class PluginAddPartialCancelButton
 * @package OnitsukaTigerKorea\PartialCancel\Plugin\Shipment
 */
class PluginAddPartialCancelButton
{
    /**
     * Show button config path
     */
    const SHOW_BUTTON_CONFIG = 'korean_address/shipment/cancel_partially';

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
        $message = __('Are you sure you want to partial cancel this shipment? It does not export Cancel.xml');

        if ($this->isShowButton($shipment->getOrder()->getStoreId())
            && ShipmentStatus::STATUS_PROCESSING === $shipment->getExtensionAttributes()->getStatus()
            && $shipment->getOrder()->getOrderSynced()
            && !$shipment->getOrder()->getCancelXmlSynced()
        ) {
            $subject->addButton(
                'cancel-partial-shipment',
                ['label' => __('Partial Delete'), 'onclick' => 'confirmSetLocation(\'' . $message . '\',\'' . $this->getPartialCancelUrl($shipmentId, $subject) . '\')', 'class' => 'cancel-partial-shipment'],
                -1
            );
        }

        return $result;
    }

    /**
     * @param $shipmentId
     * @param View $subject
     * @return string
     */
    private function getPartialCancelUrl($shipmentId, View $subject)
    {
        return $this->data->getUrl(
            'cancel_shipment/shipment/cancel',
            [
                'shipment_id' => $shipmentId,
                'come_from' => $subject->getRequest()->getParam('come_from'),
                'is_partial_cancel' => true
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
