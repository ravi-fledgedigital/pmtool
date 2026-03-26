<?php

namespace Seoulwebdesign\KakaoSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigHelper extends AbstractHelper
{
    public const CONFIG_PATH = 'kakaosync/general/';
    /**
     * @var EncryptorInterface
     */
    private $_encryptor;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * ConfigHelper constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->_encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Get logger
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get hash value
     *
     * @param string $value
     * @param bool $salt
     * @return string
     */
    public function getHash($value, bool $salt)
    {
        return $this->_encryptor->getHash($value, $salt);
    }

    /**
     * Get config value
     *
     * @param string $key
     * @param int|null $storeId
     * @return mixed|string
     */
    public function getValue($key, $storeId = null)
    {
        try {
            $storeId = $storeId ? $storeId : $this->getStoreId();
            return $this->scopeConfig->getValue(
                $key,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        } catch (\Throwable $exception) {
            return '';
        }
    }

    /**
     * Get general config value
     *
     * @param string $key
     * @param int|null $storeId
     * @return string
     */
    public function getConfig($key, $storeId = null)
    {
        $key = self::CONFIG_PATH . $key;
        return $this->getValue($key, $storeId);
    }

    /**
     * Get store id
     *
     * @return int
     */
    public function getStoreId()
    {
        try {
            return $this->storeManager->getStore()->getId();
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    /**
     * Get website Id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        try {
            return $this->storeManager->getStore()->getWebsiteId();
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    /**
     * Get rest api key value
     *
     * @return string
     */
    public function getRestApiKey()
    {
        return $this->getConfig('rest_api_key');
    }

    /**
     * Get redirect url value
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getConfig('redirect_url');
    }

    /**
     * Get admin key value
     *
     * @return string
     */
    public function getAdminKey()
    {
        return $this->_encryptor->decrypt($this->getConfig('admin_key'));
    }

    /**
     * Get client serect
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->getConfig('client_secret');
    }

    /**
     * Get javascript key value
     *
     * @return string
     */
    public function getJavascriptKey()
    {
        return $this->getConfig('javascript_key');
    }

    /**
     * Get customer group id
     *
     * @return int
     */
    public function getCustomerGroupId()
    {
        return (int)$this->getConfig('customer_group_id');
    }
}
