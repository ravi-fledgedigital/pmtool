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

namespace Mirasvit\CatalogLabel\Repository;

use Magento\Framework\EntityManager\EntityManager;
use Mirasvit\CatalogLabel\Api\Data\NewProductsInterface;
use Mirasvit\CatalogLabel\Api\Data\NewProductsInterfaceFactory;
use Mirasvit\CatalogLabel\Model\ResourceModel\NewProducts\CollectionFactory;
use Mirasvit\CatalogLabel\Model\ResourceModel\NewProducts\Collection as NewProductsCollection;

class NewProductsRepository
{
    private $factory;

    private $collectionFactory;

    private $entityManager;

    public function __construct(
        NewProductsInterfaceFactory $factory,
        CollectionFactory $collectionFactory,
        EntityManager $entityManager
    ) {
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->entityManager     = $entityManager;
    }

    public function create(): NewProductsInterface
    {
        return $this->factory->create();
    }

    public function getCollection(): NewProductsCollection
    {
        return $this->collectionFactory->create();
    }

    public function get(int $id): ?NewProductsInterface
    {
        $model = $this->create();

        $this->entityManager->load($model, $id);

        return $model->getId() ? $model : null;
    }

    public function save(NewProductsInterface $newProducts): NewProductsInterface
    {
        return $this->entityManager->save($newProducts);
    }

    public function delete(NewProductsInterface $newProducts): bool
    {
        return $this->entityManager->delete($newProducts);
    }
}
