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

namespace Mirasvit\Brand\Service;

use Magento\Framework\View\Element\BlockFactory;
use Mirasvit\Brand\Api\Data\BrandInterface;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Block\Logo;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Repository\BrandPageRepository;
use Mirasvit\Brand\Repository\BrandRepository;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Cms\Model\Template\FilterProvider;

class BrandLogoService
{
    const BRAND_TITLE_PATTERN             = '{title}';
    const BRAND_SMALL_IMAGE_PATTERN       = '{small_image}';
    const BRAND_IMAGE_PATTERN             = '{image}';
    const BRAND_DESCRIPTION_PATTERN       = '{description}';
    const BRAND_SHORT_DESCRIPTION_PATTERN = '{short_description}';

    private static $brandPageList;

    private static $brandList;

    /** @var BrandPageInterface */
    private $brandPage;

    /** @var BrandInterface */
    private $brand;

    private $brandPageRepository;

    private $brandRepository;

    private $config;

    private $brandUrlService;

    private $imageUrlService;

    private $blockFactory;

    private $attrOptionCollectionFactory;

    private $brandAttributeService;

    private $filterProvider;

    public function __construct(
        BlockFactory $blockFactory,
        ImageUrlService $imageUrlService,
        BrandPageRepository $brandPageRepository,
        BrandRepository $brandRepository,
        BrandUrlService $brandUrlService,
        CollectionFactory $attrOptionCollectionFactory,
        BrandAttributeService $brandAttributeService,
        FilterProvider $filterProvider,
        Config $config
    ) {
        $this->blockFactory                = $blockFactory;
        $this->imageUrlService             = $imageUrlService;
        $this->brandPageRepository         = $brandPageRepository;
        $this->brandRepository             = $brandRepository;
        $this->brandUrlService             = $brandUrlService;
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->brandAttributeService       = $brandAttributeService;
        $this->filterProvider              = $filterProvider;
        $this->config                      = $config;
    }

    public function getLogoHtml(): string
    {
        if (!$this->isBrandPageAvailable()) {
            return '';
        }

        return (string)$this->blockFactory
            ->createBlock(Logo::class)
            ->setTemplate('Mirasvit_Brand::logo/logo.phtml')
            ->toHtml();
    }

    public function isBrandPageAvailable(): bool
    {
        return $this->brandPage->getIsActive();
    }

    public function getLogoImageUrl(?string $imageType = null): string
    {
        $imageType = $imageType ? null : 'thumbnail';

        return $this->imageUrlService->getImageUrl($this->brandPage->getLogo(), $imageType);
    }

    public function getBrandTitle(): string
    {
        return $this->brandPage->getBrandTitle();
    }

    public function getBrandUrl(): string
    {
        return $this->brandUrlService->getBrandUrl($this->brand);
    }

    public function getBrandDescription(): string
    {
        return $this->brandPage->getBrandDescription();
    }

    public function getBrandShortDescription(): string
    {
        return $this->filterOutputHtml($this->brandPage->getBrandShortDescription());
    }

    public function getLogoTooltipContent(string $tooltip): string
    {
        $tooltipContent = '';
        $style          = '';
        if ($tooltip) {
            $alt = ' alt="' . $this->getBrandTitle() . '"';
            if ($tooltipMaxImageWidth = $this->config->getBrandLogoConfig()->getTooltipMaxImageWidth()) {
                $style = 'style="max-width: ' . $tooltipMaxImageWidth . 'px !important;"';
            }
            $search  = [
                BrandLogoService::BRAND_TITLE_PATTERN,
                BrandLogoService::BRAND_IMAGE_PATTERN,
                BrandLogoService::BRAND_SMALL_IMAGE_PATTERN,
                BrandLogoService::BRAND_DESCRIPTION_PATTERN,
                BrandLogoService::BRAND_SHORT_DESCRIPTION_PATTERN,
            ];
            $replace = [
                $this->getBrandTitle(),
                '<img ' . $style . $alt . ' src="' . $this->getLogoImageUrl() . '">',
                '<img' . $alt . ' src="' . $this->getLogoImageUrl() . '">',
                $this->getPreparedText((string)__($this->getBrandDescription())),
                $this->getBrandShortDescription(),
            ];

            $tooltipContent .= str_replace($search, $replace, $tooltip);
        }

        return $tooltipContent;
    }

    public function setBrandDataByOptionId(int $optionId): void
    {
        $this->setBrandData($optionId);

        if (self::$brandPageList && isset(self::$brandPageList[$optionId]) && isset(self::$brandList[$optionId])) {
            $this->brandPage = self::$brandPageList[$optionId];
            $this->brand     = self::$brandList[$optionId];
        } else {
            $this->brandPage = $this->brandPageRepository->create();
            $this->brand     = $this->brandRepository->create();
        }
    }

    private function getPreparedText(string $text): string
    {
        return str_replace(['"', "'"], ['&quot;', '&apos;'], $text);
    }

    private function setBrandData(?int $optionId = null): void
    {
        $attribute = $this->brandAttributeService->getAttribute();
        if (!$attribute) {
            return;
        }

        if ($optionId === null) {
            if (self::$brandPageList !== null) {
                return;
            }

            self::$brandPageList = [];
            self::$brandList     = [];

            foreach ($this->brandPageRepository->getCollection() as $brandPage) {
                $optionId = $brandPage->getAttributeOptionId();
                self::$brandPageList[$optionId] = $brandPage;
            }

            foreach ($this->brandRepository->getFullList() as $brand) {
                $optionId = $brand->getPage()->getAttributeOptionId();
                self::$brandList[$optionId] = $brand;
            }

            return;
        }

        if (isset(self::$brandPageList[$optionId]) && isset(self::$brandList[$optionId])) {
            return;
        }

        $brandPage = $this->brandPageRepository->getByOptionId($optionId);
        $brandOption = $this->loadBrandOption($optionId);
        $brand = $this->buildBrand($optionId, $brandPage, $brandOption);

        self::$brandPageList[$optionId] = $brandPage;
        self::$brandList[$optionId]     = $brand;
    }

    private function loadBrandOption(int $optionId): Option
    {
        $attribute = $this->brandAttributeService->getAttribute();

        return $this->attrOptionCollectionFactory->create()
            ->setPositionOrder('asc')
            ->setAttributeFilter($attribute->getId())
            ->setStoreFilter()
            ->addFieldToFilter('tsv.option_id', $optionId)
            ->getFirstItem();
    }

    private function buildBrand(
        int $optionId,
        ?BrandPageInterface $brandPage,
        Option $option
    ): BrandInterface {
        $attribute = $this->brandAttributeService->getAttribute();

        $brandData = [
            BrandInterface::ID             => $optionId,
            BrandInterface::LABEL          => $option->getValue(),
            BrandInterface::PAGE           => $brandPage,
            BrandInterface::ATTRIBUTE_ID   => $attribute->getId(),
            BrandInterface::ATTRIBUTE_CODE => $attribute->getAttributeCode(),
        ];

        return $this->brandRepository->create(['data' => $brandData]);
    }

    private function filterOutputHtml($string): string
    {
        return $this->filterProvider->getPageFilter()->filter($string);
    }
}
