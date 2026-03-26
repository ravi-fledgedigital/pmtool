<?php
namespace WeltPixel\GA4\Observer\MetaPixel;

use Magento\Framework\Event\ObserverInterface;

class CheckoutCartAddProductObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\MetaPixelTracking
     */
    protected $metaPixelHelper;

    /**
     * @var \WeltPixel\GA4\Helper\RedditPixelTracking
     */
    protected $redditPixelTrackingHelper;

    /**
     * @var \WeltPixel\GA4\Helper\TiktokPixelTracking
     */
    protected $tiktokPixelTrackingHelper;

    /**
     * @var \WeltPixel\GA4\Helper\BingPixelTracking
     */
    protected $bingPixelTrackingHelper;

    /**
     * @var \WeltPixel\GA4\Helper\XPixelTracking
     */
    protected $xPixelTrackingHelper;

    /**
     * @var \WeltPixel\GA4\Helper\KlaviyoPixelTracking
     */
    protected $klaviyoPixelTrackingHelper;

    /**
     * @var \WeltPixel\GA4\Helper\PinterestPixelTracking
     */
    protected $pinterestPixelTrackingHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;


    /**
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelHelper
     * @param \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\TiktokPixelTracking $tiktokPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\XPixelTracking $xPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\PinterestPixelTracking $pinterestPixelTrackingHelper
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    public function __construct(\WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelHelper,
                                \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\TiktokPixelTracking $tiktokPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\XPixelTracking $xPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\PinterestPixelTracking $pinterestPixelTrackingHelper,
                                \Magento\Checkout\Model\Session $_checkoutSession,
                                \Magento\Framework\Locale\ResolverInterface $localeResolver)
    {
        $this->metaPixelHelper = $metaPixelHelper;
        $this->redditPixelTrackingHelper = $redditPixelTrackingHelper;
        $this->tiktokPixelTrackingHelper = $tiktokPixelTrackingHelper;
        $this->bingPixelTrackingHelper = $bingPixelTrackingHelper;
        $this->xPixelTrackingHelper = $xPixelTrackingHelper;
        $this->klaviyoPixelTrackingHelper = $klaviyoPixelTrackingHelper;
        $this->pinterestPixelTrackingHelper = $pinterestPixelTrackingHelper;
        $this->_checkoutSession = $_checkoutSession;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getData('product');
        $request = $observer->getData('request');

        $params = $request->getParams();

        if (isset($params['qty'])) {
            $filter = new \Magento\Framework\Filter\LocalizedToNormalized(
                ['locale' => $this->localeResolver->getLocale()]
            );
            $qty = $filter->filter($params['qty']);
        } else {
            $qty = 1;
        }

        if ($this->metaPixelHelper->isMetaPixelTrackingEnabled() && $this->metaPixelHelper->shouldMetaPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\MetaPixel\TrackingEvents::EVENT_ADD_TO_CART)) {
            $addToCartPushData = $this->metaPixelHelper->metaPixelAddToCartPushData($product, $qty);
            $initialAddTocartPushData = $this->_checkoutSession->getMetaPixelAddToCartData() ?? [];
            $initialAddTocartPushData[] = $addToCartPushData;
            $this->_checkoutSession->setMetaPixelAddToCartData($initialAddTocartPushData);
        }
        if ($this->redditPixelTrackingHelper->isRedditPixelTrackingEnabled() && $this->redditPixelTrackingHelper->shouldRedditPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\RedditPixel\TrackingEvents::EVENT_ADD_TO_CART)) {
            $addToCartPushData = $this->redditPixelTrackingHelper->redditPixelAddToCartPushData($product, $qty);
            $initialAddTocartPushData =  $this->_checkoutSession->getRedditPixelAddToCartData() ?? [];
            $initialAddTocartPushData[] = $addToCartPushData;
            $this->_checkoutSession->setRedditPixelAddToCartData($initialAddTocartPushData);
        }
        if ($this->tiktokPixelTrackingHelper->isTiktokPixelTrackingEnabled() && $this->tiktokPixelTrackingHelper->shouldTiktokPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\TiktokPixel\TrackingEvents::EVENT_ADD_TO_CART)) {
            $addToCartPushData = $this->tiktokPixelTrackingHelper->tiktokPixelAddToCartPushData($product, $qty);
            $initialAddTocartPushData =  $this->_checkoutSession->getTiktokPixelAddToCartData() ?? [];
            $initialAddTocartPushData[] = $addToCartPushData;
            $this->_checkoutSession->setTiktokPixelAddToCartData($initialAddTocartPushData);
        }
        if ($this->bingPixelTrackingHelper->isBingPixelTrackingEnabled() && $this->bingPixelTrackingHelper->shouldBingPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\BingPixel\TrackingEvents::EVENT_ADD_TO_CART)) {
            $addToCartPushData = $this->bingPixelTrackingHelper->bingPixelAddToCartPushData($product, $qty);
            $initialAddTocartPushData =  $this->_checkoutSession->getBingPixelAddToCartData() ?? [];
            $initialAddTocartPushData[] = $addToCartPushData;
            $this->_checkoutSession->setBingPixelAddToCartData($initialAddTocartPushData);
        }
        if ($this->xPixelTrackingHelper->isXPixelTrackingEnabled() && $this->xPixelTrackingHelper->shouldXPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\XPixel\TrackingEvents::EVENT_ADD_TO_CART)) {
            $addToCartPushData = $this->xPixelTrackingHelper->xPixelAddToCartPushData($product, $qty);
            $initialAddTocartPushData =  $this->_checkoutSession->getXPixelAddToCartData() ?? [];
            $initialAddTocartPushData[] = $addToCartPushData;
            $this->_checkoutSession->setXPixelAddToCartData($initialAddTocartPushData);
        }
        if ($this->klaviyoPixelTrackingHelper->isKlaviyoPixelTrackingEnabled() && $this->klaviyoPixelTrackingHelper->shouldKlaviyoPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_ADDED_TO_CART)) {
            $addToCartPushData = $this->klaviyoPixelTrackingHelper->klaviyoPixelAddToCartPushData($product, $qty);
            $initialAddTocartPushData =  $this->_checkoutSession->getKlaviyoPixelAddToCartData() ?? [];
            $initialAddTocartPushData[] = $addToCartPushData;
            $this->_checkoutSession->setKlaviyoPixelAddToCartData($initialAddTocartPushData);
        }
        if ($this->pinterestPixelTrackingHelper->isPinterestPixelTrackingEnabled() && $this->pinterestPixelTrackingHelper->shouldPinterestPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\PinterestPixel\TrackingEvents::EVENT_ADD_TO_CART)) {
            $addToCartPushData = $this->pinterestPixelTrackingHelper->pinterestPixelAddToCartPushData($product, $qty);
            $initialAddTocartPushData = $this->_checkoutSession->getPinterestPixelAddToCartData() ?? [];
            $initialAddTocartPushData[] = $addToCartPushData;
            $this->_checkoutSession->setPinterestPixelAddToCartData($initialAddTocartPushData);
        }

        return $this;
    }
}
