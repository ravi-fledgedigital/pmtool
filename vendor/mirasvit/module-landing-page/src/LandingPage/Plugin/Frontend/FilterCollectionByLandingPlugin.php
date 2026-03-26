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

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Mirasvit\LandingPage\Repository\PageRepository;

/** @see \Magento\Catalog\Model\Layer\CollectionFilterInterface::filter() */
class FilterCollectionByLandingPlugin
{
    private $pageRepository;

    private $filterRepository;

    private $storeManager;

    private $request;

    public function __construct(
        StoreManagerInterface $storeManager,
        RequestInterface      $request,
        FilterRepository      $filterRepository,
        PageRepository        $pageRepository
    ) {
        $this->storeManager     = $storeManager;
        $this->request          = $request;
        $this->filterRepository = $filterRepository;
        $this->pageRepository   = $pageRepository;
    }

    public function aroundFilter(object $subject, callable $proceed, ?Collection $collection = null, ...$args): void
    {
        $proceed($collection, ...$args);

        if ((string)$this->request->getFullActionName() !== 'landing_landing_view') {
            return;
        }
        // for brand page we register the root category ID, so products' request_paths are empty
        // to fix this we set flag and add URL-rewrite on category 0
        $collection->setFlag('do_not_use_category_id', true);
        $collection->addUrlRewrite(0);
        $pageId = (int)$this->request->getParam('landing');

        $landing = $this->pageRepository->get($pageId);
        if (!$landing) {
            return;
        }

        $categories = $landing->getCategories();

        if ($categories) {
            $collection->addFieldToFilter('category_ids', explode(',', $categories));
        }

        $filterCollection = $this->filterRepository->getByPageId($pageId);
        foreach ($filterCollection as $filter) {
            $collection->addFieldToFilter(
                $filter->getAttributeCode(),
                explode(',', $filter->getOptionIds())
            );
        }
    }
}
