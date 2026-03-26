<?php
namespace OnitsukaTiger\Worldpay\Controller\Notification;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

class Index extends \Sapient\Worldpay\Controller\Notification\Index
{
    private $abstractMethod;

    public function __construct(
            Context $context,
            JsonFactory $resultJsonFactory,
            \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
            \Sapient\Worldpay\Model\Payment\Service $paymentservice,
            \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
            \Sapient\Worldpay\Model\Order\Service $orderservice,
            \Sapient\Worldpay\Model\PaymentMethods\PaymentOperations $abstractMethod,
            \Sapient\Worldpay\Model\HistoryNotificationFactory $historyNotification,
            \Magento\Framework\Filesystem\Driver\file $fileDriver)
    {
        parent::__construct(
            $context,
            $resultJsonFactory,
            $wplogger,
            $paymentservice,
            $worldpaytoken,
            $orderservice,
            $abstractMethod,
            $historyNotification,
            $fileDriver);
        $this->abstractMethod = $abstractMethod;
    }


    public function execute()
    {
        $this->wplogger->info('notification index url hit');
        try {
            $xmlRequest = simplexml_load_string($this->_getRawBody());

            if ($xmlRequest instanceof \SimpleXMLElement) {
                $this->updateNotification($xmlRequest);
                $this->_createPaymentUpdate($xmlRequest);
                $this->_loadOrder();
                $this->_tryToApplyPaymentUpdate();
                $this->_updateOrderStatus();
                $this->_applyTokenUpdate($xmlRequest);
                return $this->_returnOk();
            } else {

                $this->wplogger->error('Not a valid xml');
            }
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            if ($e->getMessage() == 'invalid state transition' || $e->getMessage() == 'same state'
                || $e->getMessage() == 'Notification received for Partial Captutre') {
                return $this->_returnOk();
            } else {
                return $this->_returnFailure();
            }
        }
    }

    /**
     * @param $xmlRequest SimpleXMLElement
     */
    private function _createPaymentUpdate($xmlRequest)
    {
        $this->wplogger->info('########## Received notification ##########');
        $this->wplogger->info($this->_getRawBody());
        $this->paymentservice->getPaymentUpdateXmlForNotification($this->_getRawBody());
        $this->_paymentUpdate = $this->paymentservice
            ->createPaymentUpdateFromWorldPayXml($xmlRequest);

        $this->_logNotification();
    }

    private function _logNotification()
    {
//        $this->wplogger->info('########## Received notification ##########');
//        $this->wplogger->info($this->_getRawBody());
        $this->wplogger->info('########## Payment update of type: ' .
            get_class($this->_paymentUpdate). ' created ##########');
    }

    /**
     * Get order code
     */
    private function _loadOrder()
    {
        $orderCode = $this->_paymentUpdate->getTargetOrderCode();
        $orderIncrementId = current(explode('-', $orderCode));

        $this->_order = $this->orderservice->getByIncrementId($orderIncrementId);
    }

    private function _tryToApplyPaymentUpdate()
    {
        try {
            $this->_paymentUpdate->apply($this->_order->getPayment(), $this->_order);
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * @param $xmlRequest SimpleXMLElement
     */
    private function _applyTokenUpdate($xmlRequest)
    {
        $tokenService = $this->worldpaytoken;
        $tokenService->updateOrInsertToken(
            new \Sapient\Worldpay\Model\Token\StateXml($xmlRequest),
            $this->_order->getPayment(),
            $this->_order->getOrder()->getCustomerId()
        );
    }


    /**
     * Save Notification
     */
    private function updateNotification($xml)
    {
        $statusNode=$xml->notify->orderStatusEvent;
        $orderCode="";
        $paymentStatus="";
        if (isset($statusNode['orderCode'])) {
            list($orderCode) = explode("-", $statusNode['orderCode']);
        }
        if (isset($statusNode->payment->lastEvent)) {
            $paymentStatus=$statusNode->payment->lastEvent;
        }
        $hn = $this->historyNotification->create();
        $hn->setData('status', $paymentStatus);
        $hn->setData('order_id', trim($orderCode));
        $hn->save();
    }

    private function _updateOrderStatus()
    {
        $this->abstractMethod->updateOrderStatusForVoidSale($this->_order);
        $this->abstractMethod->updateOrderStatusForCancelOrder($this->_order);
    }
}
