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
use Mirasvit\Brand\Service\BrandPageService;

class Content extends Template
{
    private $brandPageService;

    public function __construct(
        BrandPageService $brandPageService,
        Template\Context $context,
        array $data = []
    ) {
        $this->brandPageService = $brandPageService;

        parent::__construct($context, $data);
    }

    public function getProductListHtml(): string
    {
        return $this->getChildHtml('brand.view.products');
    }

    public function shouldDisplayProducts(): bool
    {
        return $this->brandPageService->shouldDisplayProducts();
    }

    public function shouldDisplayCmsBlock(): bool
    {
        return $this->brandPageService->shouldDisplayCmsBlock();
    }

    public function getCmsBlockHtml(): string
    {
        $cmsBlockId = $this->brandPageService->getBrandPage()->getBrandCmsBlock();

        if (!$cmsBlockId) {
            return '';
        }

        $html = $this->getLayout()->createBlock(
            \Magento\Cms\Block\Block::class
        )->setBlockId(
            $cmsBlockId
        )->toHtml();

        return $html;
    }
}
