<?php

namespace Seoulwebdesign\Kakaopay\Model\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Seoulwebdesign\Kakaopay\Helper\ConfigHelper;
use Seoulwebdesign\Kakaopay\Helper\Constant;
use Seoulwebdesign\Base\Helper\MobileDetect;

class Kakaopay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'kakaopay';
    protected $_code = self::CODE;

    protected $_isGateway = true;
    protected $_isOffline = false;
    protected $_canOrder = true;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_isInitializeNeeded = true;
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = false;

    protected $checkoutSession;
    protected $myLogger;
    protected $configHelper;
    protected $storeManager;
    protected $_messageManager;
    /**
     * @var MobileDetect
     */
    protected $mobileDetect;

    /**
     * Kakaopay constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param \Seoulwebdesign\Kakaopay\Logger\Logger $customLogger
     * @param ConfigHelper $configHelper
     * @param ManagerInterface $messageManager
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param MobileDetect $mobileDetect
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        \Seoulwebdesign\Kakaopay\Logger\Logger $customLogger,
        ConfigHelper $configHelper,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        Logger $logger,
        MobileDetect $mobileDetect,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data);
        $this->_messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->myLogger = $customLogger;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->mobileDetect = $mobileDetect;
    }

    /**
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->configHelper->paymentAction();
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        return true;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return Kakaopay|void
     * @throws \Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            /**
             * @var InfoInterface|\Magento\Sales\Model\Order\Payment $payment
             */
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $amount = $order->getBaseGrandTotal();
            $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $stateObject->setStatus('pending_payment');
            $stateObject->setIsNotified(false);
            $this->order($payment, $amount);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function order(InfoInterface $payment, $amount)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $approvalUrl = $this->storeManager->getStore()->getBaseUrl() . Constant::KAKAOPAY_APPROVAL_URL;
        $cancelUrl = $this->storeManager->getStore()->getBaseUrl() . Constant::KAKAOPAY_CANCEL_URL;
        $failUrl = $this->storeManager->getStore()->getBaseUrl() . Constant::KAKAOPAY_FAIL_URL;

        $avaiCardsJson = $this->configHelper->getAvailableCards();
        $avaiCards = json_decode($avaiCardsJson);

        $data =
            [
                'cid' => strval($this->configHelper->getCID()),
                'partner_order_id' => $order->getIncrementId(),
                'partner_user_id' => $order->getCustomerEmail(),
                'item_name' => $this->getProductName($order),
                'item_code' => $order->getIncrementId(),
                'quantity' => intval($order->getBaseTotalQtyOrdered()),
                'total_amount' => intval($order->getBaseGrandTotal()),
                'tax_free_amount' => 0,
                'vat_amount' => 0,
                'approval_url' => $approvalUrl,
                'cancel_url' => $cancelUrl,
                'fail_url' => $failUrl,
                'available_cards' => $this->configHelper->getAvailableCards(),
                'install_month' => '',
                'custom_json' => '',
                'user_phone_number' => ''
            ];
        if ($avaiCards) {
            $data['available_cards'] = $this->configHelper->getAvailableCards();
        }
        if ($this->configHelper->getPaymentMethodType()) {
            $data['payment_method_type'] = $this->configHelper->getPaymentMethodType();
        }
        $response = $this->configHelper->sendCurl(Constant::KAKAOPAY_PAYMENT_READY, $data, 'POST');
        $this->myLogger("order//" . print_r($response, true));

        if ($response && isset($response['tid'])
            && (isset($response['next_redirect_pc_url']) || isset($response['next_redirect_mobile_url']))
        ) {
            if ($this->mobileDetect->isMobile()) {
                $responseUrl = $response['next_redirect_mobile_url'];
            } else {
                $responseUrl = $response['next_redirect_pc_url'];
            }

            $payment->setAdditionalInformation(Constant::KAKAOPAY_RESPONSE_URL, $responseUrl);
            $payment->setAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TID, $response['tid']);
            $order->addStatusHistoryComment(__('Creating Kakaopay Url'), "pending_payment");
            $order->addStatusHistoryComment(__('Transaction ID: ') . $response['tid'], "pending_payment");
            $order->save();
        } else {
            $this->myLogger("Unable to create Payment Url");
            throw new \Exception(__("Unable to create Payment Url"));
        }
    }

    /**
     * create product name string
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getProductName($order)
    {
        $name = [];
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            /** @var $item \Magento\Sales\Model\Order\Item */
            $productName = $this->convertEncodeToUtf8($item->getProduct()->getName());
            $name[] = $productName . "-" . $item->getQtyOrdered();
        }
        $fullName =  implode('|', $name);
        return mb_strcut($fullName, 0, 99);
    }

    /**
     * @param $params
     * @return array|false|string|string[]|null
     */
    public function convertEncodeToUtf8($params)
    {
        if (!$params) {
            return null;
        }
        if (is_array($params)) {
            foreach ($params as $key => $val) {
                $currentEncode = mb_detect_encoding($val);
                $currentEncode = $currentEncode ? $currentEncode : "EUC-KR";
                $params[$key] = mb_convert_encoding($val, "UTF-8", $currentEncode);
            }
            return $params;
        } else {
            $currentEncode = mb_detect_encoding($params);
            $currentEncode = $currentEncode ? $currentEncode : "EUC-KR";
            return mb_convert_encoding($params, "UTF-8", $currentEncode);
        }
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return Kakaopay|void
     * @throws \Exception
     */
    public function capture(InfoInterface $payment, $amount)
    {
        try {
            /**
             * @var \Magento\Sales\Model\Order $order
             */
            $order = $payment->getOrder();
            $tid = $payment->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TID);
            $token = $payment->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TOKEN);
            $poi = $payment->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_POI);
            $pui = $payment->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_PUI);
            $data =
                [
                    'cid' => strval($this->configHelper->getCID()),
                    'tid' => $tid,
                    'partner_order_id' => $poi,
                    'partner_user_id' => $pui,
                    'pg_token' => $token,
                ];
            $this->myLogger->debug(print_r($data, true));
            $response = $this->configHelper->sendCurl(Constant::KAKAOPAY_PAYMENT_APPROVE, $data, 'POST');
            $this->myLogger->debug("response//" . print_r($response, true));

            if (isset($response['tid']) && $response['tid'] && isset($response['partner_order_id']) && $response['partner_order_id']) {
                // if the approve request is successful, then some content will be returned, such as tid, partner_order_id.
                $this->myLogger("capture-success//" . print_r($response, true));
                $payment->setAdditionalInformation(Constant::KAKAOPAY_RESPONSE_PAYMENT_DETAIL, $response);
            } else {
                // if the payment fails, then stop processing payment
                $this->myLogger("capture-fail//" . print_r($response, true));
                $errorMessage = isset($response['extras']) && isset($response['extras']['method_result_message']) ? $response['extras']['method_result_message'] : 'Error';
                throw new \Exception($errorMessage);
            }
        } catch (\Exception $exception) {
            $this->myLogger("capture//" . $exception->getMessage());
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $mess
     */
    public function myLogger($mess)
    {
        if ($this->configHelper->getCanDebug()) {
            $this->myLogger->debug($mess);
        }
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return Kakaopay|void
     * @throws \Exception
     */
    public function refund(InfoInterface $payment, $amount)
    {
        try {
            $tid = $payment->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_TID);
            $data =
                [
                    'cid' => strval($this->configHelper->getCID()),
                    'tid' => $tid,
                    'cancel_amount' => $amount,
                    'cancel_tax_free_amount' => 0,
                    'cancel_vat_amount' => 0
                ];
            $this->myLogger->debug('refund-' . print_r($data, true));
            $response = $this->configHelper->sendCurl(Constant::KAKAOPAY_PAYMENT_REFUND, $data, 'POST');
            $this->myLogger("refund//" . print_r($response, true));
            if ($response && isset($response['status']) && isset($response['aid'])) {
                if ($response['status'] === 'CANCEL_PAYMENT') {
                    parent::refund($payment, $amount);
                } else {
                    $this->_messageManager->addWarningMessage("Something went wrong. Please try again");
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __("Something went wrong. Please try again")
                    );
                }
            } else {
                throw new \Exception(__("Something went wrong. Please try again"));
            }
        } catch (\Exception $exception) {
            $this->myLogger("refund//" . $exception->getMessage());
            throw new \Exception("Something went wrong. Please try again");
        }
    }
}
