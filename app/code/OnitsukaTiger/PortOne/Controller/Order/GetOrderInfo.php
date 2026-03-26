<?php
namespace OnitsukaTiger\PortOne\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use OnitsukaTiger\PortOne\Model\PortOneFactory;

class GetOrderInfo extends Action
{
    protected JsonFactory $resultJsonFactory;
    protected CheckoutSession $checkoutSession;
    protected PortOneFactory $portoneFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        PortOneFactory $portoneFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->portoneFactory = $portoneFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $order = $this->checkoutSession->getLastRealOrder();
            $paymentMethod = $order->getPayment()->getMethod();

            if (!$order || !$order->getId()) {
                return $result->setData([
                    'success' => false,
                    'error'   => 'No order found in session.'
                ]);
            }

            $portonePaymentMethod = ['portone', 'portonetransfer','portoneapplepay','portone_kakaopay','portone_npay','portonepaycopay','portonesamsungpay','portone_tosspay'];
            if(in_array($paymentMethod, $portonePaymentMethod))
            {
                $today = date('Ymd');
                $paymentId = 'payment_' . $today . '_' . $order->getIncrementId();

                $portoneModel = $this->portoneFactory->create();
                $portoneModel->setOrderEntityId($order->getId());
                $portoneModel->setPaymentId($paymentId);
                $portoneModel->save();

                return $result->setData([
                    'success'       => true,
                    'order_id'      => $order->getId(),
                    'increment_id'  => $order->getIncrementId(),
                    'paymentId'     => $paymentId
                ]);
            } else {
                return $result->setData([
                    'success' => false,
                    'error'   => 'No PortOne Order Found.'
                ]);
            }
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error'   => $e->getMessage()
            ]);
        }
    }
}
