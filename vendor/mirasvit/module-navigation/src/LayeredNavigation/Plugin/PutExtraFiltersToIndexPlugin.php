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

namespace Mirasvit\LayeredNavigation\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LayeredNavigation\Model\DataMapper;

class PutExtraFiltersToIndexPlugin
{
    private $newDataMapper;

    private $onSaleDataMapper;

    private $ratingDataMapper;

    private $stockDataMapper;

    private $storeManager;

    public function __construct(
        DataMapper\NewDataMapper $newDataMapper,
        DataMapper\OnSaleDataMapper $onSaleDataMapper,
        DataMapper\RatingDataMapper $ratingDataMapper,
        DataMapper\StockDataMapper $stockDataMapper,
        StoreManagerInterface $storeManager
    ) {
        $this->newDataMapper    = $newDataMapper;
        $this->onSaleDataMapper = $onSaleDataMapper;
        $this->ratingDataMapper = $ratingDataMapper;
        $this->stockDataMapper  = $stockDataMapper;
        $this->storeManager     = $storeManager;
    }

    /**
     * @param object $subject
     * @param array  $documents
     * @param int    $storeId
     * @param int    $mappedIndexerId
     *
     * @return array
     */
    public function beforeAddDocs($subject, array $documents, $storeId, $mappedIndexerId)
    {
        if (isset(array_values($documents)[0]['trigram']) || $mappedIndexerId !== 'product') {
            return [$documents, $storeId, $mappedIndexerId];
        }

        $storeId   = (int)$storeId;
        $store     = $this->storeManager->getStore($storeId);
        $websiteId = (int)$store->getWebsiteId();

        $documents = $this->newDataMapper->map($documents, $storeId);
        $documents = $this->onSaleDataMapper->map($documents, $storeId);
        $documents = $this->ratingDataMapper->map($documents, $storeId);
        $documents = $this->stockDataMapper->map($documents, $websiteId);

        return [$documents, $storeId, $mappedIndexerId];
    }
}
