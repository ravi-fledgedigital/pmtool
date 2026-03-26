<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\ConfigInterface;
use Vaimo\OTScene7Integration\Api\Data\Scene7AssetInterface;
use Vaimo\OTScene7Integration\Api\Data\Scene7AssetInterfaceFactory;
use Vaimo\OTScene7Integration\Api\Scene7ImageAssetProviderInterface;

class Scene7ImageAssetProvider implements Scene7ImageAssetProviderInterface
{
    /**
     * @var mixed[]
     */
    private array $imageMiscParams = [];

    private Scene7AssetInterfaceFactory $assetFactory;
    private ConfigProvider $configProvider;
    private ConfigInterface $presentationConfig;
    private ParamsBuilder $imageParamsBuilder;
    private ImageHelper $imageHelper;
    private SerializerInterface $serializer;

    public function __construct(
        ConfigProvider $configProvider,
        Scene7AssetInterfaceFactory $assetFactory,
        ConfigInterface $presentationConfig,
        ParamsBuilder $imageParamsBuilder,
        SerializerInterface $serializer,
        ImageHelper $imageHelper
    ) {
        $this->assetFactory = $assetFactory;
        $this->configProvider = $configProvider;
        $this->presentationConfig = $presentationConfig;
        $this->imageParamsBuilder = $imageParamsBuilder;
        $this->imageHelper = $imageHelper;
        $this->serializer = $serializer;
    }

    /**
     * Generate Scene 7 Asset object, that contains url and size of an Image
     *
     * @param ProductInterface $product
     * @param string $imageId
     * @return Scene7AssetInterface
     */
    public function getAsset(ProductInterface $product, string $imageId): Scene7AssetInterface
    {
        $fileName = $this->getC7FileName($product, $this->getImageMiscParams($imageId));

        return $this->getAssetByFilename($fileName, $imageId);
    }

    public function getAssetByFilename(?string $fileName, string $imageId): Scene7AssetInterface
    {
        $imageMiscParams = $this->getImageMiscParams($imageId);

        /** @var Scene7AssetInterface $asset */
        $asset = $this->assetFactory->create();
        $asset->setWidth($imageMiscParams['image_width']);
        $asset->setHeight($imageMiscParams['image_height']);
        $asset->setUrl($this->getImageUrl($fileName, $imageMiscParams));
        if ($fileName === null) {
            $asset->setIsPlaceHolder(true);
        }

        return $asset;
    }

    /**
     * @param ProductInterface $product
     * @return string[]
     */
    public function getProductAvailableImages(ProductInterface $product): array
    {
        $productGroup = $product->getAttributeText('product_group')
            ? $product->getAttributeText('product_group') : "Footwear";
        if (empty($productGroup)) {
            return [];
        }

        try {
            $availableAssets = $this->serializer->unserialize($product->getData('scene7_available_image_angles'));
        } catch (\InvalidArgumentException $e) {
            return [];
        }

        return $this->filterAvailableAssets($availableAssets, $productGroup);
    }

    /**
     * @param string $imageId
     * @return mixed[]
     */
    private function getImageMiscParams(string $imageId): array
    {
        if (isset($this->imageMiscParams[$imageId])) {
            return $this->imageMiscParams[$imageId];
        }

        $viewImageConfig = $this->presentationConfig->getViewConfig()->getMediaAttributes(
            'Magento_Catalog',
            ImageHelper::MEDIA_TYPE_CONFIG_NODE,
            $imageId
        );

        if (empty($viewImageConfig)) {
            $viewImageConfig = ['type' => $imageId];
        }

        $this->imageMiscParams[$imageId] = $this->imageParamsBuilder->build($viewImageConfig);

        return $this->imageMiscParams[$imageId];
    }

    /**
     * Returns image url based on filename and params
     *
     * @param string|null $fileName
     * @param string[] $params
     * @return string
     */
    private function getImageUrl(?string $fileName, array $params = []): string
    {
        if ($fileName === null) {
            return $this->imageHelper->getDefaultPlaceholderUrl('image');
        }

        return $fileName . $this->generateScene7ImageParams($params);
    }

    /**
     * Get Image Filename (asset name) stored in Scene7
     *
     * @param ProductInterface $product
     * @param string[] $params
     * @return string|null
     */
    private function getC7FileName(ProductInterface $product, array $params): ?string
    {
        $availableAssets = $this->getProductAvailableImages($product);

        if (empty($availableAssets)) {
            return null;
        }

        $productGroup = $product->getAttributeText('product_group')
            ? $product->getAttributeText('product_group') : "Footwear";

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $state = $objectManager->get('Magento\Framework\App\State');
        if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND) {
            if ($product->getStoreId() == 5) {
                $needles = ['1183C157.001', '1183C157.200'];
                $sku = $product->getSku();
                $matched = false;
                foreach ($needles as $needle) {
                    if (str_contains($sku, $needle)) {
                        $matched = true;
                        break;
                    }
                }
                if ($matched && isset($availableAssets["SB_Z4"])) {
                    return $availableAssets["SB_Z4"];
                }

                if (str_contains($product->getSku(), '1183C157.400') && isset($availableAssets["SB_Z1"])) {
                    return $availableAssets["SB_Z1"];
                }

                if (str_contains($product->getSku(), '1183C317') && isset($availableAssets["SB_TP"])) {
                    return $availableAssets["SB_TP"];
                }
            }
        }

        $filenameByRole = $this->getFilenameByRole($params['image_type'], $productGroup, $availableAssets);
        if ($filenameByRole !== null) {
            return $filenameByRole;
        }

        if (isset($params['return_default']) && $params['return_default']) {
            return array_shift($availableAssets);
        }

        return null;
    }

    /**
     * @param string[] $availableAssets
     * @param string $productGroup
     * @return string[]
     */
    private function filterAvailableAssets(array $availableAssets, string $productGroup): array
    {
        $mapping = $this->configProvider->getProductTypesMapping($productGroup);
        $result = [];
        foreach ($mapping as $mapItem) {
            $mapAssetAngle = $mapItem['asset_angle'];

            if (strpos($mapAssetAngle, '(N)') !== false) {
                $pattern = '/^' . str_replace('(N)', '[0-9]+', $mapAssetAngle) . '$/';
                foreach ($availableAssets as $assetAngle => $value) {
                    //phpcs:ignore: Vaimo.ControlStructures.NestedIf.Found
                    if (!preg_match($pattern, $assetAngle)) {
                        continue;
                    }

                    $result[$assetAngle] = $value;
                }
            } elseif (empty($availableAssets[$mapItem['asset_angle']])) {
                continue;
            } else {
                $result[$mapItem['asset_angle']] = $availableAssets[$mapItem['asset_angle']];
            }
        }

        return $result;
    }

    /**
     * @param string $imageRole
     * @param string $productGroup
     * @param string[] $availableAssets
     * @return string|null
     */
    private function getFilenameByRole(string $imageRole, string $productGroup, array $availableAssets): ?string
    {
        $rolesMapping = $this->configProvider->getAnglesMapping($productGroup);
        foreach ($rolesMapping as $mapItem) {
            if ($mapItem['role'] === $imageRole) {
                $angleForRole = $mapItem['angle'];
                break;
            }
        }

        if (!isset($angleForRole)) {
            return null;
        }

        foreach ($availableAssets as $typeAngle => $fileName) {
            $angle = explode('_', $typeAngle)[1] ?? null;
            if ($angle === $angleForRole) {
                return $fileName;
            }
        }

        return null;
    }

    /**
     * Generates GET params string, with image params for Scene 7.
     *
     * @param string[] $params
     * @return string
     */
    private function generateScene7ImageParams(array $params): string
    {
        $result = ['qlt=' . $params['quality']];

        if (isset($params['image_width']) && isset($params['image_height'])) {
            $result[] = 'wid=' . $params['image_width'];
            $result[] = 'hei=' . $params['image_height'];
        } else {
            $result[] = 'scl=1';
        }

        if (isset($params['background']) && \is_array($params['background'])) {
            $result[] = 'bgc=' . \implode(',', $params['background']);
        }

        if (!empty($params['angle'])) {
            $result[] = 'rotate=' . $params['angle'];
        }

        $resamplingMode = $this->configProvider->getResamplingMode();
        if ($resamplingMode !== null) {
            $result[] = 'resMode=' . $resamplingMode;
        }

        return '?' . \implode('&', $result);
    }
}
