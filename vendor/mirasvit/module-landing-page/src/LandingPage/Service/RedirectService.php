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

use Mirasvit\LandingPage\Repository\PageRepository;
use Magento\Store\Model\StoreManagerInterface;

class RedirectService
{
    private $pageRepository;

    private $storeManager;

    public function __construct(
        PageRepository $pageRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->pageRepository = $pageRepository;
        $this->storeManager   = $storeManager;
    }

    public function isRedirected(int $pageId, string $referer): bool
    {
        $page = $this->pageRepository->get($pageId, (int)$this->storeManager->getStore()->getId());

        $pageUrl = trim($page->getUrlKey(), '/');

        $urlParsed = parse_url($referer);

        $path = isset($urlParsed['path']) ? $urlParsed['path'] : '';

        $storeCode = '/' . $this->storeManager->getStore()->getCode() . '/';

        if (substr($path, 0, strlen($storeCode)) === $storeCode) {
            $path = substr($path, strlen($storeCode));
        } 

        return substr(trim($path, '/'), 0, strlen($pageUrl)) !== $pageUrl;
    }

    
}