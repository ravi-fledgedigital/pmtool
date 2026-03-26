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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Plugin;

use Magento\Framework\Search\Request\Config\FilesystemReader;
use Magento\Eav\Model\Config;
use Magento\Catalog\Model\Product;

class RequestConfigPlugin
{
    private $eavConfig;

    public function __construct(
        Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
    }

    public function afterRead(
        FilesystemReader $fsReader,
        array            $requests
    ) {
        $requests['catalogsearch_fulltext'] = $this->generateRequest('catalogsearch_fulltext');

        return $requests;
    }

    private function generateRequest(string $identifier)
    {
        $matchFields = $this->getSearchableFields();

        $request = [
            'dimensions'   => [
                'scope' => [
                    'name'  => 'scope',
                    'value' => 'default',
                ],
            ],
            'query'        => $identifier,
            'index'        => $identifier,
            'from'         => '0',
            'size'         => '1000',
            'filters'      => [],
            'aggregations' => [],
            'queries'      => [
                $identifier    => [
                    'type'           => 'boolQuery',
                    'name'           => $identifier,
                    'boost'          => 1,
                    'queryReference' => [
                        [
                            'clause' => 'should',
                            'ref'    => 'search_query',
                        ],
                    ],
                ],
                'search_query' => [
                    'type'  => 'matchQuery',
                    'name'  => $identifier,
                    'value' => '$search_term$',
                    'match' => $matchFields,
                ],
            ],
        ];

        return $request;
    }

    private function getSearchableFields(): array
    {
        $fields = [];

        $entityType = Product::ENTITY;
        $attributeCodes = ['name', 'sku', 'description'];

        foreach ($attributeCodes as $code) {
            $attribute = $this->eavConfig->getAttribute($entityType, $code);

            if ($attribute && $attribute->getIsSearchable()) {
                $weight = (int)$attribute->getSearchWeight() ?: 1;
                $fields[] = [
                    'field' => $code,
                    'boost' => $weight,
                ];
            }
        }

        return $fields;
    }
}
