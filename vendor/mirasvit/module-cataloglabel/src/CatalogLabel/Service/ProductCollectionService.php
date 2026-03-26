<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Service;


use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatusSource;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Mirasvit\CatalogLabel\Model\ConfigProvider;

class ProductCollectionService
{
    private $productCollectionFactory;

    private $productStatusSource;

    private $productVisibility;

    private $configProvider;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductStatusSource $productStatusSource,
        ProductVisibility $productVisibility,
        ConfigProvider $configProvider
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productStatusSource      = $productStatusSource;
        $this->productVisibility        = $productVisibility;
        $this->configProvider           = $configProvider;
    }

    public function getCollection(int $storeId, ?array $productIds = [], ?string $attributeCode = null): Collection
    {
        $productCollection = $this->productCollectionFactory->create()
            ->addStoreFilter($storeId)
            ->addAttributeToFilter('status', ['in' => $this->productStatusSource->getVisibleStatusIds()]);

        if (!empty($productIds)) {
            $productCollection->addFieldTofilter('entity_id', ['in' => $productIds]);
        }

        if ($attributeCode) {
            $productCollection->addAttributeToFilter($attributeCode, ['notnull' => true]);
        }

        if (!$this->configProvider->isApplyForChild() && !$this->configProvider->isApplyForParent()) {
            $productCollection->setVisibility($this->productVisibility->getVisibleInSiteIds());
        }

        return $productCollection;
    }
}
