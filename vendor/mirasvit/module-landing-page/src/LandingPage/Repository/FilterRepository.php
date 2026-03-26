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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Repository;

use Mirasvit\LandingPage\Api\Data\FilterInterface;
use Mirasvit\LandingPage\Model\FilterFactory;
use Mirasvit\LandingPage\Model\ResourceModel\Filter\Collection;
use Mirasvit\LandingPage\Model\ResourceModel\Filter\CollectionFactory;

class FilterRepository
{
    private $filterFactory;

    private $collectionFactory;

    public function __construct(
        FilterFactory     $filterFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->filterFactory     = $filterFactory;
    }

    public function create(): FilterInterface
    {
        return $this->filterFactory->create();
    }

    public function getByPageId(int $pageId): Collection
    {
        $collection = $this->getCollection();

        $collection->addFieldToFilter(FilterInterface::PAGE_ID, $pageId);

        return $collection;

    }

    public function get(int $id): ?FilterInterface
    {
        $model = $this->create();

        $model->load($id);

        return $model->getId() ? $model : null;
    }

    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    public function save(FilterInterface $model): FilterInterface
    {
        $model->save();

        return $model;
    }

    public function delete(FilterInterface $model)
    {
        $model->delete();
    }

}
