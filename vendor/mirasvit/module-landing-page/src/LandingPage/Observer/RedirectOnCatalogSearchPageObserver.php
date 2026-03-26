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
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Repository\PageRepository;
use Mirasvit\LandingPage\Service\FilterService;

class RedirectOnCatalogSearchPageObserver implements ObserverInterface
{
    protected $urlFactory;

    private   $response;

    private   $pageRepository;

    private   $query;

    private   $storeManager;

    private   $filterService;

    public function __construct(
        HttpResponse $response,
        PageRepository $pageRepository,
        QueryFactory $queryFactory,
        UrlFactory $urlFactory,
        StoreManagerInterface $storeManager,
        FilterService $filterService
    ) {
        $this->response       = $response;
        $this->pageRepository = $pageRepository;
        $this->query          = $queryFactory->get();
        $this->urlFactory     = $urlFactory;
        $this->storeManager   = $storeManager;
        $this->filterService  = $filterService;
    }

    /**
     * Observer for controller_action_postdispatch_catalogsearch_result_index
     */
    public function execute(EventObserver $observer): bool
    {
        $queryText = strip_tags($this->query->getQueryText());

        $collection = $this->pageRepository->getCollection();
        $collection->addFieldToFilter(PageInterface::IS_ACTIVE, true)
            ->addFieldToFilter(PageInterface::REDIRECT, true)
            ->addFieldToFilter(PageInterface::SEARCH_TERM, ['like' => "%$queryText%"])
            ->addStoreFilter((int)$this->storeManager->getStore()->getId());

        $filtersData = $this->filterService->getFiltersData();
        
        foreach ($collection as $landingPage) {
            foreach (preg_split("~\,~", $landingPage->getSearchTerm()) as $term) {
                if (
                    (trim($term) == trim($queryText)) 
                    && $this->filterService->validateFilters($landingPage, $filtersData, true)
                    && $landingPage->getId()
                ) {
                    $url  = $this->urlFactory->create()->getUrl($landingPage->getUrlKey());
                    $this->response->setRedirect($url)->sendResponse();

                    return true;
                }
            }
        }

        return false;
    }
}
