<?php

namespace WeltPixel\GA4\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;

class WishlistAddToFromCart
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4ServerSideHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistBuilderInterface */
    protected $addToWishlistBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

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
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistBuilderInterface $addToWishlistBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\TiktokPixelTracking $tiktokPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\XPixelTracking $xPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper
    */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4ServerSideHelper,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        \WeltPixel\GA4\Api\ServerSide\Events\AddToWishlistBuilderInterface $addToWishlistBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper,
        \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper,
        \WeltPixel\GA4\Helper\TiktokPixelTracking $tiktokPixelTrackingHelper,
        \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper,
        \WeltPixel\GA4\Helper\XPixelTracking $xPixelTrackingHelper,
        \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper
    )
    {
        $this->ga4ServerSideHelper = $ga4ServerSideHelper;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->addToWishlistBuilder = $addToWishlistBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->metaPixelTrackingHelper = $metaPixelTrackingHelper;
        $this->redditPixelTrackingHelper = $redditPixelTrackingHelper;
        $this->tiktokPixelTrackingHelper = $tiktokPixelTrackingHelper;
        $this->bingPixelTrackingHelper = $bingPixelTrackingHelper;
        $this->xPixelTrackingHelper = $xPixelTrackingHelper;
        $this->klaviyoPixelTrackingHelper = $klaviyoPixelTrackingHelper;
    }

    /**
     * @param \Magento\Wishlist\Model\Wishlist $subject
     * @param $result
     * @param int|Product $product
     * @param DataObject|array|string|null $buyRequest
     * @param bool $forciblySetQty
     * @return \Magento\Wishlist\Model\Item|string
     * @throws \Magento\Catalog\Model\Product\Exception
     */
    public function afterAddNewItem(
        \Magento\Wishlist\Model\Wishlist $subject,
        $result,
        $product,
        $buyRequest = null,
        $forciblySetQty = false
        )
    {
        if (!$this->ga4ServerSideHelper->isEnabled()) {
            return $result;
        }

        if ($product instanceof Product) {
            return $result;
        }

        $productId = (int)$product;
        $product = null;
        if ($productId && $result) {
            try {
                /** @var Product $product */
                $product = $this->productRepository->getById($productId);
            } catch (\Exception $e) {
                return $result;
            }
        }

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

        if ($this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_TO_CART)) {
            $addToWishlistEvent = $this->addToWishlistBuilder->getAddToWishlistEvent($product, $buyRequest, null);
            $this->ga4ServerSideApi->pushAddToWishlistEvent($addToWishlistEvent);
        }

        if (($this->ga4ServerSideHelper->isServerSideTrakingEnabled() && $this->ga4ServerSideHelper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_ADD_TO_CART)
            && $this->ga4ServerSideHelper->isDataLayerEventDisabled())) {
            return $result;
        }

        if ($product) {
            $this->customerSession->setGA4AddToWishListData($this->ga4ServerSideHelper->addToWishListPushData($product, $buyRequest, null));
        }

        return $result;
    }


}
