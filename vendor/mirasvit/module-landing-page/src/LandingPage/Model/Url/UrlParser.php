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

namespace Mirasvit\LandingPage\Model\Url;

use Mirasvit\LandingPage\Api\Data\PageInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Mirasvit\LandingPage\Repository\PageRepository;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Magento\Framework\UrlInterface;
use Mirasvit\LandingPage\Model\Config\ConfigProvider;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class UrlParser
{
    private $request;

    private $oldStoreId = null;

    private $isStoreSwitched = null;

    private $storeId = null;

    private $page = null;

    private $storeManager;

    private $pageRepository;

    private $filterRepository;

    private $urlInterface;

    private $config;

    public function __construct(
        RequestInterface      $request,
        StoreManagerInterface $storeManager,
        PageRepository        $pageRepository,
        FilterRepository      $filterRepository,
        UrlInterface          $urlInterface,
        ConfigProvider        $config
    ) {
        $this->request            = $request;
        $this->storeManager       = $storeManager;
        $this->pageRepository     = $pageRepository;
        $this->filterRepository   = $filterRepository;
        $this->urlInterface       = $urlInterface;
        $this->config             = $config;
    }

    public function match(string $pathInfo, ?int $oldStoreId = null): ?DataObject
    {   
        $suffix = $this->config->getUrlSuffix() ? : '';
        
        if ($suffix) {
            if (substr($pathInfo, (int)strrpos($pathInfo, $suffix)) !== $suffix) {
                return null;
            }

            $pathInfo = substr($pathInfo, 0, strrpos($pathInfo, $suffix));
        }

        $this->isStoreSwitched = !is_null($oldStoreId);

        $this->oldStoreId = $oldStoreId;
        
        $urlPath = trim($pathInfo, '/');

        $url = $this->getPageRoute($urlPath);

        $pageUrl = $this->page ? $this->getPageUrl($this->page) : '';

        if ($url) {
            return !$this->isStoreSwitched
            ? $url
            : $this->redirect($pageUrl, $this->isStoreSwitched);
            return $url;
        }

        return null;
    }

    public function getPageUrl(PageInterface $page): string
    {
        $newPage = $this->pageRepository->get($page->getPageId(), $this->getStoreId());

        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        
        return $baseUrl . $newPage->getUrlKey() . $this->config->getUrlSuffix();
    }
    
    private function getPageRoute(string $urlKey): ?DataObject
    {
        $page = $this->findPage($urlKey, false);

        if (is_null($page) || $page->getPageId() == 0) {
            return null;
        }

        $this->page = $page;

        $pageData = $this->getPageData($page);
        
        $result = new DataObject([
            'module_name'     => 'landing',
            'controller_name' => 'landing',
            'action_name'     => 'view',
            'route_name'      => $pageData['url'],
            'params'          => $pageData['params'],
        ]);

        return $result;
    }

    public function findPage(string $urlKey, $matchWithFilters = true): ?PageInterface
    {
        $currentUrl = $this->urlInterface->getCurrentUrl();

        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        $replace = preg_replace("#$baseUrl#", '', $currentUrl);

        if (($replace != $currentUrl) && !$this->oldStoreId) {
            $urlKey = $replace;
        }
        
        $urlKey = trim($urlKey, '/');

        $storeId = $this->getStoreId();
        
        if ($this->isStoreSwitched) {
            $storeId = $this->oldStoreId;
        }
       
        $pageCollection = $this->pageRepository->getCollection()
            ->addStoreFilter($storeId)
            ->setOrder(PageInterface::PAGE_ID, 'DESC');

        $urlKey = explode('?', $urlKey);

        $suffix = $this->config->getUrlSuffix() ? : '';

        if ($suffix && substr($urlKey[0], -strlen($suffix)) === $suffix) {
            $urlKey[0] = substr($urlKey[0], 0, -strlen($suffix));
        }

        if ($matchWithFilters) {
            $urlKey = $urlKey[0] . '/';  
            $pageCollection->getSelect()->where("? LIKE CONCAT(`url_key`, '/', '%')", $urlKey);
        } else {
            $urlKey = trim($urlKey[0], '/');
            $pageCollection->addFieldToFilter(PageInterface::URL_KEY, $urlKey);
        }

        $page = $pageCollection->getFirstItem();

        if (!$page || !$page->getIsActive()) {
            return null;
        }

        return $page;

    }

    public function getStoreId(): int
    {
        if (!isset($this->storeId)) {
            try {
                $this->storeId = (int)$this->storeManager->getStore()->getId();
            } catch (NoSuchEntityException $exception) {
                return Store::DEFAULT_STORE_ID;
            }
        }

        return $this->storeId;
    }

    public function getPageData(PageInterface $page): array
    {
        $data = [];
        $filterCollection                                 = $this->filterRepository->getByPageId((int)$page->getPageId());
        $data['url']                      = $page->getUrlKey();
        $data['storeId']                  = explode(',', $page->getStoreIds());
        $data['params']['landing']        = (int)$page->getPageId();
        $data['params']['landing_search'] = $page->getSearchTerm();
        foreach ($filterCollection as $filter) {
            $data['params'][$filter->getAttributeCode()] = $filter->getOptionIds();
        }

        return $data;
    }

    private function redirect(string $redirectUrl, bool $isStoreSwitched = false): DataObject
    {
        $queryParams = $this->request->getParams();
        $query       = $isStoreSwitched ? '' : '?' . http_build_query($queryParams);

        return new DataObject(['redirect_url' => $redirectUrl . $query]);
    }
}
