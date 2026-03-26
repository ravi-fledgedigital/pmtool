<?php

namespace WeltPixel\GA4\Observer\MetaPixel;

use Magento\Framework\Event\ObserverInterface;

class WishListAddProductObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\MetaPixelTracking
     */
    protected $metaPixelTrackingHelper;

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
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;


    /**
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\TiktokPixelTracking $tiktokPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\XPixelTracking $xPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\PinterestPixelTracking
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(\WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\TiktokPixelTracking $tiktokPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\XPixelTracking $xPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper,
                                \WeltPixel\GA4\Helper\PinterestPixelTracking $pinterestPixelTrackingHelper,
                                \Magento\Customer\Model\Session $customerSession)
    {
        $this->metaPixelTrackingHelper = $metaPixelTrackingHelper;
        $this->redditPixelTrackingHelper = $redditPixelTrackingHelper;
        $this->tiktokPixelTrackingHelper = $tiktokPixelTrackingHelper;
        $this->bingPixelTrackingHelper = $bingPixelTrackingHelper;
        $this->xPixelTrackingHelper = $xPixelTrackingHelper;
        $this->klaviyoPixelTrackingHelper = $klaviyoPixelTrackingHelper;
        $this->pinterestPixelTrackingHelper = $pinterestPixelTrackingHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getData('product');

        if ($this->metaPixelTrackingHelper->isMetaPixelTrackingEnabled() && $this->metaPixelTrackingHelper->shouldMetaPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\MetaPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST)) {
            $addToWishlistPushData = $this->metaPixelTrackingHelper->metaPixelAddToWishlistPushData($product);
            $initialAddToWishlistPushData =  $this->customerSession->getMetaPixelAddToWishlistData() ?? [];
            $initialAddToWishlistPushData[] = $addToWishlistPushData;
            $this->customerSession->setMetaPixelAddToWishlistData($initialAddToWishlistPushData);
        }

        if ($this->redditPixelTrackingHelper->isRedditPixelTrackingEnabled() && $this->redditPixelTrackingHelper->shouldRedditPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\RedditPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST)) {
            $addToWishlistPushData = $this->redditPixelTrackingHelper->redditPixelAddToWishlistPushData($product);
            $initialAddToWishlistPushData =  $this->customerSession->getRedditPixelAddToWishlistData() ?? [];
            $initialAddToWishlistPushData[] = $addToWishlistPushData;
            $this->customerSession->setRedditPixelAddToWishlistData($initialAddToWishlistPushData);
        }

        if ($this->tiktokPixelTrackingHelper->isTiktokPixelTrackingEnabled() && $this->tiktokPixelTrackingHelper->shouldTiktokPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\TiktokPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST)) {
            $addToWishlistPushData = $this->tiktokPixelTrackingHelper->tiktokPixelAddToWishlistPushData($product);
            $initialAddToWishlistPushData =  $this->customerSession->getTiktokPixelAddToWishlistData() ?? [];
            $initialAddToWishlistPushData[] = $addToWishlistPushData;
            $this->customerSession->setTiktokPixelAddToWishlistData($initialAddToWishlistPushData);
        }

        if ($this->bingPixelTrackingHelper->isBingPixelTrackingEnabled() && $this->bingPixelTrackingHelper->shouldBingPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\BingPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST)) {
            $addToWishlistPushData = $this->bingPixelTrackingHelper->bingPixelAddToWishlistPushData($product);
            $initialAddToWishlistPushData =  $this->customerSession->getBingPixelAddToWishlistData() ?? [];
            $initialAddToWishlistPushData[] = $addToWishlistPushData;
            $this->customerSession->setBingPixelAddToWishlistData($initialAddToWishlistPushData);
        }

        if ($this->xPixelTrackingHelper->isXPixelTrackingEnabled() && $this->xPixelTrackingHelper->shouldXPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\XPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST)) {
            $addToWishlistPushData = $this->xPixelTrackingHelper->xPixelAddToWishlistPushData($product);
            $initialAddToWishlistPushData =  $this->customerSession->getXPixelAddToWishlistData() ?? [];
            $initialAddToWishlistPushData[] = $addToWishlistPushData;
            $this->customerSession->setXPixelAddToWishlistData($initialAddToWishlistPushData);
        }

        if ($this->klaviyoPixelTrackingHelper->isKlaviyoPixelTrackingEnabled() && $this->klaviyoPixelTrackingHelper->shouldKlaviyoPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_ADDED_TO_WISHLIST)) {
            $addToWishlistPushData = $this->klaviyoPixelTrackingHelper->klaviyoPixelAddToWishlistPushData($product);
            $initialAddToWishlistPushData =  $this->customerSession->getKlaviyoPixelAddToWishlistData() ?? [];
            $initialAddToWishlistPushData[] = $addToWishlistPushData;
            $this->customerSession->setKlaviyoPixelAddToWishlistData($initialAddToWishlistPushData);
        }

        if ($this->pinterestPixelTrackingHelper->isPinterestPixelTrackingEnabled() && $this->pinterestPixelTrackingHelper->shouldPinterestPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\PinterestPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST)) {
            $addToWishlistPushData = $this->pinterestPixelTrackingHelper->pinterestPixelAddToWishlistPushData($product);
            $initialAddToWishlistPushData =  $this->customerSession->getPinterestPixelAddToWishlistData() ?? [];
            $initialAddToWishlistPushData[] = $addToWishlistPushData;
            $this->customerSession->setPinterestPixelAddToWishlistData($initialAddToWishlistPushData);
        }

        return $this;
    }
}
