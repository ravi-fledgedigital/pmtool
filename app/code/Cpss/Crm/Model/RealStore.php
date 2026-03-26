<?php

namespace Cpss\Crm\Model;

class RealStore extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{

    const CACHE_TAG = 'crm_real_stores';
    const SHOP_OPEN = "REG";

    protected $_cacheTag = 'crm_real_stores';
    protected $_eventPrefix = 'crm_real_stores';
    protected $_encryptor;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_encryptor = $encryptor;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Cpss\Crm\Model\ResourceModel\RealStore');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

    /**
     * Login
     * Returns Acccess Token
     *
     * @param  mixed $shopId
     * @param  mixed $password
     * @return $this
     */
    public function login($shopId, $password)
    {
        $this->loadById($shopId);
        return ($this->getId() && strtoupper($this->getShopStatus()) === self::SHOP_OPEN && $this->_encryptor->validateHash($password, $this->getPassword())) ? $this : NULL;
    }

    /**
     * validateAccessToken
     *
     * @param  mixed $shopId
     * @param  mixed $accessToken
     * @return bool
     */
    public function validateAccessToken($shopId, $accessToken)
    {
        $this->loadById($shopId);
        return ($this->getId() && strtoupper($this->getShopStatus()) === self::SHOP_OPEN && $accessToken === $this->getAccessToken());
    }

    /**
     * Load shop by its shopId
     *
     * @param string $shopId
     * @return $this
     */
    public function loadById($shopId)
    {
        $data = $this->getResource()->loadByShopId($shopId);
        if ($data !== false) {
            if (isset($data['extra']) && is_string($data['extra'])) {
                $data['extra'] = $this->serializer->unserialize($data['extra']);
            }

            $this->setData($data);
            $this->setOrigData();
        }

        return $this;
    }

    public function getPassword()
    {
        return $this->_getData('shop_password_hash');
    }

    // public function getShopAccount()
    // {
    //     return $this->_getData('shop_account');
    // }

    public function getShopName()
    {
        return $this->_getData('shop_name');
    }

    public function getShopId()
    {
        return $this->_getData('shop_password_hash');
    }

    public function getAccessToken()
    {
        return $this->_getData('access_token');
    }

    public function getShopStatus()
    {
        return $this->_getData('shop_status');
    }
}
