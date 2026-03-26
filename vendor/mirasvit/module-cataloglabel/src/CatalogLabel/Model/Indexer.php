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

use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Mirasvit\CatalogLabel\Service\ReindexService;

class Indexer implements IndexerActionInterface, MviewActionInterface
{
    const INDEXER_ID = 'mst_cataloglabel';

    private $reindexService;

    public function __construct(ReindexService $reindexService)
    {
        $this->reindexService = $reindexService;
    }

    public function reindex(?int $labelId = null, ?array $productIds = []): void
    {
        $this->reindexService->execute($labelId, $productIds);
    }

    public function reindexProduct(int $productId)
    {
        $this->reindex(null, [$productId]);
    }

    public function reindexLabel(int $labelId)
    {
        $this->reindex($labelId);
    }

    public function executeFull(): void
    {
        $this->reindex();
    }

    public function executeSingleRow(?int $labelId = null, ?int $productId = null): void
    {
        $this->reindex($labelId, $productId ? [$productId] : []);
    }

    public function executeList($ids): void
    {
        $this->reindex(null, $ids);
    }

    public function execute($ids): void
    {
        $this->executeList($ids);
    }

    public function executeRow($id): void
    {
        $this->reindexProduct($id);
    }
}
