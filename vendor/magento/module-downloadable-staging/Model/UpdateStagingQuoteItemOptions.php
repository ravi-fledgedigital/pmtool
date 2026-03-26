<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DownloadableStaging\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogStaging\Model\Product\Retriever as ProductRetriever;
use Magento\Downloadable\Model\Product\Type as ProductType;
use Magento\DownloadableStaging\Model\ResourceModel\UpdateStagingQuoteItemOptions
    as UpdateStagingQuoteItemOptionsResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Model\Entity\RetrieverPool;
use Magento\Staging\Model\StagingApplier\PostProcessorInterface;

class UpdateStagingQuoteItemOptions implements PostProcessorInterface
{
    /**
     * @var ProductType
     */
    private ProductType $downloadableType;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var RetrieverPool
     */
    private RetrieverPool $retrieverPool;

    /**
     * @var UpdateStagingQuoteItemOptionsResource
     */
    private UpdateStagingQuoteItemOptionsResource $updateStagingQuoteItemOptions;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param RetrieverPool $retrieverPool
     * @param ProductType $downloadableType
     * @param UpdateStagingQuoteItemOptionsResource $updateStagingQuoteItemOptions
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        RetrieverPool $retrieverPool,
        ProductType $downloadableType,
        UpdateStagingQuoteItemOptionsResource $updateStagingQuoteItemOptions
    ) {
        $this->productRepository = $productRepository;
        $this->retrieverPool = $retrieverPool;
        $this->downloadableType = $downloadableType;
        $this->updateStagingQuoteItemOptions = $updateStagingQuoteItemOptions;
    }

    /**
     * Update quote item options with new Downloadable Links data for products updated during staging update
     *
     * @param int $oldVersionId
     * @param int $currentVersionId
     * @param array $entityIds
     * @param string $entityType
     * @throws NoSuchEntityException|LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(
        int $oldVersionId,
        int $currentVersionId,
        array $entityIds,
        string $entityType
    ): void {
        if ($this->retrieverPool->getRetriever($entityType) instanceof ProductRetriever) {
            foreach ($entityIds as $entityId) {
                $newProduct = $this->productRepository->getById($entityId, false, null, true);
                $this->update($newProduct);
            }
        }
    }

    /**
     * Update quote item option with new Downloadable Links Ids.
     *
     * @param ProductInterface $entity
     * @return void
     * @throws LocalizedException
     */
    private function update(ProductInterface $entity): void
    {
        $extensionAttributes = $entity->getExtensionAttributes();
        $links = $extensionAttributes->getDownloadableProductLinks() ?? [];

        if ($links && $entity->getTypeId() === ProductType::TYPE_DOWNLOADABLE) {
            $newLinkIds = $this->getLinksIds($entity);
            $linksByFile = $this->getLinksByFile($entity);

            $this->updateStagingQuoteItemOptions->execute($entity, $newLinkIds, $linksByFile);
        }
    }

    /**
     * Retrieve Downloadable Links ids from the provided entity.
     *
     * @param ProductInterface $entity
     * @return int[]
     */
    private function getLinksIds(ProductInterface $entity): array
    {
        $linksIds = [];
        $entity->unsDownloadableLinks();
        $links = $this->downloadableType->getLinks($entity);

        foreach ($links as $link) {
            $linksIds[] = (int) $link->getId();
        }

        return $linksIds;
    }

    /**
     * Retrieve Downloadable Links ids by files from the provided entity.
     *
     * @param ProductInterface $entity
     * @return int[]
     */
    private function getLinksByFile(ProductInterface $entity): array
    {
        $linksByFile = [];
        $links = $this->downloadableType->getLinks($entity);

        foreach ($links as $link) {
            $key = json_encode(
                $link->getProductId()
                . $link->getSortOrder()
                . $link->getLinkType()
                . $link->getLinkFile()
                . $link->getLinkUrl()
            );
            $linksByFile[$key] = (int) $link->getId();
        }

        return $linksByFile;
    }
}
