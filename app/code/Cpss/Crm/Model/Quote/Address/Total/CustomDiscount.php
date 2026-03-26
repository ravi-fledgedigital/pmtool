<?php
namespace Cpss\Crm\Model\Quote\Address\Total;

class CustomDiscount extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{

    /**
    * @var \Magento\Framework\Pricing\PriceCurrencyInterface
    */
    protected $_priceCurrency;
    protected $checkoutSession;
    protected $customerHelper;
    protected $requestInterface;

    /**
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency [description]
     */
    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Cpss\Crm\Helper\Customer $customerHelper,
        \Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->_priceCurrency = $priceCurrency;
        $this->checkoutSession = $checkoutSession;
        $this->customerHelper = $customerHelper;
        $this->requestInterface = $requestInterface;
        
    }

    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        return $this;
    }

    /**
     * Assign subtotal amount and label to address object
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param Address\Total $total
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        return [
            'code' => 'Point_Discount',
            'title' => $this->getLabel(),
            'value' => $this->checkoutSession->getAppliedPoints()
        ];
    }

    /**
     * get label
     * 
     * @return string
     */
    public function getLabel()
    {
        return __('Point Discount');
    }
}