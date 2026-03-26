<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Seoulwebdesign\Kakaopay\Gateway\Response\Handler\Refund;

use Magento\Payment\Gateway\Helper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class RefundHandler implements HandlerInterface
{

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = Helper\SubjectReader::readPayment($handlingSubject);

        if ($paymentDO->getPayment() instanceof Payment) {
            /** @var Payment $orderPayment */
            $orderPayment = $paymentDO->getPayment();

            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction());
            $closed = $this->shouldCloseParentTransaction($orderPayment);
            $orderPayment->setShouldCloseParentTransaction($closed);
        }
    }


    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return false;
    }
    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment)
    {
        return !(bool)$orderPayment->getCreditmemo()->getInvoice()->canRefund();
    }
}
