<?php
namespace WeltPixel\GA4\Block\PinterestPixel;

/**
 * Class \WeltPixel\GA4\Block\PinterestPixel\Common
 */
class Common extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \WeltPixel\GA4\Helper\PinterestPixelTracking
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
     * @var int
     */
    protected $orderItemCount = 0;

    /**
     * @var int
     */
    protected $checkoutItemCount = 0;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \WeltPixel\GA4\Helper\PinterestPixelTracking $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \WeltPixel\GA4\Helper\PinterestPixelTracking $helper,
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
    public function isPinterestPixelTrackingEnabled()
    {
        return $this->helper->isPinterestPixelTrackingEnabled();
    }

    /**
     * @return string
     */
    public function getPinterestPixelTrackingCode()
    {
        return $this->helper->getPinterestPixelCodeSnippet();
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldPinterestPixelEventBeTracked($eventName)
    {
        return $this->helper->shouldPinterestPixelEventBeTracked($eventName);
    }

    /**
     * @return false|string
     */
    public function getOrderLineItems()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $products = [];
        $this->orderItemCount = 0;
        if ($this->order) {
            foreach ($this->order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $products[] = [
                    'product_id' => $this->helper->getPinterestProductId($product),
                    'product_name' => addslashes(str_replace('"','&quot;', $this->helper->getProductName($product))),
                    'product_category' => addslashes(str_replace('"','&quot;',$this->helper->getContentCategory($product->getCategoryIds()))),
                    'product_price' => floatval(number_format($item->getPrice() ?? 0, 2, '.', '')),
                    'product_quantity' =>  $item->getQtyOrdered()
                ];

                $this->orderItemCount += (int)$item->getQtyOrdered();
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
     * @return string
     */
    public function getOrderTransactionId()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        return  $this->order->getIncrementId();
    }

    /**
     * @return int
     */
    public function getOrderItemCount()
    {
        return $this->orderItemCount;
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
    public function getCheckoutLineItems()
    {
        $quote = $this->checkoutSession->getQuote();
        $cartItems = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $cartItems[] = [
                'product_id' => $this->helper->getPinterestProductId($product),
                'product_name' => addslashes(str_replace('"','&quot;', $this->helper->getProductName($product))),
                'product_category' => addslashes(str_replace('"','&quot;',$this->helper->getContentCategory($product->getCategoryIds()))),
                'product_price' => floatval(number_format($item->getPrice() ?? 0, 2, '.', '')),
                'product_quantity' =>  $item->getQty()
            ];

            $this->checkoutItemCount += (int)$item->getQty();
        }

        return json_encode($cartItems);
    }

    /**
     * @return int
     */
    public function getCheckoutItemCount()
    {
        return $this->checkoutItemCount;
    }
}
