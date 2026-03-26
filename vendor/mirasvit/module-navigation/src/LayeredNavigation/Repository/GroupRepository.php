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


namespace Mirasvit\LayeredNavigation\Repository;


use Magento\Framework\EntityManager\EntityManager;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterfaceFactory;
use Mirasvit\LayeredNavigation\Model\ResourceModel\Group\Collection;
use Mirasvit\LayeredNavigation\Model\ResourceModel\Group\CollectionFactory;

class GroupRepository
{
    private $factory;

    private $collectionFactory;

    private $entityManager;

    private $groupsListByCode = [];

    private $groupsListByAttributeCode = [];

    private $attributesList = [];

    public function __construct(
        GroupInterfaceFactory $factory,
        CollectionFactory $collectionFactory,
        EntityManager $entityManager
    ) {
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->entityManager     = $entityManager;
    }

    public function create(): GroupInterface
    {
        return $this->factory->create();
    }

    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    /**
     * @return GroupInterface[]
     */
    public function getGroupsListByAttributeCode(string $attributeCode): array
    {
        if (isset($this->groupsListByAttributeCode[$attributeCode])) {
            return $this->groupsListByAttributeCode[$attributeCode];
        }

        $collection = $this->getCollection()
            ->addFieldToFilter(GroupInterface::IS_ACTIVE, true)
            ->addFieldToFilter(GroupInterface::ATTRIBUTE_CODE, $attributeCode);

        $collection->getSelect()->order(GroupInterface::POSITION);

        $this->groupsListByAttributeCode[$attributeCode] = $collection->getItems();

        return $this->groupsListByAttributeCode[$attributeCode];
    }

    public function get(int $id): ?GroupInterface
    {
        $model = $this->create();

        $this->entityManager->load($model, $id);

        return $model->getId() ? $model : null;
    }

    public function getByCode(string $code): ?GroupInterface
    {
        if (isset($this->groupsListByCode[$code])) {
            return $this->groupsListByCode[$code];
        }

        $group = $this->getCollection()->addFieldToFilter(GroupInterface::CODE, $code)->getFirstItem();

        $groupList = $group->getId() ? $group : null;

        $this->groupsListByCode[$code] = $groupList;

        return $groupList;
    }

    public function save(GroupInterface $model): GroupInterface
    {
        return $this->entityManager->save($model);
    }

    public function delete(GroupInterface $model): void
    {
        $this->entityManager->delete($model);
    }

    public function getAttributesList(): array
    {
        if (count($this->attributesList)) {
            return $this->attributesList;
        }

        $collection = $this->getCollection()
            ->addFieldToSelect(GroupInterface::ATTRIBUTE_CODE)
            ->addFieldToFilter(GroupInterface::IS_ACTIVE, true);

        $this->attributesList = $collection->getColumnValues(GroupInterface::ATTRIBUTE_CODE);

        return $this->attributesList;
    }
}
