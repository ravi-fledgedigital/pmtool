<?php

namespace OnitsukaTiger\Newsletter\Helper;

use Magento\Eav\Model\Config;
use Magento\Framework\Json\EncoderInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_SUCCESS_EMAIL_TEMPLATE = 'newsletter/subscription/success_email_template';
    const XML_PATH_SUCCESS_EMAIL_IDENTITY = 'newsletter/subscription/success_email_identity';
    const XML_PATH_CART_RULE_ID = 'newsletter/cart_rule_generate/cart_rule';
    const XML_PATH_CART_RULE_LENGTH = 'newsletter/cart_rule_generate/cart_rule_length';
    const XML_PATH_CART_RULE_FORMAT = 'newsletter/cart_rule_generate/cart_rule_format';
    const XML_PATH_CART_RULE_PREFIX = 'newsletter/cart_rule_generate/cart_rule_prefix';
    const XML_PATH_CART_RULE_SUFFIX = 'newsletter/cart_rule_generate/cart_rule_suffix';


    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $_scopeConfig;
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    private $ruleFactory;
    /**
     * @var \Magento\SalesRule\Model\Coupon\Massgenerator
     */
    private $couponGenerator;
    /**
     * @var SubscriberFactory
     */
    private $_subscriberFactory;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $_transportBuilder;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \OnitsukaTiger\Logger\Logger
     */
    private $logger;

    /**
     * Data constructor.
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Model\Coupon\Massgenerator $couponGenerator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param SubscriberFactory $subscriberFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \OnitsukaTiger\Logger\Logger $logger
     */
    public function __construct(
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\Coupon\Massgenerator $couponGenerator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        SubscriberFactory $subscriberFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Helper\Context $context,
        \OnitsukaTiger\Logger\Logger $logger
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->ruleFactory = $ruleFactory;
        $this->couponGenerator = $couponGenerator;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function sendDiscountCode($email){
        $newsletterFactory = $this->_subscriberFactory->create()->loadByEmail($email);
        if(!$newsletterFactory->getSendCode()){
            $code = $this->generateCouponCode();
            if($code){
                $newsletterFactory = $this->_subscriberFactory->create()->loadByEmail($email);
                $newsletterFactory->setSendCode('1');
                $newsletterFactory->save();
                $this->_transportBuilder->setTemplateIdentifier(
                    $this->_scopeConfig->getValue(
                        self::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->_storeManager->getStore()->getId(),
                    ]
                )->setTemplateVars(
                    [
                        'subscriber' => $newsletterFactory,
                        'coupon' => [
                            'code' => $code[0],
                        ],
                    ]
                )->setFrom(
                    $this->_scopeConfig->getValue(
                        self::XML_PATH_SUCCESS_EMAIL_IDENTITY,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )->addTo(
                    $newsletterFactory->getEmail(),
                    $newsletterFactory->getName()
                );
                try {
                    $transport = $this->_transportBuilder->getTransport();
                    $transport->sendMessage();
                }catch (\Exception $e){
                    $this->logger->error($e->getMessage());
                    return $e->getMessage();
                }
            }
        }
    }
    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function generateCouponCode(){
        $data = $this->getStoreConfiguration();
        $rule = $this->ruleFactory->create()->load($data['rule_id']);
        if($rule){
            $this->couponGenerator->setData($data);
            $this->couponGenerator->setData('to_date', $rule->getToDate());
            $this->couponGenerator->setData('uses_per_coupon', $rule->getUsesPerCoupon());
            $this->couponGenerator->setData('usage_per_customer', $rule->getUsesPerCustomer());

            $this->couponGenerator->generatePool();
            $code =  $this->couponGenerator->getGeneratedCodes();
            return $code;
        }
        return false;
    }

    /**
     * @return array
     */
    private function getStoreConfiguration(){
        $data = [
            'rule_id' =>$this->_scopeConfig->getValue(
                self::XML_PATH_CART_RULE_ID,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'length' =>$this->_scopeConfig->getValue(
                self::XML_PATH_CART_RULE_LENGTH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'format' =>$this->_scopeConfig->getValue(
                self::XML_PATH_CART_RULE_FORMAT,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'prefix' =>$this->_scopeConfig->getValue(
                self::XML_PATH_CART_RULE_PREFIX,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'suffix' =>$this->_scopeConfig->getValue(
                self::XML_PATH_CART_RULE_SUFFIX,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'dash' => "0",
            'qty' => '1',
            'quantity' => '1'
        ];
        return $data;
    }
}
