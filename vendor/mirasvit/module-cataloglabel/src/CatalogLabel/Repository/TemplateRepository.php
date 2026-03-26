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
use Mirasvit\CatalogLabel\Api\Data\TemplateInterface;
use Mirasvit\CatalogLabel\Model\ResourceModel\Template\Collection;
use Mirasvit\CatalogLabel\Model\ResourceModel\Template\CollectionFactory;
use Mirasvit\CatalogLabel\Model\Template;
use Mirasvit\CatalogLabel\Model\TemplateFactory;

class TemplateRepository
{
    private $factory;

    private $collectionFactory;

    private $entityManager;

    public function __construct(
        TemplateFactory $factory,
        CollectionFactory $collectionFactory,
        EntityManager $entityManager
    ) {
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->entityManager     = $entityManager;
    }

    public function create(): TemplateInterface
    {
        return $this->factory->create();
    }

    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    public function get(int $id): ?TemplateInterface
    {
        $model = $this->create();

        $this->entityManager->load($model, $id);

        return $model->getId() ? $model : null;
    }

    public function getByCode(string $code): ?TemplateInterface
    {
        $model = $this->getCollection()
            ->addFieldToFilter(TemplateInterface::CODE, $code)
            ->getFirstItem();

        return $model && $model->getId() ? $model : null;
    }

    public function save(TemplateInterface $model): TemplateInterface
    {
        return $this->entityManager->save($model);
    }

    public function delete(TemplateInterface $model): bool
    {
        return $this->entityManager->delete($model);
    }
}
