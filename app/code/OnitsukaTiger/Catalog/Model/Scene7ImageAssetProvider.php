<?php
/**
 * Scene7ImageAssetProvider
 */

namespace OnitsukaTiger\Catalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\ConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Vaimo\OTScene7Integration\Api\Data\Scene7AssetInterfaceFactory;
use Vaimo\OTScene7Integration\Model\ConfigProvider;

class Scene7ImageAssetProvider extends \Vaimo\OTScene7Integration\Model\Scene7ImageAssetProvider
{
    protected $storeManager;

    protected $productIds = [];
    private Http $request;

    public function __construct(
        ConfigProvider              $configProvider,
        Scene7AssetInterfaceFactory $assetFactory,
        ConfigInterface             $presentationConfig,
        ParamsBuilder               $imageParamsBuilder,
        SerializerInterface         $serializer,
        ImageHelper                 $imageHelper,
        StoreManagerInterface $storeManager,
        Http $request
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($configProvider, $assetFactory, $presentationConfig, $imageParamsBuilder, $serializer, $imageHelper);
        $this->request = $request;
    }

    public function getProductAvailableImages(ProductInterface $product): array
    {
        $availableImages = parent::getProductAvailableImages($product);
        $storeId = $this->storeManager->getStore()->getId();
        if ($storeId == 5) {
            $i = 1;

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $newProduct = $objectManager->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')
                ->getParentIdsByChild($product->getId());
            if ($newProduct) {
                if (array_key_exists($newProduct[0], $this->productIds)) {
                    $additionalImages =  $this->productIds[$newProduct[0]];
                } else {
                    $newProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($newProduct[0]);
                    if ($newProduct && $newProduct->getId()) {
                        $this->productIds[$newProduct->getId()] = $newProduct->getAdditionalImages();
                        $additionalImages =  $newProduct->getAdditionalImages();
                    }
                }
            } else {
                $additionalImages = $product->getAdditionalImages();
            }

            if (!empty($additionalImages)) {
                $additionalImages = explode(",", $additionalImages);
                if (!empty($additionalImages)) {
                    foreach ($additionalImages as $image) {
                        $availableImages["CUSTOM_IMAGE$i"] = $image;
                        $i++;
                    }
                }
            }
            if (str_contains($product->getSku(), '1183C317') && isset($availableImages["SB_TP"])) {
                $availableImages = $this->changeToFirstPositionPDP($availableImages);
            }
            if (in_array($product->getSku(), ['1182A676.200', '1182A676.100', '1182A676.001']) && isset($availableImages["SB_FR"]) && isset($availableImages["SB_Z1"]) && isset($availableImages["SB_Z2"])) {
                $availableImages = $this->moveArrayElementPostion($availableImages);
            }
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $state = $objectManager->get('Magento\Framework\App\State');
            if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND) {
                $fullActionName = $this->request->getFullActionName();
                if ($fullActionName != 'catalog_product_view') {
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
                        $availableImages = $this->changePosition($availableImages);
                    }
                    if (str_contains($product->getSku(), '1183C157.400') && isset($availableAssets["SB_Z1"])) {
                        $availableImages = $this->moveArrayElementFirstPostion($availableImages);
                    }
                    if (str_contains($product->getSku(), '1183C317') && isset($availableAssets["SB_TP"])) {
                        $availableImages = $this->moveArrayElementToFirstPostion($availableImages);
                    }
                }
            }
        }
        return $availableImages;
    }

    private function moveArrayElementPostion($availableImages)
    {
        $keyToMoveFirst = 'SB_FR';
        $keyToMoveSecond = 'SB_Z1';
        $keyToMoveThird = 'SB_Z2';
        $elementToMove = [$keyToMoveFirst => $availableImages[$keyToMoveFirst], $keyToMoveSecond => $availableImages[$keyToMoveSecond], $keyToMoveThird => $availableImages[$keyToMoveThird]]; // Store the key-value pair

        unset($availableImages[$keyToMoveFirst]);
        unset($availableImages[$keyToMoveSecond]);
        unset($availableImages[$keyToMoveThird]);

        $newArray = [];
        $targetPosition = 1;
        $counter = 0;

        foreach ($availableImages as $key => $value) {
            if ($counter === $targetPosition) {
                $newArray = array_merge($newArray, $elementToMove);
            }
            $newArray[$key] = $value;
            $counter++;
        }

        if ($targetPosition >= count($availableImages)) {
            $newArray = array_merge($newArray, $elementToMove);
        }

        return $newArray;
    }

    private function changePosition($availableImages)
    {
        $sb_z4 = $availableImages['SB_Z4'];
        unset($availableImages['SB_Z4']);

        return ['SB_Z4' => $sb_z4] + $availableImages;
    }

    private function moveArrayElementFirstPostion($availableImages)
    {
        $sb_z1 = $availableImages['SB_Z1'];
        unset($availableImages['SB_Z1']);

        return ['SB_Z1' => $sb_z1] + $availableImages;
    }

    private function moveArrayElementToFirstPostion($availableImages)
    {
        $sb_tp = $availableImages['SB_TP'];
        unset($availableImages['SB_TP']);

        return ['SB_TP' => $sb_tp] + $availableImages;
    }


    private function changeToFirstPositionPDP($availableImages)
    {
        $sbtp = $availableImages['SB_TP'];
        unset($availableImages['SB_TP']);

        return ['SB_TP' => $sbtp] + $availableImages;
    }
}
