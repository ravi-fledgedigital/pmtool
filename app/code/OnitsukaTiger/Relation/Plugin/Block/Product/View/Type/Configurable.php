<?php
declare(strict_types=1);

namespace OnitsukaTiger\Relation\Plugin\Block\Product\View\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableBlock;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

use OnitsukaTiger\Relation\Helper\Data as HelperRelation;

class Configurable
{
    private const PDP_CONTROLLER_ACTION_NAME = 'catalog_product_view';

    /**
     * @var array
     */
    public array $dataAlow;

    /**
     * @var ConfigurableModel
     */
    private ConfigurableModel $configurable;

    /**
     * @var HelperRelation
     */
    private HelperRelation $helperRelation;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    private SourceItemRepositoryInterface $sourceItemRepository;

    /**
     * @var Http
     */
    private Http $httpRequest;

    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $repoProduct;

    /**
     * @var \OnitsukaTiger\Catalog\Helper\Data
     */
    private $catalogHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $requestData;

    /**
     * @param Http $httpRequest
     * @param ConfigurableModel $configurable
     * @param Registry $coreRegistry
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param HelperRelation $helperRelation
     * @param OnitsukaTiger\Catalog\Helper\Data $catalogHelper
     * @param Magento\Framework\App\RequestInterface $requestData
     */
    public function __construct(
        Http                          $httpRequest,
        ConfigurableModel             $configurable,
        Registry                      $coreRegistry,
        SearchCriteriaBuilder         $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository,
        HelperRelation                $helperRelation,
        \Magento\Catalog\Model\ProductRepository $repoProduct,
        \OnitsukaTiger\Catalog\Helper\Data $catalogHelper,
        \Magento\Framework\App\RequestInterface $requestData
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->httpRequest = $httpRequest;
        $this->configurable = $configurable;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->dataAlow = [];
        $this->helperRelation = $helperRelation;
        $this->repoProduct = $repoProduct;
        $this->catalogHelper = $catalogHelper;
        $this->requestData = $requestData;
    }

    /**
     * Get Product Allow
     *
     * @param ConfigurableBlock $subject
     * @param callable $proceed
     * @return array|void
     */
    public function arrowGetAllowProducts(ConfigurableBlock $subject, callable $proceed)
    {
        if (!str_contains($subject->getRequest()->getPathInfo(), 'catalog/product/view/') && $this->requestData->getFullActionName() != 'quickview_catalog_product_view') {
            return [];
        }
    }

    /**
     * Add All Children from relation Product Configurable
     *
     * @param ConfigurableBlock $subject
     * @param Product[] $result
     * @return array
     */
    public function afterGetAllowProducts(ConfigurableBlock $subject, array $result): array
    {
        if (!$this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ENABLE)) {
            return $result;
        }

        $currentProduct = $subject->getProduct();
        if (!str_contains($subject->getRequest()->getPathInfo(), 'catalog/product/view/') && $this->requestData->getFullActionName() != 'quickview_catalog_product_view') {
            return $result;
        }
        if ($this->coreRegistry->registry('allow_product_children_lists')) {
            return $this->coreRegistry->registry('allow_product_children_lists');
        }
        $attributeRelations = $this->helperRelation->getAttributeRelation($currentProduct);
        if ($this->coreRegistry->registry('allow_product_configurable_list')) {
            $productConfigurable = $this->coreRegistry->registry('allow_product_configurable_list');
        } else {
            $productConfigurableList = $this->helperRelation->getProductsConfigurable(
                $attributeRelations
            );
            $productConfigurable = [];
            foreach ($productConfigurableList as $productList) {
                $productConfigurable[$productList->getEntityId()] = $productList;
            }
            ksort($productConfigurable);
            $this->coreRegistry->unregister('allow_product_configurable_list');
            $this->coreRegistry->register('allow_product_configurable_list', $productConfigurable);
        }

        $products = $productsTmp = [];

        foreach ($productConfigurable as $product) {
            if ($product->getStatus() == 1) {
                $registryKey = 'all_children_by_id' . $product->getEntityId();
                $registryKeyStock = 'all_children_stock_by_id' . $product->getEntityId();
                $collectionByIds = false;
                if ($this->coreRegistry->registry($registryKey)) {
                    $allChildrenIds = $this->coreRegistry->registry($registryKey);
                } else {
                    $allChildrenIds = $this->configurable->getChildrenIds($product->getEntityId());
                    $this->coreRegistry->unregister($registryKey);
                    $this->coreRegistry->register($registryKey, $allChildrenIds);
                }
                if ($this->coreRegistry->registry($registryKeyStock)) {
                    $collectionByIds = $this->coreRegistry->registry($registryKeyStock);
                } elseif (!empty($allChildrenIds[0])) {
                    $collectionByIds = $this->helperRelation->getChildrenProductByIds($allChildrenIds);
                    $this->coreRegistry->unregister($registryKeyStock);
                    $this->coreRegistry->register($registryKeyStock, $collectionByIds);
                }

                if ($collectionByIds && $collectionByIds->count() && !empty($allChildrenIds[0])) {
                    $allProductChildren = $this->helperRelation->getAllChildren($allChildrenIds);
                    foreach ($allProductChildren as $productChildren) {
                        if ($productChildren->getStatus() == 1) {
                            if (array_key_exists($productChildren->getEntityId(), $productsTmp)) {
                                continue;
                            }
                            $stockBySku = $this->getStockBySku([$productChildren->getSku()]);
                            if (!$stockBySku) {
                                continue;
                            }

                            if ((int)$productChildren->getStatus() === Status::STATUS_ENABLED) {
                                $productsTmp[$productChildren->getEntityId()] = $productChildren->getEntityId();
                                $products[] = $productChildren;
                            }
                        }
                    }
                }
            }
        }

        $this->coreRegistry->unregister('allow_product_children_lists');
        $this->coreRegistry->register('allow_product_children_lists', $products);
        return $products;
    }

    /**
     * Check Product Stock
     *
     * @param array $sku
     * @return array
     */
    private function getStockBySku(array $sku): array
    {
        $this->searchCriteriaBuilder->addFilter(SourceItemInterface::SKU, $sku, 'in');
        $sourceItems = $this->sourceItemRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        $stockBySku = [];
        foreach ($sourceItems as $sourceItem) {
            $stockBySku[$sourceItem->getSku()] = $sourceItem->getStatus();
        }

        return $stockBySku;
    }

    /**
     * Add new json data from Configurable
     *
     * @param ConfigurableBlock $subject
     * @param string $result
     * @return false|mixed|string|null
     */
    public function afterGetJsonConfig(ConfigurableBlock $subject, string $result): mixed
    {
        if (!$this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ENABLE)) {
            return $result;
        }

        if (!str_contains($subject->getRequest()->getPathInfo(), 'catalog/product/view/') && $this->requestData->getFullActionName() != 'quickview_catalog_product_view') {
            return $result;
        }

        $jsonRelation = $subject->getProduct()->getData('json_relation');

        $productRelationProduct = $productRelationProductColor = [];
        if ($jsonRelation) {
            $jsonArr  = json_decode($jsonRelation, true);
            foreach ($jsonArr as $index => $json) {
                $productRelationProduct[$json['product_sku']] = $json['swatches_image'];
                $productRelationProductColor[$json['product_sku']] = $json['swatches_color'];
            }
        }

        $currentProduct = $subject->getProduct();
        if ($this->coreRegistry->registry('allow_product_configurable_list')) {
            $productConfigurable = $this->coreRegistry->registry('allow_product_configurable_list');
        } else {
            $productConfigurableList = $this->helperRelation->getProductsConfigurable(
                $currentProduct->getData(
                    $this->helperRelation->getConfig(HelperRelation::XML_PATH_RELATION_ATTRIBUTES)
                )
            );
            $productConfigurable = [];
            foreach ($productConfigurableList as $productList) {
                $productConfigurable[$productList->getEntityId()] = $productList;
            }
            ksort($productConfigurable);
            $this->coreRegistry->unregister('allow_product_configurable_list');
            $this->coreRegistry->register('allow_product_configurable_list', $productConfigurable);
        }

        $result = json_decode($result, true);
        $result['styleCodes'] = $result['styleCodesParent'] = $result['relationSwatches'] = [];
        $configProductsStack = [];
        foreach ($subject->getAllowProducts() as $simpleProducts) {
            $result['styleCodes'][$simpleProducts->getEntityId()] = $simpleProducts->getData('style_code');
            if ($simpleProducts->getStatus() == 1) {
                $product = $this->configurable->getParentIdsByChild($simpleProducts->getEntityId());
                if (isset($product[0])) {
                    if(!empty($configProductsStack[$product[0]])) {
                        $productObj = $configProductsStack[$product[0]];
                    } else {
                        $productObj = $this->repoProduct->getById($product[0]);
                        $configProductsStack[$product[0]] = $productObj;
                    }
                    if ($productObj->getStatus() == 1) {
                        $result['styleCodesParent'][$simpleProducts->getEntityId()] = $productObj->getSku();

                        if (!empty($productRelationProduct) && isset($productRelationProduct[$productObj->getSku()])) {
                            $result['relationSwatches'][$simpleProducts->getEntityId()] = $productRelationProduct[$productObj->getSku()];
                        }

                        if (!empty($productRelationProductColor) && isset($productRelationProductColor[$productObj->getSku()])) {
                            $result['relationProductColor'][$simpleProducts->getId()] = $productRelationProductColor[$productObj->getSku()];
                        }
                    }
                }
            }
        }

        $urls = $relatedParents = $relatedParentsSku = $prImage = [];

        foreach ($productConfigurable as $product) {
            if ($product->getStatus() == 1) {
                $registryKeyStock = 'all_children_stock_by_id' . $product->getEntityId();
                $registryKey = 'all_children_by_id' . $product->getEntityId();
                $urlParentProduct = $product->getUrlModel()->getUrl($product);
                $relatedParents[$product->getSku()] = $product->getEntityId();
                $relatedParentsSku[$product->getEntityId()] = $product->getSku();
                $collectionByIds = false;

                if ($this->coreRegistry->registry($registryKeyStock)) {
                    $collectionByIds = $this->coreRegistry->registry($registryKeyStock);
                } else {
                    if ($this->coreRegistry->registry($registryKey)) {
                        $allChildrenIds = $this->coreRegistry->registry($registryKey);
                    } else {
                        $allChildrenIds = $this->configurable->getChildrenIds($product->getEntityId());
                        $this->coreRegistry->unregister($registryKey);
                        if (!empty($allChildrenIds[0])) {
                            $this->coreRegistry->register($registryKey, $allChildrenIds);
                        }
                    }

                    if (!empty($allChildrenIds[0])) {
                        $collectionByIds = $this->helperRelation->getChildrenProductByIds(
                            $allChildrenIds
                        );
                    }

                    $this->coreRegistry->unregister($registryKeyStock);
                    if (!empty($allChildrenIds[0]) && $collectionByIds && $collectionByIds->getSize()) {
                        $this->coreRegistry->register($registryKeyStock, $collectionByIds);
                    }
                }

                if ($collectionByIds && $collectionByIds->getSize()) {
                    foreach ($collectionByIds as $productChildren) {
                        $urls[$product->getEntityId()][$productChildren->getEntityId()] = $urlParentProduct;
                    }
                }
            }
        }

        $result['currentSku'] = $currentProduct->getSku();
        $result['currentId'] = $currentProduct->getEntityId();
        $result['isPdpRelation'] = (($this->httpRequest->getFullActionName() === self::PDP_CONTROLLER_ACTION_NAME) ? true : false);
        $result['productUrl'] = $urls;
        $result['relationParents'] = $relatedParents;
        $result['relatedParentsSku'] = $relatedParentsSku;

        return json_encode($result);
    }
}