<?php
namespace WeltPixel\GA4\Block\XPixel;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use WeltPixel\GA4\Helper\XPixelTracking as XHelper;

class Common extends Template
{
    /**
     * @var XHelper
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
     * @param Context $context
     * @param XHelper $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator
     * @param array $data
     */
    public function __construct(
        Context $context,
        XHelper $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->orderTotalCalculator = $orderTotalCalculator;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isXPixelTrackingEnabled()
    {
        return $this->helper->isXPixelTrackingEnabled();
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldXPixelEventBeTracked($eventName)
    {
        return $this->helper->shoulXPixelEventBeTracked($eventName);
    }

    /**
     * @return string
     */
    public function getXPixelTrackingCodeSnippet()
    {
        return $this->helper->getXPixelTrackingCodeSnippet();
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
    public function getOrderId()
    {
        $order = $this->getOrder();
        return $order->getId();
    }

    /**
     * @return string
     */
    public function getPaymentMethodTitle()
    {
        $order = $this->getOrder();
        $methodTitle = '';
        try {
            $payment = $order->getPayment();
            if ($payment) {
                $method = $payment->getMethodInstance();
                $methodTitle = $method->getTitle();
            }
        } catch (\Exception $e) {}

        return $methodTitle;
    }

    /**
     * @return false|string
     */
    public function getProductsForPurchase()
    {
        $order = $this->getOrder();
        $products = [];
        if ($order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $productItemData = [
                    'content_id' =>  $this->helper->getXProductId($product),
                    'content_name' => addslashes(str_replace('"', '&quot;', $this->helper->getProductName($product))),
                    'content_price' => floatval(number_format($item->getPrice()  ?? 0, 2, '.', '')),
                    'num_items' => (int)$item->getQtyOrdered()
                ];
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
        $order = $this->getOrder();
        $orderValue = $this->orderTotalCalculator->calculateOrderTotal($order, $this->helper);
        return  floatval(number_format($orderValue ?? 0, 2, '.', ''));
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
                'content_id' => $this->helper->getXProductId($item->getProduct()),
                'content_name' => addslashes(str_replace('"', '&quot;', $this->helper->getProductName($item->getProduct()))),
                'content_price' => floatval(number_format($item->getPrice()  ?? 0, 2, '.', '')),
                'num_items' => $item->getQty()
            ];
        }

        return json_encode($cartItems);
    }

    /**
     * @return int
     */
    public function getCheckoutNumItems()
    {
        $quote = $this->checkoutSession->getQuote();
        $numItems = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $numItems += $item->getQty();
        }

        return $numItems;
    }
}
