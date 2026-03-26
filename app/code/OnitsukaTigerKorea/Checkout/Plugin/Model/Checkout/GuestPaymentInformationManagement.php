<?php

namespace OnitsukaTigerKorea\Checkout\Plugin\Model\Checkout;

/**
 * Class PaymentInformationManagement
 * @package OnitsukaTigerKorea\Checkout\Plugin\Model\Checkout
 */
class GuestPaymentInformationManagement
{
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $filterManager;
    /**
     * Checkout Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Cart Repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * PaymentInformationManagement constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data        $jsonHelper,
        \Magento\Framework\Filter\FilterManager    $filterManager,
        \Magento\Quote\Model\QuoteRepository       $quoteRepository,
        \Magento\Checkout\Model\Session            $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->filterManager = $filterManager;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSavePaymentInformation(
        \Magento\Checkout\Model\GuestPaymentInformationManagement $subject,
                                                                  $cartId,
                                                                  $email,
        \Magento\Quote\Api\Data\PaymentInterface                  $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface                  $billingAddress = null
    ) {
        $extensionAttributes = $paymentMethod->getExtensionAttributes();
        if (!empty($extensionAttributes)) {
            $quoteId = $this->checkoutSession->getQuoteId();
            $quote = $this->cartRepository->get($quoteId);
            if ($extensionAttributes->getUsePersonalInformation()) {
                $usePersonalInformation = $extensionAttributes->getUsePersonalInformation();
                $quote->setUsePersonalInformation($usePersonalInformation);
            }
            if ($extensionAttributes->getSendNewsletterSubscription()) {
                $sendNewsletterSubscription = $extensionAttributes->getSendNewsletterSubscription();
                $quote->setSendNewsletterSubscription($sendNewsletterSubscription);
            }
            if ($extensionAttributes->getPurchaserName()) {
                $purchaserName = $extensionAttributes->getPurchaserName();
                $quote->setPurchaserName($purchaserName);
            }
            if ($extensionAttributes->getCompanyTaxCode()) {
                $companyTaxCode = $extensionAttributes->getCompanyTaxCode();
                $quote->setCompanyTaxCode($companyTaxCode);
            }
            /*if($extensionAttributes->getIsGift()) {*/
                $quote->setGiftPackaging($extensionAttributes->getIsGift());
            /*}*/
        }
    }
}