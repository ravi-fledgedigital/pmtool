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
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Model\PlaceholderFactory;
use Mirasvit\CatalogLabel\Model\ResourceModel\Placeholder\Collection;
use Mirasvit\CatalogLabel\Model\ResourceModel\Placeholder\CollectionFactory;

class PlaceholderRepository
{
    private $factory;

    private $collectionFactory;

    private $entityManager;

    /** @var PlaceholderInterface[]|null */
    private $positionedPlaceholder = null;

    /** @var PlaceholderInterface[]|null */
    private $placeholdersByCode = null;

    public function __construct(
        PlaceholderFactory $factory,
        CollectionFactory $collectionFactory,
        EntityManager $entityManager
    ) {
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->entityManager     = $entityManager;
    }

    public function create(): PlaceholderInterface
    {
        return $this->factory->create();
    }

    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    public function get(int $id): ?PlaceholderInterface
    {
        $model = $this->create();

        $this->entityManager->load($model, $id);

        return $model->getId() ? $model : null;
    }

    public function getByCode(string $code): ?PlaceholderInterface
    {
        if (!isset($this->placeholdersByCode[$code])) {
            $model = $this->getCollection()
                ->addFieldToFilter(PlaceholderInterface::CODE, $code)
                ->getFirstItem();

            $this->placeholdersByCode[$code] = $model && $model->getId() ? $model : null;
        }

        return $this->placeholdersByCode[$code];
    }

    public function save(PlaceholderInterface $model): PlaceholderInterface
    {
        return $this->entityManager->save($model);
    }

    public function delete(PlaceholderInterface $model): bool
    {
        return $this->entityManager->delete($model);
    }

    public function getPositionedItems(): ?array
    {
        if (!$this->positionedPlaceholder) {
            $placeholders = $this->getCollection()
                ->addFieldToFilter(PlaceholderInterface::IS_ACTIVE, true)
                ->addFieldToFilter(PlaceholderInterface::POSITION, ['neq' => 'MANUAL'])
                ->getItems();

            $this->positionedPlaceholder = $placeholders && count($placeholders) ? $placeholders : null;
        }

        return $this->positionedPlaceholder;
    }
}
