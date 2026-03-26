<?php
// phpcs:ignoreFile
namespace OnitsukaTiger\Catalog\Model\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class OtherColorOptionResolver implements ResolverInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var \Onitsukatiger\AdobeScene7\Helper\AdobeSceneHelper\Data
     */
    protected $helper;
    /**
     * @var Image
     */
    protected $imageHelper;
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $productRepositoryInterface;

    /**
     * OtherColorOptionResolver constructor.
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * //* @param \Onitsukatiger\AdobeScene7\Helper\AdobeSceneHelperData $helper
     * @param Image $imageHelper
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        AttributeRepositoryInterface                                   $attributeRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        //\Onitsukatiger\AdobeScene7\Helper\AdobeSceneHelperData $helper,
        Image                                                          $imageHelper,
        \Magento\Store\Model\App\Emulation                             $appEmulation,
        \Magento\Store\Model\StoreManagerInterface                     $storeManagerInterface,
        ProductRepositoryInterface                                     $productRepositoryInterface
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        //$this->helper = $helper;
        $this->imageHelper = $imageHelper;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManagerInterface;
        $this->productRepositoryInterface = $productRepositoryInterface;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field       $field,
                    $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        $styleCode = $args['styleCode'];
        $sku = $args['productSku'];
        $storeId = $args['storeId'];

        $productColorDetails = $this->getAssociateProduct($styleCode, $sku, $storeId);

        return [
            'othercolordetail' => $productColorDetails
        ];
    }

    /**
     * Get color attribute id value
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getColorAttributeId()
    {
        $attribute = $this->attributeRepository->get(Product::ENTITY, 'color_code');
        return $attribute->getAttributeId();
    }

    /**
     * Get associate product html
     *
     * @param mixed $partNo
     * @param mixed $sku
     * @param integer $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * phpcs:disable
     */
    public function getAssociateProduct($styleCode, $sku, $storeId)
    {
        $colorHtml = "";

        $collection = $this->productCollectionFactory->create()->addStoreFilter($storeId)->addAttributeToFilter('style_code', $styleCode)->addAttributeToFilter('type_id', 'configurable')->addAttributeToSelect('json_relation')->addAttributeToFilter('status', Status::STATUS_ENABLED)->addAttributeToFilter('visibility', Visibility::VISIBILITY_BOTH);

        if ($collection) {
            $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
            $colorAttrId = $this->getColorAttributeId();
            $i = 0;
            foreach ($collection as $product) {
                $parentCurlResult = '';//$this->helper->checkInAdobeScene7($product->getSmallImage());

                try {
                    $productId = $product->getId();

                    if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {

                        /*$_children = $product->getTypeInstance()->getUsedProducts($product);*/
                        $_children = $product->getTypeInstance()->getUsedProductCollection($product)->addStoreFilter($storeId)
                            ->addAttributeToSelect(['color','restock_notification_flag']);

                        $colorArr = [];
                        $productSku = $product->getSku();

                        $jsonRelation = $product->getData('json_relation');
                        $jsonRelation = json_decode($jsonRelation, true);

                        if ($_children) {
                            foreach ($_children as $child) {
                                if ($child->getStatus() == 2) {
                                    continue;
                                }

                                $imageUrl = "";

                                /*$curlResult = $this->helper->checkInAdobeScene7($child->getData('small_image'));
                                if (isset($curlResult['result']) && !empty($curlResult['result'])) {
                                    $imageUrl = $this->helper->generateUrl($curlResult, 'product_swatch_image_small');
                                } elseif (isset($parentCurlResult['result']) && !empty($parentCurlResult['result'])) {
                                    $imageUrl = $this->helper->generateUrl($parentCurlResult, 'product_swatch_image_small');
                                } else {
                                    $imageUrl = $this->imageHelper->getDefaultPlaceholderUrl('swatch_image');
                                }*/
                                $imageUrl = '';

                                $colorLabel = "";
                                if ($child->getResource()->getAttribute('color_code')) {
                                    $colorLabel = $child->getResource()->getAttribute('color_code')->getFrontend()->getValue($child);
                                }

                                $colorOptionId = $child->getColorCode();
                                $colorName = $child->getAttributeText('color');
                                /*echo $colorName;exit;*/
                                if (!in_array($colorOptionId, $colorArr)) {
                                    $swatchesImage = '';
                                    foreach ($jsonRelation as $json) {
                                        if ($productSku == $json['product_sku']) {
                                            $swatchesImage = $json['swatches_image'];
                                            break;
                                        }
                                    }

                                    $optionClass = '';
                                    if ($product->getSku() == $sku) {
                                        $optionClass = ' selected';
                                    }
                                    $productStatus = $this->checkProductStatus($_children);
                                    $notifyMe = $productStatus['notifyMe'];
                                    $outOfStock = $productStatus['outOfStock'];
                                    $colorHtml .= "<div data-id='$productId' data-child-products-notify-me='$notifyMe' data-child-products-stock-status='$outOfStock' class='swatch-option image other-color-options$optionClass' id='option-label-color_code-$colorAttrId-item-$colorOptionId' index='$i' aria-checked='false' aria-describedby='option-label-color_code-$colorAttrId' tabindex='0' data-option-type='2' data-option-id='$colorOptionId' data-option-label='$colorLabel' data-option-color-label='$colorName' aria-label='$colorLabel' role='option' data-thumb-width='110' data-size-product-id='' data-size-option-id='' data-size-stock-status-id='' data-attribute-code='color_code' data-thumb-height='90' data-out-of-stock-product-id='' data-option-style='$productSku' data-option-tooltip-thumb='$swatchesImage' data-option-tooltip-value='$swatchesImage' style='background: url($swatchesImage) no-repeat center; background-size: initial;width:105px; height:78px'></div>";
                                    $i++;
                                    array_push($colorArr, $colorOptionId);
                                }
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            $this->appEmulation->stopEnvironmentEmulation();
        }
        return $colorHtml;
    }

    /**
     * @param $_children
     * @return int|int[]
     */
    public function checkProductStatus($_children)
    {
        $isProductOutOfStock = 0;
        $isProductNotifyMe = 0;
        if ($_children) {
            $childOutOfStock = 0;
            $childNotify = 0;
            $noOfChildProducts = 0;
            foreach ($_children->getItems() as $childProduct) {
                if (!$childProduct->getIsSalable()) {
                    $childOutOfStock++;
                }
                if (!$childProduct->getIsSalable() && $childProduct->getRestockNotificationFlag() == 2) {
                    $childNotify++;
                }
                $noOfChildProducts++;
            }
            if ($childOutOfStock > 0 && $noOfChildProducts == $childOutOfStock) {
                $isProductOutOfStock = 1;
            }
            if ($childNotify > 0 && $noOfChildProducts == $childNotify) {
                $isProductNotifyMe = 1;
            }
        }
        return [
            'outOfStock' => $isProductOutOfStock,
            'notifyMe' => $isProductNotifyMe
        ];
    }
    /** phpcs:enable */
}
