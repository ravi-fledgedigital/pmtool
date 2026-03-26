<?php
namespace OnitsukaTiger\Rma\Helper;

class NotDelivered
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Amasty\Rma\Api\RequestRepositoryInterface
     */
    protected $requestRepository;
    /**
     * @var \Amasty\Rma\Api\StatusRepositoryInterface
     */
    protected $rmaStatusRepository;
    /**
     * @var \OnitsukaTiger\NetSuite\Model\SuiteTalk\NotDelivered
     */
    protected $notDelivered;

    /**
     * @var \OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping
     */
    protected $storeShipping;

    /**
     * NotDelivered constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Amasty\Rma\Api\RequestRepositoryInterface $requestRepository
     * @param \Amasty\Rma\Api\StatusRepositoryInterface $rmaStatusRepository
     * @param \OnitsukaTiger\NetSuite\Model\SuiteTalk\NotDelivered $notDelivered
     * @param \OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping $storeShipping
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Amasty\Rma\Api\RequestRepositoryInterface $requestRepository,
        \Amasty\Rma\Api\StatusRepositoryInterface $rmaStatusRepository,
        \OnitsukaTiger\NetSuite\Model\SuiteTalk\NotDelivered $notDelivered,
        \OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping $storeShipping
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->requestRepository = $requestRepository;
        $this->rmaStatusRepository = $rmaStatusRepository;
        $this->notDelivered = $notDelivered;
        $this->storeShipping = $storeShipping;
    }

    /**
     * Make not delivered RMA request
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $conNo
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function makeNotDeliveredRequest(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $conNo
    ){
        $order = $shipment->getOrder();

        $id = $this->scopeConfig->getValue('netsuite/api/rma_not_delivered');
        $status = $this->rmaStatusRepository->getById($id);

        $request = $this->requestRepository->getEmptyRequestModel();
        $requestItems = array();
        foreach($shipment->getItems() as $shipmentItem) {
            $request->setNote('Not Delivered : ' . $conNo)
                ->setStatus($status->getStatusId())
                ->setCustomerId($order->getCustomerId())
                ->setManagerId('')
                ->setOrderId($order->getId())
                ->setStoreId($order->getStoreId())
                ->setShipmentIncrementId($shipment->getIncrementId())
                ->setCustomerName(
                    $order->getBillingAddress()->getFirstname()
                    . ' ' . $order->getBillingAddress()->getLastname()
                );

            $orderItems = $shipment->getOrder()->getAllItems();
            $shipmentChildItem = $shipmentItem;
            foreach($orderItems as $orderItem) {
                if($orderItem->getParentItemId() == $shipmentItem->getOrderItemId()) {
                    $shipmentChildItem = $orderItem;
                    break;
                }
            }

            $requestItem = $this->requestRepository->getEmptyRequestItemModel();
            $requestItem->setItemStatus(0)
                ->setOrderItemId($shipmentChildItem->getItemId())
                ->setConditionId(1) // Unopened
                ->setReasonId(1) // Wrong Product Delivered
                ->setResolutionId(2) // Return
                ->setRequestQty($shipmentItem->getQty())
                ->setQty($shipmentItem->getQty());

            $requestItems[] = $requestItem;

        }
        $request->setRequestItems($requestItems);
        $this->requestRepository->save($request);
        // sync to NetSuite
        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        // If ship from WH, sync to NetSuite
        if ($this->storeShipping->isShippingFromWareHouse($sourceCode)) {
            $this->notDelivered->execute($shipment, $request);
        }

    }
}
