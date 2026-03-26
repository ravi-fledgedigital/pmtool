<?php

namespace OnitsukaTiger\Razer\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\ObjectManager;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    protected $resultPageFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Magento\Quote\Api\Data\AddressInterface
     */
    private $address;

    /**
     * @var \Magento\Quote\Api\Data\AddressExtensionInterface
     */
    private $addressExtension;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var \OnitsukaTiger\Logger\Razer\Logger
     */
    private $logger;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote = null;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;


    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param OrderSender $orderSender
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        OrderSender $orderSender,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        \Magento\Quote\Api\Data\AddressInterface $address,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \OnitsukaTiger\Logger\Razer\Logger $logger
    )
    {
        parent::__construct($context);
        $this->resultPageFactory  = $resultPageFactory;
        $this->invoiceSender      = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->checkoutSession    = $checkoutSession;
        $this->orderSender        = $orderSender;
        $this->quoteRepository    = $quoteRepository;
        $this->orderRepository    = $orderRepository;
        $this->address            = $address;
        $this->addressExtension   = $address->getExtensionAttributes();
        $this->quoteManagement    = $quoteManagement;
        $this->logger             = $logger;
    }

    public function execute()
    {

        if( isset($_POST['payment_options']) && $_POST['payment_options'] != "" ) {
            // Attempt to store the cart into magento system
            // This function should be execute during MOLPay selection page AFTER the address selection
            // Begin calling Magento API

            $om =   \Magento\Framework\App\ObjectManager::getInstance();

            ### At first time, create quote and order
            $cartData = $om->create('\Magento\Checkout\Model\Cart')->getQuote();
            $quote = $this->checkoutSession->getQuote();
            // set billing address if difference shipping
            if ($address = (array)json_decode($_POST['billingAddress'])) {
                $address = $this->setBillingAddress($address);
                $customerId = $quote->getBillingAddress()
                    ->getCustomerId();
                if (!$address->getCustomerId() && $customerId) {
                    //It's necessary to verify the price rules with the customer data
                    $address->setCustomerId($customerId);
                }
                $quote->removeAddress($quote->getBillingAddress()->getId());
                $quote->setBillingAddress($address);
                $quote->setDataChanges(true);
                $quote->save();
            }else{
                $addressExtension = (array)json_decode($_POST['addressExtension']);
                if(array_key_exists('extension_attributes',$addressExtension)){
                    $billingAddress = $quote->getBillingAddress();
                    $billingAddressExtension = $this->addressExtension;
                    $extensionAttributes = (array)$addressExtension['extension_attributes'];
                    foreach ($extensionAttributes as $key => $value) {
                        $billingAddressExtension->setData($key,$value);
                    }
                    $billingAddress->setExtensionAttributes($billingAddressExtension);
                    $quote->setBillingAddress($billingAddress);
                    $quote->setDataChanges(true);
                    $quote->save();
                }
            }

            $quote->collectTotals();
            $quote->getPayment()->setMethod('molpay_seamless'); // Todo: Will Appear MOLPay Seamless

            $customerSess = $om->create('\Magento\Customer\Model\Session');
            $checkoutHelperData = $om->create('\Magento\Checkout\Helper\Data');

            //Get customer email
            if( $_POST['current_email'] == ''){ //if the case is empty
                $quote_extra = $this->quoteRepository->getActive($cartData->getId());
                $_POST['current_email'] = $quote_extra->getBillingAddress()->getEmail();
            }

            $customerType = '';
            if ($customerSess->isLoggedIn()) {
                $customerType = \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
            }
            if (!$quote->getCheckoutMethod()) {
                if ($checkoutHelperData->isAllowedGuestCheckout($quote)) {
                    $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
                } else {
                    $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
                }

                $customerType = $quote->getCheckoutMethod();
            }

            if ( $customerType == \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {

                $quote->setCustomerId(null)
                    ->setCustomerEmail($_POST['current_email'])
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);

                if ($quote->getCustomerFirstname() === null && $quote->getCustomerLastname() === null) {
                    $quote->setCustomerFirstname($quote->getBillingAddress()->getFirstname());
                    $quote->setCustomerLastname($quote->getBillingAddress()->getLastname());
                    if ($quote->getBillingAddress()->getMiddlename() === null) {
                        $quote->setCustomerMiddlename($quote->getBillingAddress()->getMiddlename());
                    }
                }
            }


            if( $quote ){
                $order = $this->quoteManagement->submit($this->getQuote());
                if( $order ){
                    $orderArr = [];
                    $orderArr = [
                        'oid' => $order->getId(),
                        "flname" => $order->getCustomerFirstName()." ".$order->getCustomerLastName(),
                        'lastorderid' => $order->getIncrementId() ];

                    $order_step2 = $om->create('\Magento\Sales\Model\Order')
                        ->load($order->getId());

                    $order_step2->setState("pending_payment")->setStatus("pending_payment");

                    $order_step2->save();

                }

            }

            ### Begin to save quote and order in session
            $checkoutSession = $om->create('\Magento\Checkout\Model\Session');

            ### initial order created, save their data in session
            if( $order ){
                $checkoutSession->setLastQuoteId($cartData->getId())->setLastSuccessQuoteId($cartData->getId());
                $checkoutSession->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus('pending');
            }

            ### When 2nd attempt to make payment but above order create is error then use the session
            if( !$order ){
                $sess_quotedata = $checkoutSession->getData();

                if( isset($sess_quotedata['last_real_order_id']) && $sess_quotedata['last_real_order_id'] != null){

                    $lastOId = $sess_quotedata['last_real_order_id'];

                    $order = $om->create('\Magento\Sales\Api\Data\OrderInterface');
                    $order->loadByIncrementId($lastOId);
                    $orderArr = [];
                    $orderArr = [
                        'orderid'       => $lastOId,
                        'customer_name' => $order->getBillingAddress()->getFirstname()." ".$order->getBillingAddress()->getLastname(),
                        'customer_email'=> $order->getCustomerEmail(),
                        'customer_tel'  => $order->getBillingAddress()->getTelephone(),
                        'amount'        => $order->getGrandTotal(),
                        'currency'      => $order->getOrderCurrencyCode()

                    ];
                }

            }

            //Get customer country id
            if( $quote->getBillingAddress()->getCountryId() === null ){
                $customer_countryid = ''; //leave empty for Collect at Store
            }
            else{
                $customer_countryid = $quote->getBillingAddress()->getCountryId();
            }

            $merchantid = $this->_objectManager->create('OnitsukaTiger\Razer\Helper\Data')->getMerchantID();
            $vkey = $this->_objectManager->create('OnitsukaTiger\Razer\Helper\Data')->getVerifyKey();

            $base_url = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();

            ### Make sure amount is always same format
            $order_amount = number_format(floatval($order->getGrandTotal()),2,'.','');

            ### End calling Magento API and set parameter for seamless button
            $params = array(
                'status'          => true,  // Set True to proceed with MOLPay
                'mpsmerchantid'   => $merchantid,
                'mpschannel'      => $_POST['payment_options'],
                'mpsamount'       => $order_amount,
                'mpsorderid'      => $order->getIncrementId(),
                'mpsbill_name'    => $order->getBillingAddress()->getFirstname()." ".$order->getBillingAddress()->getLastname(),
                'mpsbill_email'   => $order->getCustomerEmail(),
                'mpsbill_mobile'  => $order->getBillingAddress()->getTelephone(),    // To Do - Change to customer mobile number
                'mpsbill_desc'    => "Payment for Order #".$order->getIncrementId(),
                'mpscountry'      => $customer_countryid, //tested and passed when empty value
                'mpsvcode'        => md5($order_amount.$merchantid.$order->getIncrementId().$vkey),
                'mpscurrency'     => $order->getOrderCurrencyCode(),
                'mpslangcode'     => "en",
                'mpsreturnurl'    => $base_url.'seamless/',
                'mpstimer'        => '8',
                'mpstimerbox'     => '#counter',
                'mpscancelurl'    => $base_url.'seamless/'
            );

            $this->getResponse()->setBody(json_encode($params));

        }
        else if( isset($_POST[ 'mpsorderid' ]) && $_POST != "" ) {
            // Get the return from MOLPay ; case using timer on payment page
            // Case: usually used on credit card payment form (pop up window) and no transaction Id created if buyer not click 'Pay Now'

            $order_id = $_POST['mpsorderid'];
            $om =   \Magento\Framework\App\ObjectManager::getInstance();

            $order = $om->create('Magento\Sales\Api\Data\OrderInterface');
            $order->loadByIncrementId($order_id);

            $commentMsg = 'Fail to complete payment. ';
            $this->messageManager->addError($commentMsg); //front-end display

            //$order->registerCancellation($commentMsg)->save(); //back-end cancel process
            $order->cancel();
            $order->setStatus('canceled',true);
            $order->save();

            $this->checkoutSession->restoreQuote(); //get back the quote

            $url_checkoutredirection = "checkout/cart";

            $this->_redirect($url_checkoutredirection);
        }
        else if( isset($_POST['status'] ) ) //response from MOLPay
        {

            //Get Channel List
            $channel_list  = $this->_objectManager->create('OnitsukaTiger\Razer\Model\Source\Channel')->toArray();

            $status = $_POST['status'];
            $order_id = $_POST['orderid'];
            $skey = $_POST['skey'];
            $channel = strtolower($_POST['channel']);

            if(isset($_POST['nbcb']))
            {
                $nbcb = $_POST['nbcb'];
            }
            else
            {
                $nbcb = 0;
            }

            $nbcb_type = 'Return';
            $nbcb_code = 'R';
            if ($nbcb == 1) {
                $nbcb_type = 'Callback';
                $nbcb_code = 'C';
            } elseif ($nbcb == 2) {
                $nbcb_type = 'Notification';
                $nbcb_code = 'N';
                sleep(25); // To prevent Database Deadlock
            }

            $gate_response = $_POST;

            $om = \Magento\Framework\App\ObjectManager::getInstance();

            $order = $om->create('Magento\Sales\Api\Data\OrderInterface');
            $order->loadByIncrementId($order_id);

            $vkey = $this->_objectManager->create('OnitsukaTiger\Razer\Helper\Data')->getSecretKey();

            $key0 = md5($_POST['tranID'].$order_id.$status.$_POST['domain'].$_POST['amount'].$_POST['currency']);
            $key1 = md5($_POST['paydate'].$_POST['domain'].$key0.$_POST['appcode'].$vkey);

            //log MOLPay Response
            $mp_logger = $this->logger;

            //log MOLPay Response
            $mp_logger->info( "LOG".$order_id." Step1 RESP:", $gate_response );

            if($skey == $key1) {

                if($status == '00') {   // Success Payment
                    $quoteId = $order->getQuoteId();
                    if ($order->getId() && $order->getState() != 'processing') {

                        $mp_logger->info( "LOG".$order_id." Step2 00 $nbcb_code: Expecting order state change ".$order->getState()." to processing" );

                        //change the way for order status update to processing
                        $order_upd = $om->create('Magento\Sales\Api\Data\OrderInterface');
                        $order_upd->loadByIncrementId($order_id);
                        //$order_upd = $this->orderRepository->get($order_id);
                        if ($order->getIsPreOrder()) {
                            $mp_logger->info( "LOG".$order_id." Step2 00 $nbcb_code: Product is preorder ".$order->getIsPreOrder());
                            // Update preorder order state and status.
                            $order_upd->setState("processing")->setStatus("pre_order_processing");
                        }else{
                            // Update order state and status.
                            $order_upd->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                            $order_upd->setStatus('processing', true);
                        }

                        $this->orderRepository->save($order_upd);

                        $order->addStatusHistoryComment(__('Response from RMS - '. $nbcb_type . ' (Transaction Channel : '.$channel.' | Status : CAPTURED).<br/>You have confirmed the order to the customer via email.' ))
                            ->setIsCustomerNotified(true);

                        $payment = $order->getPayment();
                        $mp_amount = $_POST['amount'];
                        $mp_txnid = $_POST['tranID'];

                        //acknowledge status payment (00 = captured) received and status order change to processing
                        $this->_ack($_POST);

                        //Create New Invoice and Transaction functions
                        $this->update_invoice_transaction( $order, $payment, $mp_txnid );
                    }

                } else if($status == '22') {    // Pending Payment

                    $order->setState('pending_payment',true);
                    $order->setStatus('pending_payment',true);

                    $order->addStatusHistoryComment(__('Response from RMS - '. $nbcb_type . ' (Transaction Status : PENDING)'))
                        ->setIsCustomerNotified(false);

                }
                else { // Fail Payment
                    if ($order->getId() && $order->getState() != 'canceled') {

                        $mp_logger->info( "LOG".$order_id." Step2 11 $nbcb_code: Expecting order state change to canceled." );

                        if( $nbcb == "1" || $nbcb == "2" ) //Callback; nbcb=1 : possible differ update from return URL status / Notification; nbcb=2 : confirm status from bank
                        {
                            if($order->getState() != 'canceled') {
                                $this->cancelOrder($order->loadByIncrementId($order_id) ,$nbcb_type);
                            }

                            //acknowledge status payment (11 = failed) received and status order change to canceled
                            $this->_ack($_POST);
                        }
                        else //During buyer return to merchant site; nbcb empty
                        {
                            /************************************************************************************************************************************************
                            Case: Buyer return to merchant website (MOLPay to merchant in Return URL)
                            There are 2 possibilities that Status Txn 11 during return/redirect to merchant website :
                            1- During return to merchant website, MOLPay get result from bank and directly give status of transaction ( This confirm txn status failed )
                            2- MOLPay not yet get result from bank during buyer redirect to merchant page. At this time, merchant will get txn status failed.
                            But, txn status can be changed, as MOLPay requery status of payment at Bank and return the status to merchant website through callback
                             *************************************************************************************************************************************************/

                            //Check is sandbox or production
                            $env  = $this->_objectManager->create('OnitsukaTiger\Razer\Helper\Data')->getSandboxEnvironment();

                            if($env === '1'){
                                $qtxn['StatCode']=$gate_response['status'];
                            } else {
                                //requery status at MOLPay
                                $qtxn = $this->queryStatusTransaction($gate_response);
                            }

                            if( !empty($qtxn) ){

                                if($qtxn['StatCode'] === "11") { //StatName = Failure

                                    $mp_logger->info( "LOG".$order_id." Step2 Q11 $nbcb_code: Expecting order state change to canceled" );
                                    $this->cancelOrder($order->loadByIncrementId($order_id) ,$nbcb_type);

                                    $this->checkoutSession->restoreQuote(); //get back the quote

                                    //acknowledge status payment (11 = failed) received and status order change to canceled
                                    $this->_ack($_POST);

                                    $url_checkoutredirection = 'checkout/cart';
                                }
                                elseif($qtxn['StatCode'] === "22") { //Statname = pending

                                    $mp_logger->info( "LOG".$order_id." Step2 Q22 $nbcb_type: Expecting order state remain pending_payment");

                                    // if notification comes first and update order state to pending , no need to update this part. otherwise, update the order
                                    if ( $order->getId() && $order->getState() != 'pending_payment' ) {

                                        //advisable to not change order status to canceled due to Magento business flow
                                        $order->setState('pending_payment',true);
                                        $order->setStatus('pending_payment',true);

                                        //But status from MOLPay at this time failed. need to wait latest status from MOLPay thru callback
                                        $order->addStatusHistoryComment(__('Response from RMS - '. $nbcb_type . ' (Transaction Status : FAILED). <br>Note: Possible status change. Waiting callback response'))
                                            ->setIsCustomerNotified(false);
                                    }

                                    //Redirect to merchant page
                                    //Buyer will see this page as Order Being Placed
                                    $this->messageManager->addSuccess('Order has been placed but we are waiting for payment');

                                    $quoteId = $order->getQuoteId();
                                    $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
                                    $this->checkoutSession->setLastOrderId($order->getId());

                                    $url_checkoutredirection = 'checkout/onepage/success';

                                }
                                elseif($qtxn['StatCode'] === "00") { //Statname = Success

                                    $mp_logger->info( "LOG".$order_id." Step2 Q00 $nbcb_type: Expecting order state change to processing".$order->getState());

                                    if ($order->getId() && $order->getState() != 'processing') {
                                        //$order_upd = $this->orderRepository->get($order_id);
                                        if ($order->getIsPreOrder()) {
                                            $mp_logger->info( "LOG".$order_id." Step2 Q00 $nbcb_code: Order Product is preorder ".$order->getIsPreOrder());
                                            // Update preorder order state and status.
                                            $order->setState("processing")->setStatus("pre_order_processing");
                                        }else{
                                            // Update order state and status.
                                            $order->setState('processing',true);
                                            $order->setStatus('processing',true);
                                        }

                                        $order->addStatusHistoryComment(__('Response from RMS - '. $nbcb_type . ' (Transaction Channel : '.$channel.' | Status : CAPTURED).<br/>You have confirmed the order to the customer via email.' ))
                                            ->setIsCustomerNotified(true);

                                        $payment = $order->getPayment();
                                        $mp_amount = $_POST['amount'];
                                        $mp_txnid = $_POST['tranID'];

                                        //acknowledge status payment (00 = captured) received and status order change to processing
                                        $this->_ack($_POST);

                                        //Create New Invoice and Transaction functions
                                        $this->update_invoice_transaction( $order, $payment, $mp_txnid );
                                    }

                                    $this->messageManager->addSuccess('Order has been successfully placed!');

                                    $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
                                    $this->checkoutSession->setLastOrderId($order->getId());

                                    //page redirect
                                    $url_checkoutredirection = 'checkout/onepage/success';
                                }

                            }
                        }
                    }

                    //Case: Order status already set canceled during notification. Must return buyer to merchant page
                    if($order->getStatus() == 'canceled'){
                        $this->messageManager->addError('Fail to complete payment.');

                        $this->checkoutSession->restoreQuote();

                        $url_checkoutredirection = 'checkout/cart';
                    }
                }

                if ($nbcb  == 1) {

                    echo 'CBTOKEN:MPSTATOK';

                } else if ($nbcb == 0) {

                    if ($status=='00' || $status=='22') {

                        //page redirect in frontend
                        $url_checkoutredirection = 'checkout/onepage/success';

                        $this->messageManager->addSuccess('Order has been successfully placed!');
                    }

                }
            } else {

                $mp_logger->info( "LOG".$order_id." Step2 FRAUD $nbcb_type: Unmatch skey be cause of wrong calculated");

                $this->messageManager->addError('Key is not valid.');
                $order->setState('fraud',true);
                $order->setStatus('fraud',true);

                $history_msg = '';
                $history_msg = 'Payment Error: Signature key not match';

                $order->addStatusHistoryComment(__( $history_msg ))
                    ->setIsCustomerNotified(false);

                $this->checkoutSession->restoreQuote();

                $url_checkoutredirection = 'checkout/cart';
            }

            $order->save(); //save the updated order info based on condition above

            if (isset($url_checkoutredirection) && $url_checkoutredirection!='') {
                $quoteId = $order->getQuoteId();
                $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
                $this->checkoutSession->setLastOrderId($order->getId());

                $this->_redirect($url_checkoutredirection);
            }
        }

        else if( empty($_POST) ){
            $this->_redirect('/');
        }
    }

    /**
     * @param $order
     * @param $nbcb_type
     */
    public function cancelOrder($order, $nbcb_type)
    {
        if($order->getStatus() != 'canceled') {
            $order->cancel();
            $order->setStatus('canceled', true);
            $order->addStatusHistoryComment(__('Response from RMS - '. $nbcb_type . ' (Transaction Status : FAILED)'))
                ->setIsCustomerNotified(false);

            $order->save();
        }
    }

    /**
     * @param $address
     */
    public function setBillingAddress($address){
        $this->address->setRegion(array_key_exists('region',$address) ? $address['region'] : '');
        $this->address->setRegionId(array_key_exists('regionId',$address) ?$address['regionId']: '');
        $this->address->setRegionCode(array_key_exists('regionCode',$address) ?$address['regionCode']: '');
        $this->address->setCountryId(array_key_exists('countryId',$address) ?$address['countryId']: '');
        $this->address->setStreet(array_key_exists('street',$address) ?$address['street']: '');
        $this->address->setCompany(array_key_exists('company',$address) ?$address['company']: '');
        $this->address->setTelephone(array_key_exists('telephone',$address) ?$address['telephone']: '');
        $this->address->setFax(array_key_exists('fax',$address) ?$address['fax']: '');
        $this->address->setPostcode(array_key_exists('postcode',$address) ?$address['postcode']: '');
        $this->address->setCity(array_key_exists('city',$address) ?$address['city']: '');
        $this->address->setFirstname(array_key_exists('firstname',$address) ?$address['firstname']: '');
        $this->address->setLastname(array_key_exists('lastname',$address) ?$address['lastname']: '');
        $this->address->setMiddlename(array_key_exists('middlename',$address) ?$address['middlename']: '');
        $this->address->setPrefix(array_key_exists('prefix',$address) ?$address['prefix']: '');
        $this->address->setSuffix(array_key_exists('suffix',$address) ?$address['suffix']: '');
        $this->address->setVatId(array_key_exists('vatId',$address) ?$address['vatId']: '');
        $this->address->setCustomerId(array_key_exists('customerId',$address) ?$address['customerId']: '');
        $this->address->setEmail(array_key_exists('email',$address) ?$address['email']: '');
        $this->address->setSaveInAddressBook(array_key_exists('saveInAddressBook',$address) ?$address['saveInAddressBook']: '');
        $this->setAddressExtension($address);
        return $this->address;
    }

    /**
     * Quote object getter
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        if ($this->_quote === null) {
            return $this->checkoutSession->getQuote();
        }
        return $this->_quote;
    }
    /**
     * @param $address
     */
    public function setAddressExtension($address){
        if(array_key_exists('extension_attributes',$address)){
            $extensionAttributes = (array)$address['extension_attributes'];
            foreach ($extensionAttributes as $key => $value) {
                $this->addressExtension->setData($key,$value);
            }
        }
        $this->address->setExtensionAttributes($this->addressExtension);
    }
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException {

        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool {

        return true;
    }

    public function _ack($P) {

        $P['treq'] = 1;
        foreach ($P as $k => $v) {
            $postData[]= $k."=".$v;
        }
        $postdata   = implode("&",$postData);
        $url        = "https://www.onlinepayment.com.my/MOLPay/API/chkstat/returnipn.php";
        $ch         = curl_init();
        curl_setopt($ch, CURLOPT_POST           , 1     );
        curl_setopt($ch, CURLOPT_POSTFIELDS     , $postdata );
        curl_setopt($ch, CURLOPT_URL            , $url );
        curl_setopt($ch, CURLOPT_HEADER         , 1  );
        curl_setopt($ch, CURLINFO_HEADER_OUT    , TRUE   );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1  );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , FALSE);
        $result = curl_exec( $ch );
        curl_close( $ch );

        if ( $result ) {
            //log MOLPay ACK
            $mp_logger = $this->logger;
            $mp_logger->info( "LOG".$P['orderid']." Step3 ACK2MOLPay: status payment (".$P['status'].") received" );
        }

        return;
    }

    public function update_invoice_transaction($order, $payment, $e){ //$a:$order_id, $b:$order, $c:$payment, $d:$mp_amount, $e:$mp_txnid
        if($order->canInvoice()) {
            $payment
                ->setTransactionId($e)
                ->setShouldCloseParentTransaction(1)
                ->setIsTransactionClosed(0);
            sleep(2);
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register();

            $transaction = $this->transactionFactory->create();

            $transaction->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

        }

        try {
            if($order->getCanSendNewEmailFlag() == false ){
                $order->setCanSendNewEmailFlag(true);
            }
            if ($order->getIsPreOrder()) {
                // Update preorder order state and status.
                $order->setState("processing")->setStatus("pre_order_processing");
            }else{
                // Update order state and status.
                $order->setState('processing',true);
                $order->setStatus('processing',true);
            }
            $this->orderSender->send($order);
            $quote = $this->quoteRepository->get($order->getQuoteId())->setIsActive(false);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/preorder_RazorIndex.log");
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info("Error log catch".$e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot send the new order email.'));
        }

    }

    protected function queryStatusTransaction($P){
        $result  = '';
        $res     = array();

        //get merchant verify key
        $mpverifykey  = $this->_objectManager->create('OnitsukaTiger\Razer\Helper\Data')->getVerifyKey();

        $rawkey  = $P['tranID'].$P['domain'].$mpverifykey.$P['amount'];
        $skey    = md5($rawkey);

        $dataq   = array(
            "amount" => $P['amount'],
            "txID"   => $P['tranID'],
            "domain" => $P['domain'],
            "skey"   => $skey,
            "type"   => "0"
        );
        $postdata = http_build_query($dataq);

        try{
            $url        = "https://api.molpay.com/MOLPay/q_by_tid.php";
            $ch         = curl_init();
            curl_setopt($ch, CURLOPT_POST           , 1     );
            curl_setopt($ch, CURLOPT_POSTFIELDS     , $postdata );
            curl_setopt($ch, CURLOPT_URL            , $url );
            curl_setopt($ch, CURLOPT_HEADER         , 1  );
            curl_setopt($ch, CURLINFO_HEADER_OUT    , TRUE   );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1  );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , FALSE);
            $result     = curl_exec( $ch );
            curl_close( $ch );

            $dataRes    = trim(strstr($result,"StatCode"));
            $dataRes    = explode("\n",$dataRes);

            $res = array();
            foreach($dataRes as $dt){
                list($k,$v) = explode(': ',$dt);
                $res[$k]    = $v;
            }

        }catch (Exception $e) {
            $res ='';
        }

        return $res;
    }

}
