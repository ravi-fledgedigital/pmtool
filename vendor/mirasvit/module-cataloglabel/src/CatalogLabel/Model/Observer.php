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

namespace Mirasvit\CatalogLabel\Model;

use Magento\Cron\Model\Schedule;
use Magento\Framework\Indexer\IndexerRegistry;
use Mirasvit\CatalogLabel\Repository\NewProductsRepository;
use Magento\Framework\App\CacheInterface;
use Magento\PageCache\Model\Cache\Type;

class Observer extends \Magento\Framework\DataObject
{
    const PRODUCT_CACHE_PREFIX = 'cat_p_';

    private $cacheType;

    private $newProductsRepository;

    private $indexer;

    private $indexerRegistry;

    public function __construct(
        Indexer $indexer,
        NewProductsRepository $newProductsRepository,
        Type $cacheType,
        IndexerRegistry $indexerRegistry,
        array $data = []
    ) {
        $this->indexer                = $indexer;
        $this->newProductsRepository  = $newProductsRepository;
        $this->cacheType              = $cacheType;
        $this->indexerRegistry        = $indexerRegistry;

        parent::__construct($data);
    }

    public function apply(?Schedule $schedule = null, bool $isOutput = false, bool $emulate = false)
    {
        if ($this->indexerRegistry->get(Indexer::INDEXER_ID)->isScheduled() || $emulate) {
            $this->indexer->reindex();
            //flush cache for new products
            $this->flushNewProductsCache();
        }
    }

    public function flushNewProductsCache()
    {
        $newProductsCollection = $this->newProductsRepository->getCollection();

        if ($newProductsCollection->getSize() > 0) {
            $tags = [];
            foreach ($newProductsCollection as $newProduct) {
                $tags[] = self::PRODUCT_CACHE_PREFIX . $newProduct->getProductId();
                $this->newProductsRepository->delete($this->newProductsRepository->get((int)$newProduct->getId()));
            }
            $this->cacheType->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array_unique($tags));
        }
    }
}
