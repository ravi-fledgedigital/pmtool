<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\CatalogStaging\Model\Product\DateAttributesMetadata;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Product update scheduler
 */
class ProductStaging implements ProductStagingInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var DateAttributesMetadata
     */
    private $dateAttributesMetadata;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * ProductStaging constructor.
     *
     * @param EntityManager $entityManager
     * @param StoreManagerInterface $storeManager
     * @param CampaignValidator $campaignValidator
     * @param MetadataPool $metadataPool
     * @param TypeResolver $typeResolver
     * @param DateAttributesMetadata $dateAttributesMetadata
     * @param UpdateRepositoryInterface $updateRepository
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        EntityManager $entityManager,
        StoreManagerInterface $storeManager,
        CampaignValidator $campaignValidator,
        MetadataPool $metadataPool,
        TypeResolver $typeResolver,
        DateAttributesMetadata $dateAttributesMetadata,
        UpdateRepositoryInterface $updateRepository,
        TimezoneInterface $localeDate,
    ) {
        $this->entityManager = $entityManager;
        $this->storeManager = $storeManager;
        $this->campaignValidator = $campaignValidator;
        $this->metadataPool = $metadataPool;
        $this->typeResolver = $typeResolver;
        $this->dateAttributesMetadata = $dateAttributesMetadata;
        $this->updateRepository = $updateRepository;
        $this->localeDate = $localeDate;
    }

    /**
     * Schedule product update
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $version
     * @param array $arguments
     * @return bool
     * @throws ValidatorException
     */
    public function schedule(ProductInterface $product, $version, $arguments = [])
    {
        $previous = isset($arguments['origin_in']) ? $arguments['origin_in'] : null;
        if (!$this->campaignValidator->canBeScheduled($product, $version, $previous)) {
            throw new ValidatorException(
                __('Future Update already exists in this time range. Set a different range and try again.')
            );
        }
        $arguments['created_in'] = $version;
        $arguments['store_id'] = $this->storeManager->getStore()->getId();
        $this->syncDateAttributes($product, $version);
        return (bool)$this->entityManager->save($product, $arguments);
    }

    /**
     * Unschedule product update
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $version
     * @return bool
     */
    public function unschedule(ProductInterface $product, $version)
    {
        return (bool)$this->entityManager->delete(
            $product,
            [
                'store_id' => $this->storeManager->getStore()->getId(),
                'created_in' => $version
            ]
        );
    }

    /**
     * Sync date attributes with update start/end time
     *
     * @param ProductInterface $product
     * @param string $version
     * @return void
     * @throws \Exception
     */
    private function syncDateAttributes(ProductInterface $product, $version): void
    {
        $entityType = $this->typeResolver->resolve($product);
        $hydrator = $this->metadataPool->getHydrator($entityType);
        $origData = $hydrator->extract($product);
        $update = null;
        $data = [];
        $attributes = [
            'start' => $this->dateAttributesMetadata->getStartDateAttributes(),
            'end' => $this->dateAttributesMetadata->getEndDateAttributes(),
        ];
        foreach ($attributes as $type => $attributeCodes) {
            foreach ($attributeCodes as $attributeCode) {
                $relatedAttribute = $this->dateAttributesMetadata->getRelatedAttribute($attributeCode);
                $emptyValues = $this->dateAttributesMetadata->getRelatedAttributeEmptyValues($attributeCode);
                if (!isset($origData[$relatedAttribute])
                    || in_array($origData[$relatedAttribute], $emptyValues, true)
                ) {
                    $data[$attributeCode] = null;
                } else {
                    if (!$update) {
                        //load update only if needed
                        $update = $this->updateRepository->get($version);
                    }
                    //
                    $time = $type === 'start' ? $update->getStartTime() : $update->getEndTime();
                    $data[$attributeCode] = $time
                        ? $this->localeDate->date(strtotime($time))->format(DateTime::DATETIME_PHP_FORMAT)
                        : null;
                }
            }
        }
        if ($data) {
            $hydrator->hydrate($product, $data);
        }
    }
}
