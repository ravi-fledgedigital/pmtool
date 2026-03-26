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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Brand\Model\StoreSwitcher;

use Magento\Framework\HTTP\PhpEnvironment\RequestFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreSwitcherInterface;
use Mirasvit\Brand\Repository\BrandRepository;
use Mirasvit\Brand\Service\BrandUrlService;

class ManageBrandUrl implements StoreSwitcherInterface
{
    private $requestFactory;

    private $brandUrlService;

    private $brandRepository;

    public function __construct(
        RequestFactory  $requestFactory,
        BrandUrlService $brandUrlService,
        BrandRepository $brandRepository
    ) {
        $this->requestFactory  = $requestFactory;
        $this->brandUrlService = $brandUrlService;
        $this->brandRepository = $brandRepository;
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

        $matchedData = $this->brandUrlService->match($urlPath, (int)$fromStore->getId());

        if (!$matchedData) {
            return $targetUrl;
        }

        if ($matchedData->getActionName() === 'index') {
            return $this->brandUrlService->getBaseBrandUrl();
        }

        foreach ($this->brandRepository->getList() as $brand) {
            if ($brand->getUrlKey() === $matchedData->getRouteName()) {
                return $brand->getUrl();
            }
        }

        return $targetUrl;
    }
}
