<?php
/** phpcs:ignoreFile */

namespace OnitsukaTiger\Customer\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;

class CreateCustomer implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $_customerRepositoryInterface;

    /**
     * CreateCustomer constructor.
     *
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($observer->getEvent()->getAccountController() != null) {
            $shoesSize = $observer->getEvent()->getAccountController()->getRequest()->getParam('shoes_size');
            $customer = $this->_customerRepositoryInterface->getById($observer->getEvent()->getCustomer()->getId());
            $customer->setCustomAttribute('shoes_size', $shoesSize);

            $offlineStore = $observer->getEvent()->getAccountController()->getRequest()
                ->getParam('offline_store_id');
            if (!empty($offlineStore)) {
                $customer->setCustomAttribute(
                    'offline_store_id',
                    $offlineStore
                );
            }

            $subscribeKakao = $observer->getEvent()->getAccountController()->getRequest()
                ->getParam('subscribe_kakao');
            if (!empty($subscribeKakao)) {
                $customer->setCustomAttribute(
                    'subscribe_kakao',
                    $subscribeKakao
                );
            }

            $receivePromotionalInformation = $observer->getEvent()->getAccountController()->getRequest()
                ->getParam('custom_newsletter_agreement', false);
            $advertisementAgreement = $observer->getEvent()->getAccountController()->getRequest()
                ->getParam('advertisement_agreement', false);
            if ($receivePromotionalInformation) {
                $customer->setCustomAttribute('receive_promotional_informatio', 1);
            }
            if ($receivePromotionalInformation && $advertisementAgreement) {
                $extensionAttributes = $customer->getExtensionAttributes();
                $extensionAttributes->setIsSubscribed(true);
                $customer->setExtensionAttributes($extensionAttributes);
            }
            $this->_customerRepositoryInterface->save($customer);
        }
        return $this;
    }
}
