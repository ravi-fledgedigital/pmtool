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

namespace Mirasvit\LandingPage\Plugin\Frontend;


use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Category;
use Magento\Catalog\Model\Layer\FilterList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Mirasvit\LandingPage\Model\Layer\Filter\SearchFilter;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Mirasvit\LandingPage\Repository\PageRepository;

/** @see FilterList::getFilters */
class ApplySearchTermFilterPlugin extends FilterList
{
    protected $layerCategoryConfig;

    protected $objectManager;

    protected $request;

    protected $filterRepository;

    protected $pageRepository;

    public function __construct(
        PageRepository         $pageRepository,
        FilterRepository       $filterRepository,
        RequestInterface       $request,
        ObjectManagerInterface $objectManager
    ) {
        $this->request          = $request;
        $this->pageRepository   = $pageRepository;
        $this->filterRepository = $filterRepository;
        $this->objectManager    = $objectManager;
    }

    public function aroundGetFilters($subject, $proceed, Layer $layer)
    {
        $result = $proceed($layer);

        if ((string)$this->request->getFullActionName() !== 'landing_landing_view') {
            return $result;
        }

        $appliedAttributeCodes = $this->getAttributeCodes();

        if ($layer instanceof Category) {
            $toApply = true;

            foreach ($result as $key => $filter) {
                if ($filter instanceof SearchFilter) {
                    $toApply = false;
                }
                if ($filter->getData('attribute_model') !== null) {

                    if (in_array($filter->getAttributeModel()->getAttributeCode(), $appliedAttributeCodes)) {
                        unset($result[$key]);
                    }
                }
            }

            if ($toApply) {
                $result[] = $this->objectManager->create(SearchFilter::class, ['layer' => $layer]);
            }
        }
        return $result;
    }

    private function getAttributeCodes(): array
    {
        $codes   = [];
        $pageId  = $this->request->getParam('landing');
        $filters = $pageId ? $this->filterRepository->getByPageId((int)$pageId) : null;
        $landing = $pageId ? $this->pageRepository->get((int)$pageId) : null;

        if ($filters) {
            foreach ($filters as $filter) {
                $codes[] = $filter->getAttributeCode();
            }
        }

        if ($landing) {
            $categoryIdsStr = trim($landing->getCategories() ?? '');
            $categoryIds = array_filter(array_map('trim', explode(',', $categoryIdsStr)));

            if (count($categoryIds) !== 1) {
                $codes[] = 'category_ids';
            }
        }

        return $codes;

    }
}
