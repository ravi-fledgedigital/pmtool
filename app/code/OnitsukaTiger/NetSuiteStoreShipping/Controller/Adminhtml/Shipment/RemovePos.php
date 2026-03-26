<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Logger\StoreShipping\Logger;

class RemovePos extends \Magento\Backend\App\Action
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Action\Context $context
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Registry $registry
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface  $orderRepository,
        Action\Context $context,
        ShipmentRepositoryInterface $shipmentRepository,
        Registry $registry,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->registry = $registry;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Remove pos receipt number from shipment
     *
     * @return void
     */
    public function execute()
    {
        $shipmentId = (int) $this->getRequest()->getParam('shipment_id');
        try {
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $this->shipmentRepository->get($shipmentId);
            if ($shipment) {
                $this->registry->register('current_shipment', $shipment);
                $posNumber = $shipment->getExtensionAttributes()->getPosReceiptNumber();
                $shipment->getExtensionAttributes()->setPosReceiptNumber(null);
                $this->shipmentRepository->save($shipment);
                $shipment->getOrder()->addCommentToStatusHistory(sprintf('Staff have remove POS receipt number: %s', $posNumber));
                $this->orderRepository->save($shipment->getOrder());

                $this->_view->loadLayout();
                $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shipments'));
                $response = $this->_view->getLayout()->getBlock('shipment_pos')->toHtml();
            } else {
                $response = [
                    'error' => true,
                    'message' => __('We can\'t initialize shipment for delete pos receipt number.'),
                ];
            }
        } catch (LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
            $this->logger->error(sprintf('SPS: Error remove POS shipment [%s]. Message: [%s]', $shipmentId, $e->getMessage()));
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We can\'t delete pos receipt number.')];
            $this->logger->error(sprintf('We can\'t delete pos receipt number shipment %s. Error: %s', $shipmentId, $e->getMessage()));
        }

        if (is_array($response)) {
            $response = $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($response);
            $this->getResponse()->representJson($response);
        } else {
            $this->getResponse()->setBody($response);
        }
    }
}
