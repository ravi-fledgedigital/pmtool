<?php
namespace WeltPixel\GA4\Block\TiktokPixel;

/**
 * Class \WeltPixel\GA4\Block\TiktokPixel\Common
 */
class Common extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \WeltPixel\GA4\Helper\TiktokPixelTracking
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var \WeltPixel\GA4\Model\OrderTotalCalculator
     */
    protected $orderTotalCalculator;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \WeltPixel\GA4\Helper\TiktokPixelTracking $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \WeltPixel\GA4\Helper\TiktokPixelTracking $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator,
        array $data = []
    )
    {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->orderTotalCalculator = $orderTotalCalculator;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isTiktokPixelTrackingEnabled()
    {
        return $this->helper->isTiktokPixelTrackingEnabled();
    }

    /**
     * @return string
     */
    public function getTiktokPixelTrackingCodeSnippet()
    {
        return $this->helper->getTiktokPixelTrackingCodeSnippet();
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldTiktokPixelEventBeTracked($eventName)
    {
        return $this->helper->shouldTiktokPixelEventBeTracked($eventName);
    }

    /**
     * @return int
     */
    public function getQuoteId()
    {
        $quote = $this->checkoutSession->getQuote();
        return $quote->getId();
    }

    /**
     * @return float|int
     */
    public function getCheckoutValue()
    {
        $quote = $this->checkoutSession->getQuote();
        $grandTotal = $quote->getGrandTotal() ?? 0;

        return $grandTotal;
    }

    /**
     * @return false|string
     */
    public function getCheckoutContents()
    {
        $quote = $this->checkoutSession->getQuote();
        $cartItems = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $cartItems[] = [
                'content_id' => $this->helper->getTiktokProductId($item->getProduct()),
                'content_type' => 'product',
                'content_name' => addslashes(str_replace('"','&quot;', $this->helper->getProductName($item->getProduct())))
            ];
        }

        return json_encode($cartItems);
    }

    /**
     * @return false|string
     */
    public function getProductsForPurchase($qty = false)
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $products = [];
        if ($this->order) {
            foreach ($this->order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $productItemData = [
                    'content_id' => $this->helper->getTiktokProductId($product),
                    'content_type' => 'product',
                    'content_name' => addslashes(str_replace('"','&quot;', $this->helper->getProductName($product)))
                ];
                if ($qty) {
                    $productItemData['quantity'] = (int)$item->getQtyOrdered();
                }

                $products[] = $productItemData;
            }
        }

        return json_encode($products);
    }

    /**
     * @return float|int
     */
    public function getOrderValue()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $orderValue = $this->orderTotalCalculator->calculateOrderTotal($this->order, $this->helper);
        return  floatval(number_format($orderValue ?? 0, 2, '.', ''));
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        return $this->order->getId();
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        return $this->order;
    }
}
