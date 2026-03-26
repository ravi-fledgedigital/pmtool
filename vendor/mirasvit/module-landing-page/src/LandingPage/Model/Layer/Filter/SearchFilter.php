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

namespace Mirasvit\LandingPage\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\CatalogSearch\Model\Advanced\Request\BuilderFactory as RequestBuilderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Search\Model\SearchEngine;

class SearchFilter extends Layer\Filter\AbstractFilter
{
    private $context;

    private $requestBuilderFactory;

    private $searchEngine;

    private $searchParam;

    public function __construct(
        Layer                 $layer,
        Context               $context,
        RequestBuilderFactory $requestBuilderFactory,
        SearchEngine          $searchEngine,
        array                 $data = []
    ) {
        parent::__construct(
            $context->filterItemFactory,
            $context->storeManager,
            $layer,
            $context->itemDataBuilder,
            ['data' => ['attribute_model' => $this], 'layer' => $layer]
        );
        $this->searchParam           = 'landing_search';
        $this->_requestVar           = 'q';
        $this->context               = $context;
        $this->requestBuilderFactory = $requestBuilderFactory;
        $this->searchEngine          = $searchEngine;
    }

    public function apply(RequestInterface $request): self
    {
        $attributeValue = $request->getParam($this->searchParam);
        if (empty($attributeValue)) {
            return $this;
        }

        $requestBuilder = $this->requestBuilderFactory->create()
            ->bind('search_term', $attributeValue)
            ->bindDimension('scope', $this->context->storeManager->getStore()->getId())
            ->setRequestName('catalogsearch_fulltext');

        $result  = $this->searchEngine->search($requestBuilder->create());
        $results = [];

        foreach ($result->getIterator() as $item) {
            $results[] = $item->getId();
        }

        if (empty($results)) {
            $results[] = -1;
        }

        $this->getLayer()->getProductCollection()->addFieldToFilter($this->_requestVar, $results);

        return $this;
    }

    public function getName(): string
    {
        return (string)__('Search');
    }

    public function isActive(): bool
    {
        return true;
    }

    protected function _getItemsData(): array
    {
        return [];
    }
}
