<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Buttons\Request;

use Amasty\Rma\Block\Adminhtml\Buttons\GenericButton;
use Amasty\Rma\Model\Request\Repository;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Api\OrderRepositoryInterface;

class ReorderButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @var Reorder
     */
    private $reorderHelper;

    public function __construct(
        Context $context,
        Reorder $reorderHelper,
        OrderRepositoryInterface $orderRepository,
        Repository $requestRepository
    ) {
        parent::__construct($context, $orderRepository, $requestRepository);
        $this->reorderHelper = $reorderHelper;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $request = $this->requestRepository->getById($this->getRequestId());
        $order = $this->getOrderById($request->getOrderId());

        if ($this->authorization->isAllowed('Magento_Sales::reorder')
            && $this->reorderHelper->isAllowed($order->getStore())
            && $order->canReorderIgnoreSalable()
        ) {
            $onClick = sprintf("location.href = '%s'", $this->getReorderUrl($order->getEntityId()));
            $data = [
                'label' => __('New Order'),
                'class' => 'reorder',
                'on_click' => $onClick,
                'sort_order' => 20
            ];
        }

        return $data;
    }

    /**
     * Reorder URL getter
     *
     * @param int $orderId
     *
     * @return string
     */
    public function getReorderUrl($orderId)
    {
        return $this->getUrl(
            'sales/order_create/reorder',
            [
                'order_id' => $orderId,
                'rma_request_id' => $this->getRequestId()
            ]
        );
    }
}
