<?php
/**
 * PaymentInformationManagement
 */

namespace OnitsukaTigerKorea\Checkout\Plugin\Model\Checkout;


class PaymentInformationManagement
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
     * PaymentInformationManagement constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data     $jsonHelper,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Quote\Model\QuoteRepository    $quoteRepository
    )
    {
        $this->jsonHelper = $jsonHelper;
        $this->filterManager = $filterManager;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     */
    public function beforeSavePaymentInformation(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
                                                             $cartId,
        \Magento\Quote\Api\Data\PaymentInterface             $paymentMethod
    )
    {
        $extAttributes = $paymentMethod->getExtensionAttributes();
        $quote = $this->quoteRepository->getActive($cartId);
        /*if($extAttributes->getIsGift()) {*/
            $quote->setGiftPackaging($extAttributes->getIsGift());
        /*}*/
    }
}