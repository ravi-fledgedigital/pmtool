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

namespace Mirasvit\LandingPage\Service;

use Exception;
use Magento\Framework\View\Result\Page;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Model\Config\ConfigProvider;
use Mirasvit\LandingPage\Model\Url\UrlParser;
use Mirasvit\LandingPage\Repository\PageRepository;

class SeoService
{
    private $storeManager;

    private $pageRepository;

    private $urlParser;

    private $configProvider;

    public function __construct(
        StoreManagerInterface $storeManager,
        PageRepository        $pageRepository,
        UrlParser             $urlParser,
        ConfigProvider        $configProvider
    ) {
        $this->storeManager   = $storeManager;
        $this->pageRepository = $pageRepository;
        $this->urlParser      = $urlParser;
        $this->configProvider = $configProvider;
    }

    public function addSeoTags(Page $page, PageInterface $landing): void
    {
        $this->addCanonicalTag($page, $landing);
        $this->addHreflangTags($page, $landing);
    }

    public function addCanonicalTag(Page $page, PageInterface $landing): void
    {
        $canonicalUrl = $this->urlParser->getPageUrl($landing);
        $page->getConfig()->addRemotePageAsset(
            $canonicalUrl,
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );
    }

    public function addHreflangTags(Page $page, PageInterface $landing): void
    {
        try {
            $pageId = $landing->getPageId();
            $currentStoreId = (int)$this->storeManager->getStore()->getId();

            $allStores = $this->storeManager->getStores();

            foreach ($allStores as $store) {
                try {
                    $storeId = (int)$store->getId();

                    if ($storeId === $currentStoreId) {
                        continue;
                    }

                    if ($storeId === 0) {
                        continue;
                    }

                    $storeLanding = $this->pageRepository->get($pageId, $storeId);

                    if (!$storeLanding || !$storeLanding->getIsActive()) {
                        continue;
                    }

                    $storeIds = $landing->getStoreIds();
                    if ($storeIds != '0') {
                        $storeIdArray = array_map('trim', explode(',', $storeIds));
                        if (!in_array((string)$storeId, $storeIdArray, true)) {
                            continue;
                        }
                    }

                    $localeCode = $store->getConfig('general/locale/code');

                    if (!$localeCode) {
                        continue;
                    }

                    $hreflang = $this->getHreflangCode($localeCode, $store->getCode());

                    $storeUrl = $this->getPageUrlForStore($storeLanding, $store);

                    $page->getConfig()->addRemotePageAsset(
                        $storeUrl,
                        'link_rel',
                        ['attributes' => ['rel' => 'alternate', 'hreflang' => $hreflang]]
                    );
                } catch (Exception $e) {
                    continue;
                }
            }
        } catch (Exception $e) {
            // Silently fail if hreflang generation fails
        }
    }

    private function getHreflangCode(string $localeCode, string $storeCode): string
    {
        $hreflang = str_replace('_', '-', strtolower($localeCode));

        $storeCodeLower = strtolower($storeCode);
        if (strlen($storeCodeLower) == 2) {
            $hreflang = $storeCodeLower;
        } elseif (preg_match('/^([a-z]{2})[-_]/', $storeCodeLower, $matches)) {
            $hreflang = str_replace('_', '-', $storeCodeLower);
        }

        return $hreflang;
    }

    private function getPageUrlForStore(PageInterface $landing, $store): string
    {
        $baseUrl = $store->getBaseUrl();
        $urlKey = $landing->getUrlKey();
        $suffix = $this->configProvider->getUrlSuffix();

        return $baseUrl . $urlKey . $suffix;
    }
}
