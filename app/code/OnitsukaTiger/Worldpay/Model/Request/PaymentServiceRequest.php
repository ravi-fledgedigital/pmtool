<?php
namespace OnitsukaTiger\Worldpay\Model\Request;

use Exception;
use Sapient\Worldpay\Model\SavedToken;

class PaymentServiceRequest extends \Sapient\Worldpay\Model\Request\PaymentServiceRequest
{

    /**
     * Send redirect order XML to Worldpay server
     *
     * @param array $redirectOrderParams
     * @return mixed
     */
    public function redirectOrder($redirectOrderParams)
    {
        $loggerMsg = '########## Submitting redirect order request. OrderCode: ';
        $this->_wplogger->info($loggerMsg . $redirectOrderParams['orderCode'] . ' ##########');

        $requestConfiguration = [
            'threeDSecureConfig' => $redirectOrderParams['threeDSecureConfig'],
            'tokenRequestConfig' => $redirectOrderParams['tokenRequestConfig'],
            'shopperId' => $redirectOrderParams['shopperId']
        ];
        $this->xmlredirectorder = new \OnitsukaTiger\Worldpay\Model\XmlBuilder\RedirectOrder($requestConfiguration);
        if (empty($redirectOrderParams['thirdPartyData']) && empty($redirectOrderParams['shippingfee'])) {
            $redirectOrderParams['thirdPartyData']='';
            $redirectOrderParams['shippingfee']='';
        }
        if (empty($redirectOrderParams['statementNarrative'])) {
            $redirectOrderParams['statementNarrative']='';
        }
        if (empty($directOrderParams['orderLineItems'])) {
            $directOrderParams['orderLineItems'] = '';
        }
        if (empty($redirectOrderParams['saveCardEnabled'])) {
            $redirectOrderParams['saveCardEnabled']='';
        }
        if (empty($redirectOrderParams['storedCredentialsEnabled'])) {
            $redirectOrderParams['storedCredentialsEnabled']='';
        }
        $captureDelay = $this->worldpayhelper->getCaptureDelayValues();

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/WorldpayPaymentLog.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Payment Log Start============================');
        $logger->info(print_r($redirectOrderParams, true));
        $orderContent = (isset($redirectOrderParams['orderContent'])) ? $redirectOrderParams['orderContent'] :'';

        $redirectSimpleXml = $this->xmlredirectorder->build(
            $redirectOrderParams['merchantCode'],
            $redirectOrderParams['orderCode'],
            $redirectOrderParams['orderDescription'],
            $redirectOrderParams['currencyCode'],
            $redirectOrderParams['amount'],
            $orderContent,
            $redirectOrderParams['paymentType'],
            $redirectOrderParams['shopperEmail'],
            $redirectOrderParams['statementNarrative'],
            $redirectOrderParams['acceptHeader'],
            $redirectOrderParams['userAgentHeader'],
            $redirectOrderParams['shippingAddress'],
            $redirectOrderParams['billingAddress'],
            $redirectOrderParams['paymentPagesEnabled'],
            $redirectOrderParams['installationId'],
            $redirectOrderParams['hideAddress'],
            $redirectOrderParams['paymentDetails'],
            $redirectOrderParams['thirdPartyData'],
            $redirectOrderParams['shippingfee'],
            $redirectOrderParams['exponent'],
            $redirectOrderParams['cusDetails'],
            $redirectOrderParams['orderLineItems'],
            $captureDelay,
            $redirectOrderParams['saveCardEnabled'],
            $redirectOrderParams['storedCredentialsEnabled']
        );
        return $this->_sendRequest(
            dom_import_simplexml($redirectSimpleXml)->ownerDocument,
            $this->worldpayhelper->getXmlUsername($redirectOrderParams['paymentType']),
            $this->worldpayhelper->getXmlPassword($redirectOrderParams['paymentType'])
        );
    }
}