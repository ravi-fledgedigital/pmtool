<?php

namespace WeltPixel\GA4\Helper;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TiktokPixelPurchaseTracking extends TiktokPixelTracking
{
    const ADDON_TYPE_NAME = 'tiktok_purchase';

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldServerSidePurchaseEventBeTracked($storeId)
    {
        $this->reloadConfigOptions($storeId);
        return $this->isServerSideTrackingEnabled() && $this->shouldTikTokServerSideEventBeTracked(\WeltPixel\GA4\Model\Config\Source\TiktokPixel\TrackingEvents::EVENT_PURCHASE);
    }

}
