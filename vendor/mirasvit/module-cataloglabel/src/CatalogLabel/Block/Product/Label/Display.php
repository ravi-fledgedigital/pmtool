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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Block\Product\Label;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Api\Data\TemplateInterface;
use Mirasvit\CatalogLabel\Helper\ProductData;
use Mirasvit\CatalogLabel\Service\ContentService;

class Display extends Template
{
    protected $_template = 'Mirasvit_CatalogLabel::label/display.phtml';

    /** @var DisplayInterface|null */
    private $display;

    private $contentService;

    private $productDataHelper;

    private $isProductListPage;

    public function __construct(
        ContentService $contentService,
        ProductData $productDataHelper,
        Template\Context $context,
        array $data = []
    ) {
        $this->contentService    = $contentService;
        $this->productDataHelper = $productDataHelper;

        parent::__construct($context, $data);
    }

    public function getLabelTemplate(): ?TemplateInterface
    {
        return $this->display->getTemplate();
    }

    public function getDisplay(): ?DisplayInterface
    {
        if (!$this->display) {
            return null;
        }

        return $this->display;
    }

    public function setDisplay(DisplayInterface $display): self
    {
        $this->display = $display;

        return $this;
    }

    public function setProduct(?ProductInterface $product = null): self
    {
        $this->productDataHelper->setProduct($product);

        return $this;
    }

    public function getImageSizeHtml(): string
    {
        if (
            ($w = $this->display->getImageWidth())
            && ($h = $this->display->getImageHeight())
        ) {
            $style = 'width:' . $w . 'px; height:' . $h . 'px;';

            return $style;
        }

        return '';
    }

    public function getPlaceholderCode(): string
    {
        return $this->display->getPlaceholder()
            ? $this->display->getPlaceholder()->getCode()
            : '';
    }

    public function getDisplayTitle(): string
    {
        $title = $this->display->getTitle();

        return $this->productDataHelper->getProduct()
            ? $this->productDataHelper->processVariables($title)
            : $title;
    }

    public function getDisplayDescription(): string
    {
        $description = $this->display->getDescription();

        return $this->productDataHelper->getProduct()
            ? $this->productDataHelper->processVariables($description)
            : $description;
    }

    public function getDisplayImageHtml(): string
    {
        if (!$this->getImageSizeHtml()) {
            return '';
        }

        $imageHtmlStyles = 'background:url(' . $this->display->getImageUrl()
            . '); background-repeat: no-repeat; ' . $this->getImageSizeHtml()
            . ' display: flex; justify-content: center; align-items: center; text-align: center';

        $imageHtml = '<div class="label-image" style="' . $imageHtmlStyles . '">'
            . '<span class="label-title">' . $this->getDisplayTitle() . '</span>'
            . '</div>';

        return $imageHtml;
    }

    public function getDisplayHtml(): string
    {
        $displayData = [
            'title'       => $this->getDisplayTitle(),
            'description' => $this->getDisplayDescription(),
            'image_url'   => $this->display->getImageUrl(),
            'image'       => $this->getDisplayImageHtml(),
            'url'         => $this->prepareDisplayUrl()
        ];
        $html = '<span class="' . $this->getDisplayCssClasses() . '">'
            . $this->getLabelTemplate()->getHtmlTemplate()
            . '</span>';

        return $this->contentService->processHtmlContent(
            $html,
            ['label' => new DataObject($displayData)]
        );
    }

    private function prepareDisplayUrl(): string
    {
        $url = trim((string)$this->display->getUrl());

        if (!$url) {
            return '#';
        }

        if (strpos($url, 'http') === 0 || strpos($url, '//') === 0) {
            return $url;
        }

        return $this->getUrl() . ltrim($url, '/');
    }

    public function getDisplayCssClasses(): string
    {
        $templateCode = $this->getLabelTemplate() ? $this->getLabelTemplate()->getCode() : 'none';

        return 'cataloglabel-' . $this->getPlaceholderCode()
            . ' cataloglabel-' . $this->display->getType()
            . ' cataloglabel-display-' . $this->display->getId()
            . ' cataloglabel-template-' . $templateCode;
    }

    public function isProductList(): bool
    {
        if (!is_null($this->isProductListPage)) {
            return $this->isProductListPage;
        }

        $parentBlock = $this->getParentBlock();

        if ($parentBlock && !is_null($parentBlock->isProductList())) {
            $this->isProductListPage = $parentBlock->isProductList();

            return $this->isProductListPage;
        }

        $productListActions = [
            'catalog_category_view',
            'catalogsearch_result_index',
            'catalogsearch_advanced_result',
            'cms_index_index'
        ];

        $this->isProductListPage = in_array($this->getRequest()->getFullActionName(), $productListActions);

        return $this->isProductList();
    }

    public function getMergedStylesOutput(): string
    {
        $style = $this->getLabelTemplate() ? trim($this->getLabelTemplate()->getStyle()) : '';
        $style .= $this->display->getStyle() ? '.cataloglabel-display-' . $this->display->getId() . ' { ' . trim($this->display->getStyle()) . ' }' : '';

        if (!$style) {
            return '';
        }

        $style = '.mst-cataloglabel-preview { ' . $style . ' }';

        try {
            $processor = new \Less_Parser();
            $processor->parse($style);

            $style = $processor->getCss();

            return '<style>' . $style . '</style>';
        } catch (\Exception $e) {
            return '<b style="font-size: 2rem; color: red; position: absolute; z-index: 5; text-align: center">STYLE ERROR<b/>';
        }
    }
}
