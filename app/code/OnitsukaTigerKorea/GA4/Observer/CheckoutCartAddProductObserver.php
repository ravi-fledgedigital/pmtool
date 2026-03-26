<?php
namespace OnitsukaTigerKorea\GA4\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use OnitsukaTigerKorea\GA4\Helper\Data as HelperData;
use WeltPixel\GA4\Helper\Data;
use WeltPixel\GA4\Helper\ServerSideTracking;
use Magento\Framework\Filter\LocalizedToNormalized;

class CheckoutCartAddProductObserver extends \WeltPixel\GA4\Observer\CheckoutCartAddProductObserver
{
    protected HelperData $helperData;

    protected $_objectManager;

    public function __construct(
        Data $helper,
        ServerSideTracking $ga4ServerSideHelper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        Session $_checkoutSession,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        HelperData $helperData
    ) {
        $this->helperData = $helperData;
        $this->_objectManager = $objectManager;
        parent::__construct($helper, $ga4ServerSideHelper, $localeResolver, $_checkoutSession);
    }

    /**
     * @param Observer $observer
     * @return self
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->helperData->isKoreaWebsite()) {
            return parent::execute($observer);
        }

        if (!$this->helper->isEnabled()) {
            return $this;
        }

        if (($this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_TO_CART)
            && $this->ga4ServerSideHelper->isDataLayerEventDisabled())) {
            return $this;
        }

        $product = $observer->getData('product');
        $request = $observer->getData('request');

        $params = $request->getParams();

        if (isset($params['qty'])) {
            $filter = new LocalizedToNormalized(
                ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
            );
            $qty = $filter->filter($params['qty']);
        } else {
            $qty = 1;
        }

        if (isset($params['super_attribute'])) {
            $product = $this->_objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->getProductByAttributes($params['super_attribute'], $product);
        }

        if ($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            $superGroup = $params['super_group'];
            $superGroup = is_array($superGroup) ? array_filter($superGroup, 'intval') : [];

            $associatedProducts =  $product->getTypeInstance()->getAssociatedProducts($product);
            foreach ($associatedProducts as $associatedProduct) {
                if (isset($superGroup[$associatedProduct->getId()]) && ($superGroup[$associatedProduct->getId()] > 0)) {
                    $currentAddToCartData = $this->_checkoutSession->getGA4AddToCartData();
                    $addToCartPushData = $this->helper->addToCartPushData($superGroup[$associatedProduct->getId()], $associatedProduct);
                    $newAddToCartPushData = $this->helper->mergeAddToCartPushData($currentAddToCartData, $addToCartPushData);
                    $this->_checkoutSession->setGA4AddToCartData($newAddToCartPushData);
                }
            }
        } else {
            $displayOption = $this->helper->getParentOrChildIdUsage();
            $requestParams = [];
            if (($displayOption == \WeltPixel\GA4\Model\Config\Source\ParentVsChild::CHILD) && ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)) {
                $params['qty'] = $qty;
                $requestParams = $params;
            }
            $this->_checkoutSession->setGA4AddToCartData($this->helper->addToCartPushData($qty, $product, $requestParams));
        }

        return $this;
    }
}
