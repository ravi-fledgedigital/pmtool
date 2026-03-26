<?php

namespace Seoulwebdesign\Kakaopay\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Seoulwebdesign\Kakaopay\Logger\Logger;

class ConfigHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_PATH = 'payment/kakaopay/';
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
     * @param $key
     * @return string
     */
    public function getPaymentConfig($key)
    {
        try {
            $cf = self::CONFIG_PATH;
            return (string)$this->scopeConfig->getValue(
                $cf . $key,
                ScopeInterface::SCOPE_STORE,
                $this->getStoreId()
            );
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        try {
            return $this->storeManager->getStore()->getId();
        } catch (\Exception $exception) {
            return 0;
        }
    }

    public function getInstructions()
    {
        return $this->getPaymentConfig('instructions');
    }

    public function getAvailableCards()
    {
        $availableCards = empty($this->getPaymentConfig('available_cards')) ? 'SHINHAN,KB,HYUNDAI,LOTTE,SAMSUNG,NH,BC,HANA,CITI,KAKAOBANK' : $this->getPaymentConfig('available_cards');
        $availableCardsArray = explode(',', $availableCards);
        return json_encode($availableCardsArray);
    }

    public function getPaymentMethodType()
    {
        $method = (string)$this->getPaymentConfig('payment_method_type');
        $methodArray = explode(',', $method);
        if (count($methodArray) > 1) {
            return '';
        }
        return $method;
    }

    public function getCID()
    {
        return $this->getPaymentConfig('cid');
    }

    public function paymentAction()
    {
        return $this->getPaymentConfig('payment_action');
    }

    public function getAdminKey()
    {
        return $this->_encryptor->decrypt($this->getPaymentConfig('admin_key'));
    }

    public function getCanDebug()
    {
        return $this->getPaymentConfig('debug') === "1";
    }

    public function sendCurl($url, $field, $type)
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Seoulwebdesign\Kakaopay\Helper\Curl $curl */
            $curl = $objectManager->create('Seoulwebdesign\Kakaopay\Helper\Curl');
            $curl->addHeader("Authorization", "KakaoAK " . $this->getAdminKey());
            $curl->addHeader("Content-Type", "application/x-www-form-urlencoded");
            switch (strtoupper($type)) {
                case "GET":
                    $curl->get($url);
                    break;
                case "POST":
                    $curl->post($url, $field);
                    break;
                case "DELETE":
                    $curl->delete($url);
                    break;
                case "PATCH":
                    $curl->patch($url, $field);
                    break;
                default:
                    return false;
            }
            $result = $curl->getBody();
            return json_decode($result, true);
        } catch (\Throwable $e) {
            $context['type'] = $type;
            $context['url'] = $url;
            $context['adminkey'] = $this->getAdminKey();
            $context['field'] = $field;
            $this->logger->debug($e->getMessage(), $context);
            return false;
        }
    }
}
