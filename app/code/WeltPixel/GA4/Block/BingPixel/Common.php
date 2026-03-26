<?php
namespace WeltPixel\GA4\Block\BingPixel;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use WeltPixel\GA4\Helper\BingPixelTracking as BingHelper;

class Common extends Template
{
    /**
     * @var BingHelper
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
     * @param BingHelper $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator
     * @param array $data
     */
    public function __construct(
        Context $context,
        BingHelper $helper,
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
    public function isBingPixelTrackingEnabled()
    {
        return $this->helper->isBingPixelTrackingEnabled();
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldBingPixelEventBeTracked($eventName)
    {
        return $this->helper->shouldBingPixelEventBeTracked($eventName);
    }

    /**
     * @return string
     */
    public function getBingPixelTrackingCodeSnippet()
    {
        return $this->helper->getBingPixelTrackingCodeSnippet();
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
                    'id' =>  $this->helper->getBingProductId($product),
                    'name' => addslashes(str_replace('"', '&quot;', $this->helper->getProductName($product))),
                    'price' => floatval(number_format($item->getPrice() ?? 0, 2, '.', '')),
                    'quantity' => $item->getQtyOrdered(),
                    'category' => $this->helper->getContentCategory($product->getCategoryIds())
                ];
                $products[] = $productItemData;
            }
        }

        return json_encode($products);
    }

    /**
     * @return string
     */
    public function getPurchaseContentIds()
    {
        $order = $this->getOrder();
        $productIds = [];

        if ($order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $productIds[] = $this->helper->getBingProductId($product);
            }
        }

        return $this->arrayToCommaSeparatedString($productIds);
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
                'id' => $this->helper->getBingProductId($item->getProduct()),
                'name' => addslashes(str_replace('"', '&quot;', $this->helper->getProductName($item->getProduct()))),
                'price' => floatval(number_format($item->getPrice() ?? 0, 2, '.', '')),
                'quantity' => $item->getQty(),
                'category' => $this->helper->getContentCategory($item->getProduct()->getCategoryIds())
            ];
        }

        return json_encode($cartItems);
    }

    /**
     * @return string
     */
    public function getCheckoutContentIds()
    {
        $quote = $this->checkoutSession->getQuote();
        $productIds = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productIds[] = $this->helper->getBingProductId($product);
        }

        return $this->arrayToCommaSeparatedString($productIds);
    }

    /**
     * @param $array
     * @return string
     */
    protected function arrayToCommaSeparatedString($array)
    {
        return implode(',', array_map(function ($i) {
            return '"' . $i . '"';
        }, $array));
    }

}
