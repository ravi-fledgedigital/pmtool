<?php

namespace Seoulwebdesign\Toast\Helper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Seoulwebdesign\Toast\Model\Message;
use Seoulwebdesign\Toast\Model\MessageFactory;
use Seoulwebdesign\Toast\Model\ResourceModel\Message\CollectionFactory;

class Data extends AbstractHelper
{
    /**
     * @var EncryptorInterface
     */
    private $_encryptor;
    /**
     * @var Curl
     */
    private $_curl;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var MessageFactory
     */
    protected $_messageFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    public const API_DOMAIN = 'https://api-alimtalk.cloud.toast.com';
    //const API_SEND_MESSAGE_ENDPOINT = self::API_DOMAIN . '/alimtalk/v1.0/appkeys/{appkey}/messages';
    public const API_SEND_MESSAGE_ENDPOINT = self::API_DOMAIN . '/alimtalk/v1.5/appkeys/{appkey}/messages';
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;
    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    /**
     * @var Message
     */
    protected $message;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param Curl $curl
     * @param MessageFactory $messageFactory
     * @param ResourceConnection $resourceConnection
     * @param OrderRepository $orderRepository
     * @param CollectionFactory $collectionFactory
     * @param array $attributeResolver
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        Logger $logger,
        Curl $curl,
        MessageFactory $messageFactory,
        ResourceConnection $resourceConnection,
        OrderRepository $orderRepository,
        CollectionFactory $collectionFactory,
        $attributeResolver = []
    ) {
        parent::__construct($context);
        $this->_encryptor = $encryptor;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_curl = $curl;
        $this->_messageFactory = $messageFactory;
        $this->connection = $resourceConnection->getConnection();
        $this->orderRepository = $orderRepository;
        $this->collectionFactory = $collectionFactory;
        $this->message = $this->_messageFactory->create();
        $this->attributeResolver = $attributeResolver;
    }

    /**
     * @var array|mixed
     */
    protected $attributeResolver;

    /**
     * Send Message By Order Id
     *
     * @param int $orderId
     * @param string $messageType
     */
    public function sendMessageByOrderId($orderId, $messageType)
    {
        if (!$this->getIsEnabled()) {
            return;
        }

        try {
            $order = $this->orderRepository->get($orderId);
            if ($order && $order->getEntityId()) {
                $data = [];
                $data['order']= $order;
                $shipData = $this->getOrderTracking($order);
                $data['tracking'] = $shipData['track_numbers'];
                $data['courier'] = $shipData['carrier_titles'];
                $data['courier_code'] = $shipData['carrier_codes'];
                $data['storeId'] = $order->getStoreId();
                $this->sendMessage($messageType, $data);
            }
        } catch (\Exception $ex) {
            $this->log($ex->getMessage(), true);
        }
    }

    /**
     * Send Message By Order Id And Custom Tracking
     *
     * @param int $orderId
     * @param string $customTracking
     * @param string $messageType
     */
    public function sendMessageByOrderIdAndCustomTracking($orderId, $customTracking, $messageType)
    {
        if (!$this->getIsEnabled()) {
            return;
        }
        try {
            $order = $this->orderRepository->get($orderId);
            if ($order && $order->getEntityId()) {
                $data = [];
                $data['order']= $order;
                $data['tracking'] = $customTracking;
                $data['storeId'] = $order->getStoreId();
                $this->sendMessage($messageType, $data);
            }
        } catch (\Exception $ex) {
            $this->log($ex->getMessage(), true);
        }
    }

    /**
     * Get Order Tracking
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderTracking($order)
    {
        $shipments = $order->getShipmentsCollection();
        $trackData = [];
        $carrierCode = [];
        $carrierTitle = [];
        $re['track_numbers'] = '';
        $re['carrier_codes'] = '';
        $re['carrier_titles'] = '';

        if ($shipments) {
            foreach ($shipments as $shipment) {
                $tracks = $shipment->getAllTracks();
                foreach ($tracks as $track) {
                    $data = $track->getData();
                    if (isset($data['track_number'])) {
                        $trackData[]=$data['track_number'];
                    }
                    if (isset($data['carrier_code'])) {
                        $carrierCode[]=$data['carrier_code'];
                    }
                    if (isset($data['carrier_title'])) {
                        $carrierTitle[]=$data['carrier_title'];
                    }
                }
            }
        }
        if ($trackData) {
            $re['track_numbers'] = implode(',', $trackData);
            $re['carrier_codes'] = implode(',', $carrierCode);
            $re['carrier_titles'] = implode(',', $carrierTitle);
            return $re;
        }
        return $re;
    }

    /**
     * Send Message
     *
     * @param string $messageType
     * @param array $params
     * @param bool $force
     * @return bool
     */
    public function sendMessage($messageType, $params = [], $force = false)
    {
        if (!$force) {
            if (!$this->getIsEnabled()) {
                return false;
            }
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect('*');
        $collection->addFieldToFilter('status', 1)
            ->addFieldToFilter('send_message_action', $messageType);
        $storeId = isset($params['storeId']) ? (int)$params['storeId'] : null;
        foreach ($collection as $message) {
            try {
                /* @var Message $message */
                $url = $this->getToastSendMessageUrl($storeId);
                $messageParams = $this->_prepareMessageParams($messageType, $message, $params, $storeId);
                $this->log('---SEND MESSAGE---', true);
                $this->log($url, true);
                $this->log('message-toast-id: ' . $message->getToastId(), true);
                $this->log($messageParams, true);

                $result = $this->sendCurl($url, $messageParams, 'POST', $storeId);
                $this->log($result, true);
                $this->log('---END SEND MESSAGE---', true);
                return true;
            } catch (\Exception $ex) {
                $this->log('---sendMessage FAILED---', true);
                $this->log($ex->getMessage(), true);
                $this->log('message-type: ' . $messageType, true);
                $this->log('message-id: ' . $message->getMessageId(), true);
                $this->log('message-toast-id: ' . $message->getToastId(), true);
                $this->log('---END sendMessage FAILED---', true);
                continue;
            }
        }
        return false;
    }

    /**
     * Prepare Message Params
     *
     * @param string $messageType
     * @param \Seoulwebdesign\Toast\Model\Message $message
     * @param array $messageParams
     * @param int|null $storeId
     * @return false|string
     * @throws LocalizedException
     */
    protected function _prepareMessageParams($messageType, $message, $messageParams, $storeId = null)
    {
        $params = [];
        if ($message->getJsonVar()) {
            $jsonVar = json_decode($message->getJsonVar(), true);
            foreach ($jsonVar as $key => $value) {
                if ($value != null) {
                    $value = trim($value);
                    if ($value && substr_count($key, 'var_')) {
                        if (isset($this->attributeResolver[$key])) {
                            $resolver = $this->attributeResolver[$key];
                            $result = $resolver->execute($message, $messageParams);
                            $params[$value] = $result;
                        }
                    }
                }
            }
        }
        $phone = null;
        if (isset($messageParams['phone'])) {
            $phone = $messageParams['phone'];
        } elseif (isset($messageParams['order'])) {
            /* @var $order \Magento\Sales\Model\Order */
            $order = $messageParams['order'];
            $billingAddress = $order->getBillingAddress();

            $phone = $billingAddress->getTelephone();
        } elseif (isset($messageParams['customer'])) {
            /** @var $customer CustomerInterface */
            $customer = $messageParams['customer'];
            $addresses = $customer->getAddresses();
            if (empty($addresses)) {
                throw new LocalizedException(__('Cannot send message for this customer: not found address'));
            }
            $phone = $addresses[0]->getTelephone();
        }
        return $this->_prepareRawParams($phone, $message->getToastId(), $params, $storeId);
    }

    /**
     * Send Raw Message
     *
     * @param string $phone
     * @param int $toastMessageId
     * @param array $params
     * @return bool
     */
    public function sendRawMessage($phone, $toastMessageId, $params)
    {
        if (!$this->getIsEnabled()) {
            return false;
        }
        try {
            $storeId = isset($params['storeId']) ? (int)$params['storeId'] : null;
            $url = $this->getToastSendMessageUrl($storeId);
            $messageParams = $this->_prepareRawParams($phone, $toastMessageId, $params, $storeId);

            $this->log('---SEND MESSAGE---', true);
            $this->log($url, true);
            $this->log('message-toast-id: ' . $toastMessageId, true);
            $this->log($messageParams, true);

            $result = $this->sendCurl($url, $messageParams, 'POST', $storeId);
            $this->log($result, true);
            $this->log('---END SEND MESSAGE---', true);
            return true;
        } catch (\Exception $ex) {
            $this->log('--- sendRawMessage FAILED---', true);
            $this->log($ex->getMessage(), true);
            $this->log('message-toast-id: ' . $toastMessageId, true);
            $this->log('---END sendRawMessage FAILED---', true);
        }
        return false;
    }

    /**
     * Prepare Raw Params
     *
     * @param string $phone
     * @param int $toastId
     * @param array $messageParams
     * @param int|null $storeId
     * @return false|string
     */
    protected function _prepareRawParams($phone, $toastId, $messageParams = [], $storeId = null)
    {
//        $this->log('---BEGIN MESSAGE ENCODE CHECK---', true);
//        $this->log($messageParams, true);
        $messageParams = $this->convertEncodeToUtf8($messageParams);
//        $this->log($messageParams, true);
//        $this->log('---END MESSAGE ENCODE CHECK---', true);
        $recipient = [
            'recipientNo' => $phone,
            'templateParameter' => $messageParams
        ];
        if ($this->getCanResend($storeId)) {
            $recipient['resendParameter']=[
                'isResend'=>true,
                'resendType'=>$this->getResendType($storeId)
            ];
        }

        return json_encode([
            'plusFriendId' => "{$this->getPlusFriendId($storeId)}",
            'templateCode' => "{$toastId}",
            'recipientList' => [$recipient],
        ]);
    }

    /**
     * Send Curl
     *
     * @param string $url
     * @param string $field
     * @param string $type
     * @param int|null $storeId
     * @return false|mixed
     */
    public function sendCurl($url, $field, $type, $storeId = null)
    {
        try {
            $this->_curl->addHeader("X-Secret-Key", $this->getSecretKey($storeId));
            $this->_curl->addHeader("Content-Type", "application/json;charset=UTF-8");
            switch (strtoupper($type)) {
                case "GET":
                    $this->_curl->get($url);
                    break;
                case "POST":
                    $this->_curl->post($url, $field);
                    break;
                default:
                    return false;
            }
            $result = $this->_curl->getBody();
            return json_decode($result, true);
        } catch (\Exception $e) {
            $this->log($e->getMessage(), true);
            return false;
        }
    }

    /**
     * Log debug data
     *
     * @param mixed $message
     * @param bool $forceDebug
     */
    public function log($message, $forceDebug = false)
    {
        if ($forceDebug || $this->getIsDebugging()) {
            $this->_logger->debug(json_encode($message));
        }
    }

    /**
     * Get Config
     *
     * @param string $key
     * @param int|null $storeId
     * @return mixed
     */
    public function getConfig($key, $storeId = null)
    {
        $storeId = $storeId ? $storeId : $this->getStoreId();
        return  $this->scopeConfig->getValue(
            $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        try {
            return $this->_storeManager->getStore()->getId();
        } catch (\Exception $exception) {
            return 0;
        }
    }

    /**
     * Get Toast Send Message Url
     *
     * @param int|null $storeId
     * @return array|string|string[]
     */
    public function getToastSendMessageUrl($storeId = null)
    {
        return str_replace('{appkey}', $this->getAppKey($storeId), self::API_SEND_MESSAGE_ENDPOINT);
    }

    /**
     * Get Is Enabled
     *
     * @param int|null $storeId
     * @return mixed
     */
    public function getIsEnabled($storeId = null)
    {
        return $this->getConfig('toast/config/active', $storeId);
    }

    /**
     * Get Is Debugging
     *
     * @param int|null $storeId
     * @return mixed
     */
    public function getIsDebugging($storeId = null)
    {
        return $this->getConfig('toast/config/debug', $storeId);
    }

    /**
     * Get AppKey
     *
     * @param int|null $storeId
     * @return mixed
     */
    public function getAppKey($storeId = null)
    {
        return $this->getConfig('toast/config/app_key', $storeId);
    }

    /**
     * Get Can Resend
     *
     * @param int|null $storeId
     * @return mixed
     */
    public function getCanResend($storeId = null)
    {
        return $this->getConfig('toast/config/can_resend', $storeId);
    }

    /**
     * Get Resend Type
     *
     * @param int|null $storeId
     * @return mixed
     */
    public function getResendType($storeId = null)
    {
        return $this->getConfig('toast/config/resend_type', $storeId);
    }

    /**
     * Get Plus Friend Id
     *
     * @param int|null $storeId
     * @return mixed
     */
    public function getPlusFriendId($storeId = null)
    {
        return $this->getConfig('toast/config/plus_friend_id', $storeId);
    }

    /**
     * Get Secret Key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSecretKey($storeId = null)
    {
        return $this->_encryptor->decrypt($this->getConfig('toast/config/secret_key', $storeId));
    }

    /**
     * Add Log
     *
     * @param int $orderId
     * @param string $status
     * @return int
     */
    public function addLog($orderId, $status)
    {
        try {
            return $this->connection->insert(
                'seoulwebdesign_toast_sendlog',
                ['order_id'=>$orderId, 'send_message_action'=>$status]
            );
        } catch (\Throwable $t) {
            return 0;
        }
    }

    /**
     * Remove Log
     *
     * @param int $orderId
     * @param string $status
     * @return int
     */
    public function removeLog($orderId, $status)
    {
        return $this->connection->delete(
            'seoulwebdesign_toast_sendlog',
            'order_id=' . $orderId . ' AND send_message_action=' . $status
        );
    }

    /**
     * Convert Encode To Utf8
     *
     * @param array|string $params
     * @return array|false|string|string[]|null
     */
    public function convertEncodeToUtf8($params)
    {
        if (!$params) {
            return $params;
        }
        if (is_array($params)) {
            foreach ($params as $key => $val) {
                $val = html_entity_decode((string)$val);
                $currentEncode = mb_detect_encoding($val);
                $currentEncode = $currentEncode ? $currentEncode : "EUC-KR";
                $params[$key] = mb_convert_encoding($val, "UTF-8", $currentEncode);
            }
            return $params;
        } else {
            $params = html_entity_decode((string)$params);
            $currentEncode = mb_detect_encoding($params);
            $currentEncode = $currentEncode ? $currentEncode : "EUC-KR";
            return mb_convert_encoding($params, "UTF-8", $currentEncode);
        }
    }
}
