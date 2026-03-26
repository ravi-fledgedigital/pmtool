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



namespace Mirasvit\LayeredNavigation\Plugin\Frontend\Elasticsearch\Aggregation\Builder\Dynamic;

use Magento\Framework\Search\Dynamic\DataProviderInterface;
use Magento\Elasticsearch\SearchAdapter\Aggregation\Builder\Dynamic;
use Magento\Framework\Search\Request\BucketInterface as RequestBucketInterface;

/**
 * @see \Magento\Elasticsearch\SearchAdapter\Aggregation\Builder\Dynamic::build()
 */

class ExtendPriceAggregationBuild
{
    public function aroundBuild(
        Dynamic $subject,
        Callable $proceed,
        RequestBucketInterface $bucket,
        array $dimensions,
        array $queryResult,
        DataProviderInterface $dataProvider
    ) {
        $data = $proceed($bucket, $dimensions, $queryResult, $dataProvider);
        if (isset($queryResult['aggregations'][$bucket->getName()]['min'])) {
            $data['min'] = [
                'value' => 'min',
                'price' => $queryResult['aggregations'][$bucket->getName()]['min'],
                'count' => 1,
            ];
        }

        if (isset($queryResult['aggregations'][$bucket->getName()]['max'])) {
            $data['max'] = [
                'value' => 'max',
                'price' => $queryResult['aggregations'][$bucket->getName()]['max'],
                'count' => 1,
            ];
        }

        return $data;
    }
}