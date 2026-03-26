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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Brand\Repository;

use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\EntityManager\EntityManager;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Api\Data\BrandPageInterfaceFactory;
use Mirasvit\Brand\Model\Config\GeneralConfig;
use Mirasvit\Brand\Model\ResourceModel\BrandPage\Collection;
use Mirasvit\Brand\Model\ResourceModel\BrandPage\CollectionFactory;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\App\ResourceConnection;
use Mirasvit\Brand\Api\Data\BrandPageStoreInterface;

class BrandPageRepository
{
    private $factory;

    private $collectionFactory;

    private $entityManager;

    private $productAction;

    private $config;

    private $productCollectionFactory;

    private $filter;

    private $resourceConnection;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductAction $productAction,
        GeneralConfig $config,
        BrandPageInterfaceFactory $factory,
        CollectionFactory $collectionFactory,
        EntityManager $entityManager,
        FilterManager $filter,
        ResourceConnection $resourceConnection
    ) {
        $this->productAction            = $productAction;
        $this->config                   = $config;
        $this->factory                  = $factory;
        $this->collectionFactory        = $collectionFactory;
        $this->entityManager            = $entityManager;
        $this->filter                   = $filter;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resourceConnection       = $resourceConnection;
    }

    public function create(): BrandPageInterface
    {
        return $this->factory->create();
    }

    /** @return Collection|BrandPageInterface[] */
    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    public function get(int $id): ?BrandPageInterface
    {
        $model = $this->create();

        $this->entityManager->load($model, $id);

        return $model->getId() ? $model : null;
    }

    public function save(BrandPageInterface $brandPage): BrandPageInterface
    { 
        $storeId = (int)($brandPage->getData(BrandPageInterface::STORE_ID) ?? 0);

        if ($storeId === 0 && $brandUrlKey = $brandPage->getData(BrandPageInterface::URL_KEY)) {
            $brandPage->setData(BrandPageInterface::URL_KEY, $this->filter->translitUrl($brandUrlKey));
        }

        if ($storeId === 0 && $brandPage->getData('products')) {
            $this->updateProductsBrand($brandPage);
        }
        if ($storeId === 0) {
            $preservedFields = $this->preserveSpecificStoreFields($brandPage);
            $brandPage = $this->entityManager->save($brandPage);
            $this->restoreSpecificStoreFields($brandPage, $preservedFields);
        } else {
            $brandPage->setPreventStoreReload(true);
            $brandPage->moveBannerFromTmp();
        }
        
        $this->saveStoreViewRow($brandPage);
        return $brandPage;
    }

    private function saveStoreViewRow(BrandPageInterface $brandPage): void
    {
        $storeId = (int)$brandPage->getData('storeId');
        
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(BrandPageStoreInterface::TABLE_NAME);

        $data = [
            BrandPageInterface::ID            => (int)$brandPage->getId(),
            BrandPageStoreInterface::STORE_ID => $storeId,
        ];

        foreach ($brandPage->getStoreFields() as $field) {
            $data[$field] = $brandPage->getData($field) ?? null;
        }

        $select = $connection->select()
            ->from($table)
            ->where('brand_page_id = ?', $data[BrandPageInterface::ID])
            ->where('store_id = ?', $storeId);

        $exists = $connection->fetchOne($select);

        if ($exists) {
            $connection->update(
                $table,
                $data,
                [
                    'brand_page_id = ?' => $data[BrandPageInterface::ID],
                    'store_id = ?'      => $storeId,
                ]
            );
        } else {
            $connection->insert($table, $data);
        }
    }

    public function delete(BrandPageInterface $brandPage): void
    {
        $this->entityManager->delete($brandPage);
    }

    private function updateProductsBrand(BrandPageInterface $brandPage): void
    {
        $attributeCode = $this->config->getBrandAttribute();
        $ids           = $brandPage->getData('products');
        $brandId       = $brandPage->getAttributeOptionId();

        // Set brand
        $this->productAction->updateAttributes(
            $ids,
            [$attributeCode => $brandId],
            0
        );

        // Unset brand
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToFilter($attributeCode, ['eq' => $brandId])
            ->addFieldToFilter('entity_id', ['nin' => $ids]);

        $idsToUnset = [];

        foreach ($collection as $item) {
            $idsToUnset[] = $item->getId();
        }

        if (count($idsToUnset)) {
            $this->productAction->updateAttributes(
                $idsToUnset,
                [$attributeCode => ''],
                0
            );
        }
    }

    public function getByOptionId(int $optionId): ?BrandPageInterface
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter(BrandPageInterface::ATTRIBUTE_OPTION_ID, $optionId);

        $brandPage = $collection->getFirstItem();

        if ($brandPage && $brandPage->getId()) {
            $brandPage->setData(BrandPageInterface::ATTRIBUTE_OPTION_ID, $optionId);
            return $brandPage;
        }

        return null;
    }

    private function preserveSpecificStoreFields(BrandPageInterface $brandPage): array
    {
        $fieldsToPreserve = [
            BrandPageStoreInterface::BRAND_CMS_BLOCK,
            BrandPageStoreInterface::BRAND_DISPLAY_MODE,
        ];

        $preserved = [];

        foreach ($fieldsToPreserve as $field) {
            if ($brandPage->getData($field) !== null) {
                $preserved[$field] = $brandPage->getData($field);
            }
        }

        return $preserved;
    }

    private function restoreSpecificStoreFields(BrandPageInterface $brandPage, array $fields): void
    {
        foreach ($fields as $key => $value) {
            $brandPage->setData($key, $value);
        }
    }
}
