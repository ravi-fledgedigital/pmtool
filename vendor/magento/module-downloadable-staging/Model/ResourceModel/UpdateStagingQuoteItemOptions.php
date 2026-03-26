<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DownloadableStaging\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Query\Generator;
use Magento\Framework\Exception\LocalizedException;

class UpdateStagingQuoteItemOptions
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @var Generator
     */
    private Generator $batchQueryGenerator;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Generator $batchQueryGenerator
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Generator $batchQueryGenerator
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->batchQueryGenerator = $batchQueryGenerator;
        $this->connection = $this->resourceConnection->getConnection();
    }

    /**
     * Main method to get and update quote item options
     *
     * @param ProductInterface $entity
     * @param array $newLinkIds
     * @param array $linksByFile
     * @return void
     * @throws LocalizedException
     */
    public function execute(ProductInterface $entity, array $newLinkIds, array $linksByFile): void
    {
        foreach ($this->getQuoteItemOptionsToUpdate($entity, $newLinkIds) as $quoteItemOptions) {
            $linksForQuery = $this->getQuoteItemOptionLinks($quoteItemOptions, $linksByFile);
            $this->updateQuoteItemOptionLinks($linksForQuery);
        }
    }

    /**
     * Retrieve quote item options that need updated
     *
     * @param ProductInterface $product
     * @param array $newLinkIds
     * @return \Generator
     * @throws LocalizedException
     */
    private function getQuoteItemOptionsToUpdate(ProductInterface $product, array $newLinkIds): \Generator
    {
        $batchSelectIterator = $this->batchQueryGenerator->generate(
            'option_id',
            $this->connection->select()->from(
                ['qio' => $this->connection->getTableName('quote_item_option')],
                ['*']
            )->join(
                ['qi' => $this->connection->getTableName('quote_item')],
                'qi.item_id = qio.item_id and qio.code = \'downloadable_link_ids\'',
                []
            )->join(
                ['cpe' => $this->connection->getTableName('catalog_product_entity')],
                'qi.product_id = cpe.entity_id',
                []
            )->join(
                ['links' => $this->connection->getTableName('downloadable_link')],
                'links.product_id = cpe.row_id',
                [
                    'links.link_id as old_link_id',
                    'sort_order',
                    'link_url',
                    'link_file',
                    'link_type',
                    'links.product_id as links_row_id'
                ]
            )
                ->where('qi.product_id = ?', $product->getId())
                ->where('qio.value not in (?)', array_map('intval', $newLinkIds))
        );

        foreach ($batchSelectIterator as $select) {
            yield $this->connection->fetchAll($select);
        }
    }

    /**
     * Get prepared data to update
     *
     * @param array $quoteItemOptions
     * @param array $links
     * @return array
     */
    private function getQuoteItemOptionLinks(array $quoteItemOptions, array $links): array
    {
        $updatedLinkIds = [];
        foreach ($quoteItemOptions as $itemOption) {
            $downloadableLinkIds = explode(',', $itemOption['value']);
            foreach ($downloadableLinkIds as $downloadableLinkId) {
                $key = json_encode(
                    $itemOption['links_row_id'] .
                    $itemOption['sort_order'] .
                    $itemOption['link_type'] .
                    $itemOption['link_file'] .
                    $itemOption['link_url']
                );
                $downloadableLinkId = $links[$key] ?? $downloadableLinkId;
                $updatedLinkIds[$itemOption['option_id']][$downloadableLinkId] = $downloadableLinkId;
            }
        }
        $linkIdsForQuery = [];
        foreach ($updatedLinkIds as $optionId => $updatedLinkId) {
            sort($updatedLinkId);
            $linkIds = implode(',', array_unique($updatedLinkId));
            if ($linkIds) {
                $linkIdsForQuery[$linkIds][] = $optionId;
            }
        }
        return $linkIdsForQuery;
    }

    /**
     * Update downloadable_link_ids in quote_item_option
     *
     * @param array $linkIdsForQuery
     * @return void
     */
    private function updateQuoteItemOptionLinks(array $linkIdsForQuery)
    {
        foreach ($linkIdsForQuery as $value => $optionIds) {
            $data = [
                'value' => $value
            ];
            $where = $this->connection->quoteInto(
                'code = \'downloadable_link_ids\' and option_id IN (?)',
                $optionIds,
                'INT'
            );
            $this->connection->update($this->connection->getTableName('quote_item_option'), $data, $where);
        }
    }
}
