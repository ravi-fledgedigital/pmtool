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


namespace Mirasvit\LayeredNavigation\Plugin\Frontend\Elasticsearch\SearchAdapter\Mapper;


use Magento\Elasticsearch7\SearchAdapter\Mapper;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager;

class MultiselectAndLogicPlugin
{
    private $attributeConfigRepository;

    private $moduleManager;

    public function __construct(
        AttributeConfigRepository $attributeConfigRepository,
        Manager $moduleManager
    ) {
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->moduleManager             = $moduleManager;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param mixed $subject
     * @param array $result
     *
     * @return array
     *
     */
    public function afterBuildQuery($subject, array $result): array
    {
        $multiselectAndFilters = [];

        if (!isset($result['body']['query']['bool']) || !isset($result['body']['query']['bool']['must'])) {
            return $result;
        }

        $filterQuery = $result['body']['query']['bool']['must'];

        foreach ($filterQuery as $idx => $filterItem) {
            if (!isset($filterItem['terms'])) {
                continue;
            }

            $attributeCode   = array_keys($filterItem['terms'])[0];
            $attributeConfig = $this->attributeConfigRepository->getByAttributeCode($attributeCode);

            if (!$attributeConfig) {
                continue;
            }

            if (
                $attributeConfig->getMultiselectLogic() === AttributeConfigInterface::MULTISELECT_LOGIC_AND
                && count($filterItem['terms'][$attributeCode]) > 1
            ) {
                $multiselectAndFilters[$attributeCode] = $filterItem['terms'][$attributeCode];

                unset($filterQuery[$idx]);
            }
        }

        foreach ($multiselectAndFilters as $attrCode => $values) {
            foreach ($values as $value) {
                $filterQuery[] = ['term' => [$attrCode => $value]];
            }
        }

        $result['body']['query']['bool']['must'] = array_values($filterQuery);

        $this->increaseAggregationSizes($result);

        return $result;
    }

    private function increaseAggregationSizes(array &$result): void
    {
        if (!isset($result['body']['aggregations']) || !is_array($result['body']['aggregations'])) {
            return;
        }

        $brandAttribute = '';
        if ($this->moduleManager->isEnabled('Mirasvit_Brand')) {
            $brandAttribute = $this->getBrandAttribute();
        }

        foreach ($result['body']['aggregations'] as $key => &$aggregation) {
            if (!isset($aggregation['terms']) || !is_array($aggregation['terms'])) {
                continue;
            }

            if (!isset($aggregation['terms']['size']) || !isset($aggregation['terms']['field'])) {
                continue;
            }

            $field = $aggregation['terms']['field'];

            if ($field === 'category_ids' || ($brandAttribute !== '' && $field === $brandAttribute)) {
                $aggregation['terms']['size'] = 3000;
            }
        }
    }

    private function getBrandAttribute(): string
    {
        try {
            $brandConfig = ObjectManager::getInstance()
                ->get(\Mirasvit\Brand\Model\Config\GeneralConfig::class);

            $attribute = $brandConfig->getBrandAttribute();

            return is_string($attribute) ? $attribute : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
