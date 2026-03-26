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

namespace Mirasvit\Brand\Block\Brand;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Service\BrandPageService;
use Mirasvit\Brand\Service\ImageUrlService;
use Magento\Cms\Model\Template\FilterProvider;

class Description extends Template
{
    protected $_template = 'brand/description.phtml';

    private   $brandPageService;

    private   $imageUrlService;

    private   $config;

    private   $filterProvider;

    public function __construct(
        Context $context,
        BrandPageService $brandPageService,
        ImageUrlService $imageUrlService,
        Config $config,
        FilterProvider $filterProvider,
        array $data = []
    ) {
        $this->brandPageService = $brandPageService;
        $this->imageUrlService  = $imageUrlService;
        $this->config           = $config;
        $this->filterProvider   = $filterProvider;

        parent::__construct($context, $data);
    }

    public function getBrandPage(): ?BrandPageInterface
    {
        return $this->brandPageService->getBrandPage();
    }

    public function getBrandLogoUrl(): string
    {
        return $this->imageUrlService->getImageUrl($this->getBrandPage()->getLogo());
    }

    public function isShowBrandLogo(): bool
    {
        return $this->config->getBrandPageConfig()->isShowBrandLogo();
    }

    public function isShowBrandDescription(): bool
    {
        return $this->config->getBrandPageConfig()->isShowBrandDescription();
    }

    public function filterOutputHtml($string): string
    {
        return $this->filterProvider->getPageFilter()->filter($string);
    }
}
