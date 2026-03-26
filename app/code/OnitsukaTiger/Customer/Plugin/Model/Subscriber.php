<?php

namespace OnitsukaTiger\Customer\Plugin\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class EmailNotification
 * @package OnitsukaTiger\Customer\Plugin
 */
class Subscriber {

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->_request = $request;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subject
     * @param \Closure $proceed
     * @return bool
     */
    public function aroundSendConfirmationSuccessEmail(
        \Magento\Newsletter\Model\Subscriber $subject,
        \Closure $proceed
    ) {
        return false;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subject
     * @param \Closure $proceed
     * @return bool
     */
    public function aroundSendConfirmationRequestEmail(
        \Magento\Newsletter\Model\Subscriber $subject,
        \Closure $proceed
    ) {
        return false;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subject
     * @param $result
     * @return int|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetStatus(\Magento\Newsletter\Model\Subscriber $subject, $result)
    {
        $receivePromotionalInformation = $this->_request
            ->getParam('custom_newsletter_agreement', false);
        $advertisementAgreement = $this->_request
            ->getParam('advertisement_agreement', false);
        $storeCode = $this->storeManager->getStore()->getCode();
        if ($advertisementAgreement &&  $receivePromotionalInformation &&
            $storeCode == 'web_kr_ko' &&
            (int)$result == (int)\Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED) {
            return  \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED;
        } elseif($advertisementAgreement && $receivePromotionalInformation &&
            $storeCode == 'web_kr_ko' && empty($result)){
            return  \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED;
        }
        return $result;
    }
}
