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

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Indexer\IndexerRegistry;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Magento\Store\Model\Store;
use Mirasvit\LandingPage\Model\Indexer\LandingPageProduct;
use Mirasvit\LandingPage\Model\PageFactory;
use Mirasvit\LandingPage\Api\Data\PageStoreInterfaceFactory;
use Mirasvit\LandingPage\Model\ResourceModel\Page\Collection;
use Mirasvit\LandingPage\Model\ResourceModel\Page\CollectionFactory;
use Mirasvit\LandingPage\Model\ResourceModel\Page\Store as PageStoreResource;

class PageRepository
{
    private $pageFactory;

    private $collectionFactory;

    private $pageStoreFactory;

    private $pageStoreResource;

    private $indexerRegistry;

    public function __construct(
        CollectionFactory         $collectionFactory,
        PageFactory               $pageFactory,
        PageStoreInterfaceFactory $pageStoreFactory,
        PageStoreResource         $pageStoreResource,
        IndexerRegistry           $indexerRegistry
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->pageFactory       = $pageFactory;
        $this->pageStoreFactory  = $pageStoreFactory;
        $this->pageStoreResource = $pageStoreResource;
        $this->indexerRegistry   = $indexerRegistry;
    }

    public function create(): PageInterface
    {
        return $this->pageFactory->create();
    }

    public function get(int $id, ?int $storeId = null): ?PageInterface
    {
        $model = $this->create();

        $model->load($id);

        if ((null !== $storeId) && (Store::DEFAULT_STORE_ID !== $storeId)) {
            $pageStore = $this->pageStoreFactory->create();
            $this->pageStoreResource->loadByPageAndStore($pageStore, $id, $storeId);
            if (null !== $pageStore) {
                $useDefault = [];
                foreach (PageInterface::STORE_FIELDS as $field) {
                    $value = $pageStore->getData($field);
                    if ($value === null) {
                        $useDefault[$field] = true;
                        continue;
                    }

                    $model->setData($field, $value);
                }

                if (count($useDefault) > 0) {
                    $model->setUseDefault($useDefault);
                }
            }
        }

        return $model->getId() ? $model : null;
    }

    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    public function save(PageInterface $model): PageInterface
    {
        $storeId = intval($model->getStoreId());

        if ((null !== $storeId) && (Store::DEFAULT_STORE_ID !== $storeId)) {
            try {
                $page = $this->savePageStore($model);
            } catch (Exception $exception) {
                throw new CouldNotSaveException(__($exception->getMessage()));
            }

            $this->reindexPage((int)$page->getPageId());

            return $page;
        }

        $model->save();

        $this->reindexPage((int)$model->getPageId());

        return $model;
    }

    public function delete(PageInterface $model)
    {
        $model->delete();

        $this->reindexAll();
    }

    /**
     * @throws Exception
     */
    private function savePageStore(PageInterface $page): PageInterface
    {

        $pageStore  = $this->pageStoreFactory->create();
        $pageId     = intval($page->getPageId());
        $storeId    = intval($page->getStoreId());
        $useDefault = $page->getUseDefault();
        $this->pageStoreResource->loadByPageAndStore($pageStore, $pageId, $storeId);
        
        if (!$pageStore->getId()) {
            $pageStore->setPageId($pageId)->setStoreId($storeId);
        }
        
        foreach (PageInterface::STORE_FIELDS as $field) {
            $pageStore->setData(
                $field,
                (isset($useDefault[$field]) && (PageInterface::USE_DEFAULT_TRUE === (int)$useDefault[$field]))
                    ? null
                    : $page->getData($field)
            );
        }
        
        $filledFields = array_flip(PageInterface::STORE_FIELDS);
        foreach (PageInterface::STORE_FIELDS as $key) {
            if (null === $pageStore->getData($key)) {
                unset($filledFields[$key]);
            }
        }

        if (!count($filledFields)) {
            $this->pageStoreResource->delete($pageStore);

            return $page;
        }
        
        $this->pageStoreResource->save($pageStore);

        return $page;
    }

    private function reindexPage(int $pageId): void
    {
        $indexer = $this->indexerRegistry->get(LandingPageProduct::INDEXER_ID);

        if (!$indexer->isScheduled()) {
            $indexer->reindexRow($pageId);
        }
    }

    private function reindexAll(): void
    {
        $indexer = $this->indexerRegistry->get(LandingPageProduct::INDEXER_ID);

        if (!$indexer->isScheduled()) {
            $indexer->reindexAll();
        }
    }
}
