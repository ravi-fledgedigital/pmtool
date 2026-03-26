<?php
namespace WeltPixel\GA4\Model;

use Magento\Framework\DataObject;

class AddonValidator
{
    /**
     * @var DataObject
     */
    protected $addonConfig;

    /**
     * @param DataObject $addonConfig
     */
    public function __construct(
        DataObject $addonConfig
    ) {
        $this->addonConfig = $addonConfig;
    }

    /**
     * Get all enabled addon helpers
     * @param $storeId
     * @return array
     */
    public function getEnabledAddons($storeId)
    {
        $enabledAddons = [];
        $helpers = $this->addonConfig->getData();

        if (empty($helpers)) {
            return $enabledAddons;
        }

        foreach ($helpers as $helper) {
            try {
                if (method_exists($helper, 'shouldServerSidePurchaseEventBeTracked') && $helper->shouldServerSidePurchaseEventBeTracked($storeId)) {
                    $enabledAddons[] = $helper;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $enabledAddons;
    }
}
