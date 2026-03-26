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

namespace Mirasvit\LandingPage\Observer;

use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Repository\PageRepository;
use Magento\Framework\Registry;
use Mirasvit\LandingPage\Service\FilterService;

class RedirectOnCategoryPageObserver implements ObserverInterface
{
    protected $urlFactory;

    private   $response;

    private   $pageRepository;

    private   $storeManager;

    private   $filterService;

    private   $registry;

    public function __construct(
        HttpResponse $response,
        PageRepository $pageRepository,
        UrlFactory $urlFactory,
        StoreManagerInterface $storeManager,
        FilterService $filterService,
        Registry $registry
    ) {
        $this->response       = $response;
        $this->pageRepository = $pageRepository;
        $this->urlFactory     = $urlFactory;
        $this->storeManager   = $storeManager;
        $this->filterService  = $filterService;
        $this->registry       = $registry;
    }

    /**
     * Observer for controller_action_postdispatch_catalog_category_view
     */
    public function execute(EventObserver $observer): bool
    {
        $category = $this->registry->registry('current_category');

        if (!$category) {
            return false;
        }

        $categoryId = $category->getId();

        $collection = $this->pageRepository->getCollection();
        $collection->addFieldToFilter(PageInterface::IS_ACTIVE, true)
            ->addFieldToFilter(PageInterface::REDIRECT, true)
            ->addFieldToFilter(PageInterface::SEARCH_TERM, ['null' => true])
            ->addStoreFilter((int)$this->storeManager->getStore()->getId());

        $collection->getSelect()->where("FIND_IN_SET(" . "'$categoryId'" . ", `" . PageInterface::CATEGORIES . "`)");

        if (!$collection->getSize()) {
            return false;
        }

        $filtersData = $this->filterService->getFiltersData();

        foreach ($collection as $landingPage) {
            if (
                $this->filterService->validateCategories($landingPage, $category)
                && $this->filterService->validateFilters($landingPage, $filtersData)
                && $landingPage->getId()
            ) {
                $url  = $this->urlFactory->create()->getUrl($landingPage->getUrlKey());
                $this->response->setRedirect($url)->sendResponse();
                return true;
            }
        }

        return false;
    }
}
