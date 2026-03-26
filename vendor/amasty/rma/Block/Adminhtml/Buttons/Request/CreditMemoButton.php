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
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class CreditMemoButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getButtonData()
    {
        $data = [];
        $request = $this->requestRepository->getById($this->getRequestId());
        $order = $this->getOrderById($request->getOrderId());

        if ($this->authorization->isAllowed('Magento_Sales::creditmemo') && $order->canCreditmemo()) {
            $message = __(
                'This will create an offline refund. ' .
                'To create an online refund, open an invoice and create credit memo for it. Do you want to continue?'
            );
            $onClick = sprintf("location.href = '%s'", $this->getCreditmemoUrl($order->getEntityId()));

            if ($order->getPayment()->getMethodInstance()->isGateway()) {
                $onClick = "confirmSetLocation('{$message}', '{$this->getCreditmemoUrl($order->getEntityId())}')";
            }

            $data = [
                'label' => __('Credit Memo'),
                'class' => 'credit-memo',
                'on_click' => $onClick,
                'sort_order' => 20
            ];
        }

        return $data;
    }

    /**
     * Credit Memos URL getter
     *
     * @param int $orderId
     *
     * @return string
     */
    public function getCreditmemoUrl($orderId)
    {
        return $this->getUrl(
            'sales/order_creditmemo/start',
            ['order_id' => $orderId, 'request_id' => $this->getRequestId()]
        );
    }
}
