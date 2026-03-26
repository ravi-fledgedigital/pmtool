<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

use Amasty\Rma\Api\Data\RequestInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Amasty\Rma\Api\RequestRepositoryInterface;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

/**
 * Class Import Receiving | This I/F add tracking number to RMA.
 * @package OnitsukaTigerKorea\SftpImportExport\Model\SftpImport
 */
class ImportReceiving extends SftpImport {

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var TrackFactory
     */
    protected $_shipmentTrackFactory;

    /**
     * @var RequestRepositoryInterface
     */
    protected $rmaRequest;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * ImportShipping constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param TrackFactory $shipmentTrackFactory
     * @param RequestRepositoryInterface $requestRepository
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        TrackFactory $shipmentTrackFactory,
        RequestRepositoryInterface $requestRepository,
        Logger $logger
    ){
        $this->orderRepository = $orderRepository;
        $this->_shipmentTrackFactory = $shipmentTrackFactory;
        $this->rmaRequest = $requestRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        if($data){
            $result['Rma'] = [];
            foreach($data as $key => $rma) {
                $rmaId = $this->removePrefix($rma['rtn_order_no']);
                $orderId = $this->removePrefix($rma['origin_order_no']);
                $trackingNo = $rma['tracking_no'];
                try {
                    $rma = $this->rmaRequest->getById($rmaId);
                    $order = $this->orderRepository->get($orderId);
                    if(count($rma->getTrackingNumbers()) > 0) {
                        $result['Rma'][$rmaId] = [
                            'status' => 'fail',
                            'message' => 'Message: Tracking number already exist'
                        ];
                        $this->logger->debug(sprintf('Message: Rma id [%s] has tracking number already exist', $rma->getRequestId()));
                    }else {
                        $result['Rma'][$rmaId] =  $this->updateTrackOrderToRma($rma, $order, $trackingNo);
                    }

                }catch (\Exception $e) {
                    $result['Rma'][$rmaId] = [
                        'status' => 'fail',
                        'message' => 'Message:' . $e->getMessage()
                    ];
                    $this->logger->debug(sprintf('Rma id [%s] has something wrong: [%s]', $rmaId, $e->getMessage()));
                }
            }
            return $result;
        }
    }

    /**
     * @param RequestInterface $rma
     * @param OrderInterface $order
     * @param $trackingNumber
     * @return array
     */
    public function updateTrackOrderToRma(RequestInterface $rma, OrderInterface $order, $trackingNumber): array
    {
        try {
            if ($rma) {
                $tracking = $this->rmaRequest->getEmptyTrackingModel();
                $tracking->setTrackingCode('post_delivery_service')
                    ->setTrackingNumber($trackingNumber)
                    ->setIsCustomer(false)
                    ->setRequestId($rma->getRequestId());
                $this->rmaRequest->saveTracking($tracking);
                $order->addCommentToStatusHistory('Event: Add tracking number '. $trackingNumber. ' to Rma request ' . $rma->getRequestId())
                    ->save();
                $this->logger->debug(sprintf('Message: Rma id [%s] add tracking number success', $rma->getRequestId()));
                return [
                    'status' => 'success',
                    'message' => 'Message: Add tracking number success'
                ];
            }
        } catch (\Exception $e) {
            $this->logger->debug(sprintf('Message: Rma id [%s] add tracking number fail', $rma->getRequestId()));
            return [
                'status' => 'fail',
                'message' => 'Message: Add tracking number fail'
            ];
        }
    }
}
