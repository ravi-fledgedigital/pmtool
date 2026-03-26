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
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Model\Label\DisplayFactory;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Display\Collection;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Display\CollectionFactory;

class DisplayRepository
{
    /** key format: [placeholder_id]_[type]_[display_id] */
    const KEY_PATTERN = '%s_%s_%s';

    private $factory;

    private $collectionFactory;

    private $entityManager;

    private $loaded = [];

    public function __construct(
        DisplayFactory $factory,
        CollectionFactory $collectionFactory,
        EntityManager $entityManager
    ) {
        $this->entityManager     = $entityManager;
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
    }

    public function create(): DisplayInterface
    {
        return $this->factory->create();
    }

    public function get(int $id): ?DisplayInterface
    {
        $model = $this->create();

        $this->entityManager->load($model, $id);

        return $model->getId() ? $model : null;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getByData(array $data = [], int $limit = 0): array
    {
        if (!count($this->loaded)) {
            $this->loadDisplays();
        }

        if (!count($data) && !$limit) {
            return $this->loaded;
        }

        $allowedKeys = [
            DisplayInterface::LABEL_ID,
            DisplayInterface::TYPE,
            DisplayInterface::PLACEHOLDER_ID,
            DisplayInterface::ATTR_OPTION_ID,
            DisplayInterface::ID
        ];

        if (
            count($data) == 3
            && isset($data[DisplayInterface::PLACEHOLDER_ID])
            && isset($data[DisplayInterface::TYPE])
            && isset($data[DisplayInterface::ID])
        ) {
            $displayIds = $data[DisplayInterface::ID];

            if (!is_array($displayIds)) {
                $displayIds = explode(',', $displayIds);
            }

            $displayIds = array_filter($displayIds);

            if (count($displayIds)) {
                return $this->getFromLoaded(
                    (int)$data[DisplayInterface::PLACEHOLDER_ID],
                    $data[DisplayInterface::TYPE],
                    $data[DisplayInterface::ID],
                    (int)$limit
                );
            }
        }

        $displays = $this->getCollection();

        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) {
                $condition = is_array($data[$key]) ? ['in' => $data[$key]] : ['eq' => $data[$key]];

                $displays->addFieldToFilter($key, $condition);
            }
        }

        if ($limit) {
            $displays->getSelect()->limit($limit);
        }

        return $displays->getItems();
    }

    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    public function save(DisplayInterface $model): DisplayInterface
    {
        $model = $this->entityManager->save($model);

        if ($model->getStyle()) {
            $processor = new \Less_Parser;
            $processor->parse($model->getStyle());
        }

        return $model;
    }

    public function delete(DisplayInterface $model): bool
    {
        return $this->entityManager->delete($model);
    }

    private function loadDisplays(): void
    {
        foreach ($this->getCollection() as $item) {
            $key = $this->getKey(
                (int)$item->getPlaceholderId(),
                $item->getType(),
                (int)$item->getId()
            );

            $this->loaded[$key] = $item;
        }
    }

    private function getKey(int $placeholderId, string $type, int $id): string
    {
        return sprintf(self::KEY_PATTERN, $placeholderId, $type, $id);
    }

    private function getFromLoaded(int $placeholderId, string $type, array $displayIds, int $limit): array
    {
        $result = [];
        $limit = $limit ?: count($this->loaded);

        foreach ($displayIds as $id) {
            $key = $this->getKey((int)$placeholderId, $type, (int)$id);
            $key2 = $this->getKey((int)$placeholderId, 'both', (int)$id);

            if (isset($this->loaded[$key])) {
                $result[] = $this->loaded[$key];
                $limit--;
            } elseif (isset($this->loaded[$key2])) {
                $result[] = $this->loaded[$key2];
                $limit--;
            }

            if ($limit <= 0) {
                break;
            }
        }

        return $result;
    }
}
