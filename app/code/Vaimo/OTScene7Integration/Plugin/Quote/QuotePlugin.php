<?php
declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Plugin\Quote;

use Magento\Quote\Model\Quote;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class QuotePlugin
{
    private const ATTRIBUTES_TO_REPLACE = [
        'image',
        'thumbnail',
        'small_image',
    ];

    private ConfigProvider $configProvider;
    private Scene7ImageAssetProviderInterface $assetProvider;

    /**
     * @param ConfigProvider $configProvider
     * @param Scene7ImageAssetProviderInterface $assetProvider
     */
    public function __construct(
        ConfigProvider $configProvider,
        Scene7ImageAssetProviderInterface $assetProvider
    ) {
        $this->configProvider = $configProvider;
        $this->assetProvider = $assetProvider;
    }

    /**
     * @param Quote $quote
     * @param $result
     * @return mixed
     */
    public function afterGetItemByProduct(\Magento\Quote\Model\Quote $quote, $result)
    {
        if (!$this->configProvider->isEnabled() || $result == false) {
            return $result;
        }

        foreach (self::ATTRIBUTES_TO_REPLACE as $attributeCode) {
            $product = $result->getProduct();
            $asset = $this->assetProvider->getAsset($product, $attributeCode);
            $product->setData($attributeCode, $asset->getUrl());
        }
        return $result->setProduct($product);
    }

    /**
     * @param Quote $quote
     * @param $result
     * @return array|mixed
     */
    public function afterGetAllVisibleItems(\Magento\Quote\Model\Quote $quote, $result)
    {
        if (!$this->configProvider->isEnabled()) {
            return $result;
        }
        $items = [];
        foreach ($result as $item) {
            $product = $item->getProduct();
            foreach (self::ATTRIBUTES_TO_REPLACE as $attributeCode) {
                $asset = $this->assetProvider->getAsset($product, $attributeCode);
                $product->setData($attributeCode, $asset->getUrl());
            }
            $item->setProduct($product);
            $items[] = $item;
        }
        return $items;
    }
}
