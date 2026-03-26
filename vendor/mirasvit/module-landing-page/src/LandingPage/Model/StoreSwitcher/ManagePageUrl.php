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

namespace Mirasvit\LandingPage\Model\StoreSwitcher;

use Magento\Framework\HTTP\PhpEnvironment\RequestFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreSwitcherInterface;
use Mirasvit\LandingPage\Model\Url\UrlParser;

class ManagePageUrl implements StoreSwitcherInterface
{
    private $requestFactory;

    private $urlParser;

    public function __construct(
        RequestFactory $requestFactory,
        UrlParser      $urlParser
    ) {
        $this->requestFactory = $requestFactory;
        $this->urlParser      = $urlParser;
    }

    public function switch(StoreInterface $fromStore, StoreInterface $targetStore, string $redirectUrl): string
    {
        $targetUrl = $redirectUrl;
        $request   = $this->requestFactory->create(['uri' => $targetUrl]);

        $urlPath = ltrim($request->getPathInfo(), '/');

        if ($targetStore->isUseStoreInUrl()) {
            $storeCode = preg_quote($targetStore->getCode() . '/', '/');
            $pattern   = "@^($storeCode)@";
            $urlPath   = preg_replace($pattern, '', $urlPath);
        }

        $result = $this->urlParser->match($urlPath, (int)$fromStore->getId());

        if ($result && $result->getRedirectUrl()) {
            return $result->getRedirectUrl();
        }

        return $targetUrl;
    }
}
