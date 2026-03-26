<?php
namespace OnitsukaTiger\Newsletter\Plugin\Controller\Subscriber;

use Magento\Framework\Controller\ResultFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\SalesRule\Api\Data\CouponGenerationSpecInterfaceFactory;

class NewAction  {

    const XML_PATH_SUCCESS_EMAIL_TEMPLATE = 'newsletter/subscription/success_email_template';
    const XML_PATH_SUCCESS_EMAIL_IDENTITY = 'newsletter/subscription/success_email_identity';
    const XML_PATH_CART_RULE_ID = 'newsletter/cart_rule_generate/cart_rule';
    const XML_PATH_CART_RULE_LENGTH = 'newsletter/cart_rule_generate/cart_rule_length';
    const XML_PATH_CART_RULE_FORMAT = 'newsletter/cart_rule_generate/cart_rule_format';
    const XML_PATH_CART_RULE_PREFIX = 'newsletter/cart_rule_generate/cart_rule_prefix';
    const XML_PATH_CART_RULE_SUFFIX = 'newsletter/cart_rule_generate/cart_rule_suffix';
    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $_blockFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Subscriber factory
     *
     * @var SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\SalesRule\Model\Coupon\Massgenerator
     */
    protected $couponGenerator;
    /**
     * @var \OnitsukaTiger\Checkout\Helper\Data
     */
    private $_helper;

    private $dataLayerHelper;

    /**
     * NewAction constructor.
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param SubscriberFactory $subscriberFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \OnitsukaTiger\Newsletter\Helper\Data $helper
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        SubscriberFactory $subscriberFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \OnitsukaTiger\Newsletter\Helper\Data $helper,
        \Magento\Framework\App\Action\Context $context,
        \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->resultFactory = $context->getResultFactory();
        $this->_storeManager = $storeManager;
        $this->_blockFactory = $blockFactory;
        $this->_filterProvider = $filterProvider;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_helper = $helper;
        $this->dataLayerHelper = $dataLayerHelper;
    }

    /**
     * @param \Magento\Newsletter\Controller\Subscriber\NewAction $subject
     * @param $result
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function afterExecute(\Magento\Newsletter\Controller\Subscriber\NewAction $subject,  $result)
    {
        if($subject->getRequest()->getParam('isAjax')){

            /** @var \Magento\Customer\Block\Address\Grid $block */
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $message = __('You have successfully signed up to receive Onitsuka Tiger newsletters.');
            $error = 0;
            if($errorMessage = $this->messageManager->getMessages()->getErrors()){
                if ($errorMessage[0]->getData() && isset($errorMessage[0]->getData()['message'])) {
                    $message = $errorMessage[0]->getData()['message'];
                }else{
                    $message = $errorMessage[0]->getText();
                }
                $error = 1;
            }
            $response = [
                'successHtml' => $this->getCmsMessage(),
                'message' => $message,
                'success' => 1,
                'error' => $error
            ];
            if($subject->getRequest()->getParam('dob') || $subject->getRequest()->getParam('isPage')){
                $newsletterFactory = $this->_subscriberFactory->create()->loadByEmail($subject->getRequest()->getParam('email'));
                $newsletterFactory->setDob($subject->getRequest()->getParam('dob'));
                $newsletterFactory->setGender($subject->getRequest()->getParam('gender'));
                $newsletterFactory->save();
                $response['popup'] = 1;
            }
            if(!$error){
                $response['popupMessage'] = __('Thank You');
                $resultMessage = $this->_helper->sendDiscountCode($subject->getRequest()->getParam('email'));
                if($resultMessage){
                    $response['popupMessage'] = $resultMessage;
                    $response['message'] = $resultMessage;
                }
            }
            // clear message
            $this->messageManager->getMessages(true);

            if($this->dataLayerHelper->isEnabledAdobeLaunch()){
                /*addind adobe data layer for end*/
                $response['pageInfo'] = $this->dataLayerHelper->getPageInfo();
                $response['userInfo'] = $this->dataLayerHelper->getUserInfo();
                /*addind adobe data layer for end*/
            }

            $resultJson->setData($response);
            return $resultJson;
        }
        return $result;
    }
    private function getCmsMessage(){
        $blockId = 'newsletter-success-message';
        $html = '';
        if ($blockId) {
            $storeId = $this->_storeManager->getStore()->getId();
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            $html = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
        }
        return   $html;
    }

}
