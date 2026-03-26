<?php

namespace WeltPixel\GA4\Plugin;

class HttpContext
{

    /**
     * GA4 context
     */
    const CONTEXT_GA4 = 'weltpixel_ga4';

    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $helper;

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
     * @param \WeltPixel\GA4\Helper\Data $helper
     * @param \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\TiktokPixelTracking $tiktokPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\XPixelTracking $xPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper
     */
    public function __construct(
        \WeltPixel\GA4\Helper\Data $helper,
        \WeltPixel\GA4\Helper\MetaPixelTracking $metaPixelTrackingHelper,
        \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper,
        \WeltPixel\GA4\Helper\TiktokPixelTracking $tiktokPixelTrackingHelper,
        \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper,
        \WeltPixel\GA4\Helper\XPixelTracking $xPixelTrackingHelper,
        \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper
    )
    {
        $this->helper = $helper;
        $this->metaPixelTrackingHelper = $metaPixelTrackingHelper;
        $this->redditPixelTrackingHelper = $redditPixelTrackingHelper;
        $this->tiktokPixelTrackingHelper = $tiktokPixelTrackingHelper;
        $this->bingPixelTrackingHelper = $bingPixelTrackingHelper;
        $this->xPixelTrackingHelper = $xPixelTrackingHelper;
        $this->klaviyoPixelTrackingHelper = $klaviyoPixelTrackingHelper;
    }

    /**
     * @param \Magento\Framework\App\Http\Context $subject
     * @return null
     */
    public function beforeGetVaryString(
        \Magento\Framework\App\Http\Context $subject
    ) {
        if (($this->helper->isEnabled() ||
                $this->metaPixelTrackingHelper->isMetaPixelTrackingEnabled() ||
                $this->tiktokPixelTrackingHelper->isTiktokPixelTrackingEnabled() ||
                $this->redditPixelTrackingHelper->isRedditPixelTrackingEnabled() ||
                $this->xPixelTrackingHelper->isXPixelTrackingEnabled() ||
                $this->klaviyoPixelTrackingHelper->isKlaviyoPixelTrackingEnabled() ||
                $this->bingPixelTrackingHelper->isBingPixelTrackingEnabled()) &&
            $this->helper->isCookieRestrictionModeEnabled()
        ) {
            $subject->setValue(
                self::CONTEXT_GA4,
                'isEnabled',
                ''
            );
        }
        return null;
    }
}
