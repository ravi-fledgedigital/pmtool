<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Observer;

use OnitsukaTiger\OrderAttribute\Model\ConfigProvider;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Model\Order;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * event sales_order_place_after
 * name SubscribeToNewsletterByOrderAttribute
 */
class NewsletterSubscriber implements ObserverInterface
{
    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AccountManagementInterface
     */
    private $customerAccountManagement;

    /**
     * @var EmailValidator
     */
    private $emailValidator;

    public function __construct(
        SubscriberFactory $subscriberFactory,
        ConfigProvider $configProvider,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $customerAccountManagement,
        EmailValidator $emailValidator
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->configProvider = $configProvider;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->emailValidator = $emailValidator;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $needSubscribe = false;

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $orderAttributes = $order->getExtensionAttributes()->getOnitsukaTigerOrderAttributes();
        $email = $order->getCustomerEmail();

        if (!empty($orderAttributes)) {
            foreach ($orderAttributes as $attribute) {
                if (
                    $this->configProvider->getValue('checkout/subscribe') == $attribute->getAttributeCode()
                    && $attribute->getValue() > 0
                ) {
                    $needSubscribe = true;
                    break;
                }
            }
        }

        if (
            $needSubscribe
            && $this->validateEmailFormat($email)
            && $this->validateGuestSubscription()
            && $this->validateEmailAvailable($email)
        ) {
            /** @var Subscriber $subscriber */
            $subscriber = $this->subscriberFactory->create()->loadByEmail($email);

            if (!$subscriber->getId() && $subscriber->getSubscriberStatus() != Subscriber::STATUS_SUBSCRIBED) {
                $this->subscriberFactory->create()->subscribe($email);
            }
        }
    }

    /**
     * @return bool
     */
    private function validateGuestSubscription()
    {
        return $this->configProvider->allowGuestSubscribe() || $this->customerSession->isLoggedIn();
    }

    /**
     * @param string $email
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validateEmailAvailable($email)
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        return $this->customerSession->getCustomerDataObject()->getEmail() === $email
            || $this->customerAccountManagement->isEmailAvailable($email, $websiteId);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    private function validateEmailFormat($email)
    {
        return $this->emailValidator->isValid($email);
    }
}
