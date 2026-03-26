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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Service;


use Magento\Framework\Serialize\Serializer\Json;
use Mirasvit\Sorting\Model\ResourceModel\Criterion\CollectionFactory as CriterionCollectionFactory;

class SortingConfigService
{

    private CriterionCollectionFactory $criterionCollectionFactory;

    private Json $json;

    public function __construct(
        CriterionCollectionFactory $criterionCollectionFactory,
        Json                       $json
    ) {
        $this->criterionCollectionFactory = $criterionCollectionFactory;
        $this->json = $json;
    }

    public function getCriterionConfig(): array
    {
        $criterionCollection = $this->criterionCollectionFactory->create();
        $criterionCollection->addFieldToFilter('is_active', 1);
        $criterionCollection->addFieldToFilter('is_search_default', 1);
        $criterionCollection->setOrder('position', 'ASC');
        $config = [];
        $sort = [];

        foreach ($criterionCollection as $criterion) {
            $criterionConfig = $criterion->getData();
            $conditions = $this->json->unserialize($criterion->getConditionsSerialized());
            foreach ($this->processConditions($conditions) as $condition) {
                $sort[] = $condition;
            }

            $criterionConfig['conditions'] = $this->json->unserialize($criterion->getConditionsSerialized());
            $config[] = $criterionConfig;

        }

        $config['sort'] = $sort;

        return $config;
    }

    public function getRankingFactorConfig(): array
    {
        return [];
    }

    public function getSortingConfig(): array
    {
        $criterionConfig = $this->getCriterionConfig();
        $rankingFactorConfig = $this->getRankingFactorConfig();

        if ([] === $criterionConfig && [] === $rankingFactorConfig) {
            return [];
        }

        return [
            'criterion' => $criterionConfig,
            'ranking_factor' => $rankingFactorConfig,
        ];
    }

    public function getSerializedSortingConfig(): string
    {
        return $this->json->serialize($this->getSortingConfig());
    }

    private function processConditions(array $conditions)
    {
        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            foreach ($condition as $subCondition) {
                if ('attribute' !== $subCondition['sortBy']) {
                    $sort = [
                        "sorting_factor_" . $subCondition['rankingFactor'] => [
                            'order' => $subCondition['direction']
                        ]
                    ];
                    yield $sort;

                    continue;
                }

                $sort = [
                    $subCondition['attribute'] => ['order' => $subCondition['direction']],
                ];

                yield $sort;
            }
        }
    }
}
