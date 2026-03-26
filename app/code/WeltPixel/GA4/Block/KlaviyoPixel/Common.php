<?php
namespace WeltPixel\GA4\Block\KlaviyoPixel;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use WeltPixel\GA4\Helper\KlaviyoPixelTracking as KlaviyoHelper;

class Common extends Template
{
    /**
     * @var KlaviyoHelper
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $imageBuilder;

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
     * @param KlaviyoHelper $helper
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator
     * @param array $data
     */
    public function __construct(
        Context $context,
        KlaviyoHelper $helper,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->imageBuilder = $imageBuilder;
        $this->orderTotalCalculator = $orderTotalCalculator;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isKlaviyoPixelTrackingEnabled()
    {
        return $this->helper->isKlaviyoPixelTrackingEnabled();
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldKlaviyoPixelEventBeTracked($eventName)
    {
        return $this->helper->shouldKlaviyoPixelEventBeTracked($eventName);
    }

    /**
     * @return string
     */
    public function getKlaviyoPublicApiKey()
    {
        return $this->helper->getKlaviyoPublicApiKey();
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
        }
        catch (\Exception $e) {}

        return $methodTitle;
    }

    /**
     * @return false|string
     */
    public function getOrderContents()
    {
        $order = $this->getOrder();
        $products = [];
        if ($order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $productItemData = [
                    'ProductID' => $product->getId(),
                    'SKU' => $product->getSku(),
                    'ProductName' => addslashes(str_replace('"','&quot;', $this->helper->getProductName($product))),
                    'Price' => floatval(number_format($item->getPrice() ?? 0, 2, '.', '')),
                    'Quantity' => $item->getQtyOrdered(),
                    'URL' => $this->helper->getProductUrl($product),
                    'Categories' => json_encode($this->helper->getCategoryPath($product->getCategoryIds()))
                ];
                $products[] = $productItemData;
            }
        }

        return json_encode($products);
    }

    /**
     * @return int
     */
    public function getOrderNumItems()
    {
        $order = $this->getOrder();
        $numItems = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $numItems += $item->getQtyOrdered();
        }

        return $numItems;
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
            $product = $item->getProduct();
            $cartItems[] = [
                'ProductID' => $product->getId(),
                'SKU' => $product->getSku(),
                'ProductName' => addslashes(str_replace('"','&quot;', $this->helper->getProductName($product))),
                'Price' => floatval(number_format($item->getPrice() ?? 0, 2, '.', '')),
                'Quantity' => $item->getQty(),
                'URL' => $this->helper->getProductUrl($product),
                'Categories' => json_encode($this->helper->getCategoryPath($product->getCategoryIds())),
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
