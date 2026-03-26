<?php
namespace WeltPixel\GA4\Block\MetaPixel;

/**
 * Class \WeltPixel\GA4\Block\MetaPixel\Purchase
 */
class Purchase extends Common
{
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
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \WeltPixel\GA4\Helper\MetaPixelTracking $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \WeltPixel\GA4\Model\OrderTotalCalculator $orderTotalCalculator,
        array $data = []
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderTotalCalculator = $orderTotalCalculator;
        parent::__construct($context, $helper, $data);
    }

    /**
     * @return string
     */
    public function getContentIds()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $productIds = [];

        if ($this->order) {
            foreach ($this->order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $productIds[] = $this->helper->getMetaProductId($product);
            }
        }

        return $this->arrayToCommaSeparatedString($productIds);
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        $productType = 'product';

        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        if ($this->order) {
            foreach ($this->order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $productType = $this->helper->getProductType($product);
                if ($productType == 'product_group') {
                    return $productType;
                }
            }
        }

        return $productType;
    }

    /**
     * @return false|string
     */
    public function getContents()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $orderItems = [];
        if ($this->order) {
            foreach ($this->order->getAllVisibleItems() as $item) {
                $orderItems[] = [
                    'id' => $this->helper->getMetaProductId($item->getProduct()),
                    'quantity' => (int)$item->getQtyOrdered(),
                    'item_price' => floatval(number_format($item->getPrice() ?? 0, 2, '.', ''))
                ];
            }
        }

        return json_encode($orderItems);
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $orderValue = $this->orderTotalCalculator->calculateOrderTotal($this->order, $this->helper);
        return $orderValue ?? 0;
    }

    /**
     * @return string
     */
    public function getPaymentMethodTitle()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        $methodTitle = '';
        try {
            $payment = $this->order->getPayment();
            if ($payment) {
                $method = $payment->getMethodInstance();
                $methodTitle = $method->getTitle();
            }
        }
        catch (\Exception $e) {}

        return $methodTitle;
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
