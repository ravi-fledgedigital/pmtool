<?php

namespace OnitsukaTiger\NetsuiteReturnOrderSync\Observer;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data;
use OnitsukaTiger\NetsuiteReturnOrderSync\Model\CleanShipmentItemSelections;
use OnitsukaTiger\NetsuiteReturnOrderSync\Model\CreateRmaRequest;
use OnitsukaTiger\NetsuiteReturnOrderSync\Model\SaveRmaRequest;
use OnitsukaTiger\NetsuiteReturnOrderSync\Model\SplitRmaRequestProcess;
use OnitsukaTiger\OrderStatus\Model\Shipment;

class RmaSplitRequest implements ObserverInterface
{

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Shipment
     */
    protected $shipmentModel;

    /**
     * @var RequestRepositoryInterface
     */
    protected $requestRepository;

    /**
     * @var SplitRmaRequestProcess
     */
    protected $splitRmaRequestProcess;

    /**
     * @var CleanShipmentItemSelections
     */
    protected $cleanShipmentItemSelections;

    /**
     * @var CreateRmaRequest
     */
    protected $createRmaRequest;

    /**
     * @var SaveRmaRequest
     */
    protected $saveRmaRequest;

    /**
     * @var Data
     */
    protected $rmaHelper;


    /**
     * RmaSplitRequest constructor.
     * @param SaveRmaRequest $saveRmaRequest
     * @param CreateRmaRequest $createRmaRequest
     * @param CleanShipmentItemSelections $cleanShipmentItemSelections
     * @param SplitRmaRequestProcess $splitRmaRequestProcess
     * @param Data $rmaHelper
     * @param RequestRepositoryInterface $requestRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param Shipment $shipmentModel
     */
    public function __construct(
        SaveRmaRequest $saveRmaRequest,
        CreateRmaRequest $createRmaRequest,
        CleanShipmentItemSelections $cleanShipmentItemSelections,
        SplitRmaRequestProcess $splitRmaRequestProcess,
        Data $rmaHelper,
        RequestRepositoryInterface $requestRepository,
        OrderRepositoryInterface $orderRepository,
        Shipment $shipmentModel
    )
    {
        $this->saveRmaRequest = $saveRmaRequest;
        $this->createRmaRequest = $createRmaRequest;
        $this->cleanShipmentItemSelections = $cleanShipmentItemSelections;
        $this->splitRmaRequestProcess = $splitRmaRequestProcess;
        $this->rmaHelper = $rmaHelper;
        $this->requestRepository = $requestRepository;
        $this->orderRepository = $orderRepository;
        $this->shipmentModel = $shipmentModel;
    }

    public function execute(Observer $observer)
    {
        /** @var RequestInterface $request */
        $request = $observer->getEvent()->getData('request');
        $rmaAlgorithmEnabled = $this->rmaHelper->getRmaAlgorithmConfig('enabled', $request->getStoreId());

        if (!$rmaAlgorithmEnabled) {
            return;
        }

        $shipments = $this->shipmentModel->getShipmentsByOrderId($request->getOrderId());
        $order = $this->orderRepository->get($request->getOrderId());
        $splitRmaRequestResult = $this->splitRmaRequestProcess->execute($request, $shipments);

        if ($splitRmaRequestResult['isShippable']) {
            $shipmentItemSelections = $splitRmaRequestResult['shipmentItemSelection'];
            $requests = [];
            foreach ($shipmentItemSelections as $shipmentIncrementId => $shipmentItemSelection) {
                $shipmentItemSelection = $this->cleanShipmentItemSelections->execute($shipmentItemSelection);
                if (empty($shipmentItemSelection)) {
                    continue;
                }
                $request = $this->createRmaRequest->execute($shipmentItemSelection, $shipmentIncrementId, $order);
                $requests[] = $request;
            }
            $this->saveRmaRequest->execute($requests);
        }
    }
}
