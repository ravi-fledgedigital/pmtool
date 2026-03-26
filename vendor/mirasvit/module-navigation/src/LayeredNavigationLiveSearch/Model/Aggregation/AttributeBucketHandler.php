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


namespace Mirasvit\LayeredNavigationLiveSearch\Model\Aggregation;


use Magento\Framework\Search\Response\Aggregation\ValueFactory;
use Magento\Framework\Search\Response\Bucket;
use Magento\Framework\Search\Response\BucketFactory;
use Magento\LiveSearchAdapter\Model\Aggregation\BucketHandlerInterface;
use Mirasvit\LayeredNavigation\Model\Config\ExtraFilterConfigProvider;

class AttributeBucketHandler implements AttributeBucketHandlerInterface
{
    private $extraFiltersCodes = [
        ExtraFilterConfigProvider::STOCK_FILTER,
        ExtraFilterConfigProvider::NEW_FILTER,
        ExtraFilterConfigProvider::ON_SALE_FILTER
    ];

    /**
     * @var string
     */
    private $storeViewCode;

    /**
     * @var string
     */
    private $attributeCode;

    /**
     * @var array
     */
    private $rawBuckets;

    /**
     * @var array
     */
    private $attributesMetadata;

    private $bucketFactory;

    private $valueFactory;

    public function __construct(
        string $storeViewCode,
        string $attributeCode,
        array $rawBuckets,
        array $attributesMetadata,
        BucketFactory $bucketFactory,
        ValueFactory $valueFactory
    ) {
        $this->storeViewCode      = $storeViewCode;
        $this->attributeCode      = $attributeCode;
        $this->rawBuckets         = $rawBuckets;
        $this->attributesMetadata = $attributesMetadata;
        $this->bucketFactory      = $bucketFactory;
        $this->valueFactory       = $valueFactory;
    }

    public function getBucketName(): string
    {
        return $this->attributeCode . '_bucket';
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getBucket(): ?Bucket
    {
        $bucketValues = [];

        if (isset($this->attributesMetadata[$this->attributeCode]['options'])) {
            $attributeOptions = $this->attributesMetadata[$this->attributeCode]['options'];

            if ($this->attributeCode == ExtraFilterConfigProvider::RATING_FILTER) {
                $this->updateRatingCount();
            }

            foreach ($this->rawBuckets as $idx => $bucket) {
                $optionId = null;
                if (isset($attributeOptions[$this->storeViewCode])) {
                    $optionId = array_search($bucket['title'], $attributeOptions[$this->storeViewCode]);
                }
                if (empty($optionId) && isset($bucket['title'], $attributeOptions['admin'])) {
                    $optionId = array_search($bucket['title'], $attributeOptions['admin']);
                }

                if (
                    (in_array($this->attributeCode, $this->extraFiltersCodes) && $optionId >= 0)
                    || $optionId
                ) {
                    $metrics = [
                        'count' => $bucket['count'],
                        'value' => $optionId
                    ];

                    $bucketValues[] = $this->valueFactory->create([
                        'value' => $optionId,
                        'metrics' => $metrics
                    ]);
                }
            }
        }

        if (!empty($bucketValues)) {
            $bucket = $this->bucketFactory->create([
                'name' => $this->getBucketName(),
                'values' => $bucketValues
            ]);

            return $bucket;
        }

        return null;
    }

    private function updateRatingCount(): void
    {
        $maxRate = 5;

        $ids              = array_column($this->rawBuckets, 'id');
        $maxRateBucketIdx = array_search($maxRate, $ids);

        if (!$maxRateBucketIdx && $maxRateBucketIdx !== 0) {
            return;
        }

        $maxRate--;

        while (
            !($bucketToFixIdx = array_search($maxRate, $ids))
            && $maxRate > 0
        ) {
            $maxRate--;
        }

        if (!$bucketToFixIdx) {
            return;
        }

        $this->rawBuckets[$bucketToFixIdx]['count'] += $this->rawBuckets[$maxRateBucketIdx]['count'];
    }
}
