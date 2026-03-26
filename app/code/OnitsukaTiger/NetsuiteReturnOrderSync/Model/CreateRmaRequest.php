<?php
namespace OnitsukaTiger\NetsuiteReturnOrderSync\Model;

use Amasty\Rma\Api\RequestRepositoryInterface;

class CreateRmaRequest {

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * CreateRmaRequest constructor.
     * @param RequestRepositoryInterface $requestRepository
     */
    public function __construct(
        RequestRepositoryInterface $requestRepository
    ){
        $this->requestRepository = $requestRepository;
    }

    /**
     * @param $shipmentItemSelection
     * @param $shipmentIncrementId
     * @param $order
     * @return mixed
     */
    public function execute($shipmentItemSelection, $shipmentIncrementId, $order)
    {
        $itemReturn[] = $shipmentItemSelection;
        $request = $this->requestRepository->getEmptyRequestModel();
        $request->setCustomerId($order->getCustomerId())
            ->setOrderId($order->getId())
            ->setShipmentIncrementId($shipmentIncrementId)
            ->setStoreId($order->getStoreId())
            ->setCustomerName(
                $order->getBillingAddress()->getFirstname()
                . ' ' . $order->getBillingAddress()->getLastname()
            );

        $returnItems = [];
        foreach ($shipmentItemSelection as $item) {
            $returnItems[] = $this->requestRepository->getEmptyRequestItemModel()
                ->setQty((float)$item['qtyToDeduct'])
                ->setResolutionId((int)$item['resolution_id'])
                ->setReasonId((int)$item['reason_id'])
                ->setConditionId((int)$item['condition_id'])
                ->setOrderItemId((int)$item['order_item_id'])
                ->setRequestQty((float)$item['request_qty']);
        }

        $request->setRequestItems($returnItems);
        return $request;
    }
}
