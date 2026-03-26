<?php

namespace Seoulwebdesign\Base\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    const CONFIG_PATH = 'payment/';
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderResource
     */
    protected $orderResource;

    protected $encryptor;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        OrderFactory $orderFactory,
        OrderResource $orderResource
    ) {
        $this->orderFactory = $orderFactory;
        $this->storeManager = $storeManager;
        $this->orderResource = $orderResource;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->_logger;
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        try {
            return $this->storeManager->getStore()->getId();
        } catch (\Exception $exception) {
            return 0;
        }
    }

    /**
     * @param $key
     * @return string|null
     */
    public function getPaymentConfig($key)
    {
        return $this->getConfig(self::CONFIG_PATH.$key);
    }

    /**
     * @param $key
     * @return mixed|string
     */
    public function getConfig($key)
    {
        try {
            return $this->scopeConfig->getValue(
                $key,
                ScopeInterface::SCOPE_STORE,
                $this->getStoreId()
            );
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * Get order object by id
     *
     * @param integer $orderId
     *
     * @return Order
     */
    public function getOrderById(int $orderId): Order
    {
        $orderModel = $this->orderFactory->create();
        $this->orderResource->load($orderModel, $orderId);
        return $orderModel;
    }

    /**
     * Get order object by $incrementId
     *
     * @param string $incrementId
     *
     * @return Order
     */
    public function getOrderByIncrementId(string $incrementId): Order
    {
        $orderModel = $this->orderFactory->create();
        $this->orderResource->load($orderModel, $incrementId, 'increment_id');

        return $orderModel;
    }

    /**
     * @param $route
     * @param array $params
     * @return string
     */
    public function getUrl($route, array $params = []): string
    {
        return $this->_urlBuilder->getUrl($route, $params);
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
                $currentEncode = $currentEncode ?: "EUC-KR";
                $params[$key] = mb_convert_encoding($val, "UTF-8", $currentEncode);
            }
            return $params;
        } else {
            $currentEncode = mb_detect_encoding($params);
            $currentEncode = $currentEncode ?: "EUC-KR";
            return mb_convert_encoding($params, "UTF-8", $currentEncode);
        }
    }

    /**
     * @param $params
     * @return array|false|string|string[]|null
     */
    public function convertEncodeToEncKr($params)
    {
        if (!$params) {
            return null;
        }
        if (is_array($params)) {
            foreach ($params as $key => $val) {
                $currentEncode = mb_detect_encoding($val);
                $currentEncode = $currentEncode ?: "UTF-8";
                $params[$key] = mb_convert_encoding($val, "EUC-KR", $currentEncode);
            }
            return $params;
        } else {
            $currentEncode = mb_detect_encoding($params);
            $currentEncode = $currentEncode ?: "UTF-8";
            return mb_convert_encoding($params, "EUC-KR", $currentEncode);
        }
    }

    /**
     * @param $data
     * @return string
     */
    public function encrypt($data): string
    {
        return $this->encryptor->encrypt($data);
    }

    /**
     * @param $data
     * @return string
     */
    public function decrypt($data): string
    {
        return $this->encryptor->decrypt($data);
    }
}
