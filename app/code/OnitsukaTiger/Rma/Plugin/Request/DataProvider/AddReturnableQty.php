<?php
declare(strict_types=1);

namespace OnitsukaTiger\Rma\Plugin\Request\DataProvider;

use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Model\Order\CreateReturnProcessor;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use OnitsukaTiger\Rma\Helper\Data;

class AddReturnableQty
{
    /**
     * @var OrderItemRepositoryInterface
     */
    protected OrderItemRepositoryInterface $orderItemRepository;

    /**
     * @var RequestRepositoryInterface
     */
    protected RequestRepositoryInterface $rmaRequestRepository;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var Data
     */
    protected Data $helperRma;

    /**
     * @var CreateReturnProcessor
     */
    private CreateReturnProcessor $createReturnProcessor;

    /**
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param RequestRepositoryInterface $rmaRequestRepository
     * @param RequestInterface $request
     * @param Data $helperRma
     * @param CreateReturnProcessor $createReturnProcessor
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        RequestRepositoryInterface $rmaRequestRepository,
        RequestInterface $request,
        Data $helperRma,
        CreateReturnProcessor $createReturnProcessor
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->rmaRequestRepository = $rmaRequestRepository;
        $this->request = $request;
        $this->helperRma = $helperRma;
        $this->createReturnProcessor = $createReturnProcessor;
    }

    public function afterGetData(\Amasty\Rma\Model\Request\DataProvider\Form $subject, $result): array
    {
        if ($this->helperRma->getIsShowSyncButton($result['items'][0]['store_id'])) {
            $requestId = $this->request->getParam('request_id');
            $returnItems = [];
            foreach ($result[$requestId]['return_items'] as $returnItem) {
                $returnableQty = $this->setReturnableQty($returnItem[0], $result['items'][0]['order_id']);
                $returnItems[$returnItem[0]['order_item_id']][] = array_merge($returnItem[0], $returnableQty);
            }
            $result[$requestId]['return_items'] = array_merge($returnItems);
        }
        return $result;
    }

    /**
     * @param $returnItem
     * @param $orderId
     * @return array|null
     */
    public function setReturnableQty($returnItem, $orderId): ?array
    {
        $orderItem = $this->orderItemRepository->get($returnItem['order_item_id']);
        $alreadyRequestedItem = $this->createReturnProcessor->getAlreadyRequestedItems($orderId);
        $qtyShipped = $orderItem->getParentItem()->getQtyShipped();
        $qtyCanceled = $orderItem->getParentItem()->getQtyCanceled();
        $rmaQty = 0;

        if (isset($alreadyRequestedItem[$orderItem->getItemId()]['qty'])) {
            $rmaQty = $alreadyRequestedItem[$orderItem->getItemId()]['qty'];
        }

        $returnItem['returnable_qty'] = $qtyShipped - $qtyCanceled - $rmaQty;
        return $returnItem;
    }
}
