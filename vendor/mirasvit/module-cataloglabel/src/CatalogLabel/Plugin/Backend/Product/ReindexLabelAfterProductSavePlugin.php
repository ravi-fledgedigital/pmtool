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




namespace Mirasvit\CatalogLabel\Plugin\Backend\Product;


use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;
use Mirasvit\CatalogLabel\Model\Indexer;

class ReindexLabelAfterProductSavePlugin
{
    private $indexer;

    private $indexerRegistry;

    public function __construct(Indexer $indexer, IndexerRegistry $indexerRegistry)
    {
        $this->indexer         = $indexer;
        $this->indexerRegistry = $indexerRegistry;
    }

    public function afterSave(ProductResource $subject, ProductResource $result, AbstractModel $product): ProductResource
    {
        $isIndexerScheduled = true;

        try {
            $idxr = $this->indexerRegistry->get(Indexer::INDEXER_ID);
            $isIndexerScheduled = $idxr->isScheduled();
        } catch (\Exception $e) {

        }

        $productId = $product->getId();

        if (!$productId || $isIndexerScheduled) {
            return $result;
        }

        $this->indexer->reindexProduct((int)$productId);

        $idxr->getState()->setUpdated(time())->save();

        return $result;
    }
}
