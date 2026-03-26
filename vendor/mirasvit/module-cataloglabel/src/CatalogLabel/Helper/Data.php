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


namespace Mirasvit\CatalogLabel\Helper;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Mirasvit\CatalogLabel\Block\Product\Label as ProductLabelBlock;


class Data extends AbstractHelper
{
    protected $blockFactory;

    protected $context;

    private $request;

    public function __construct(
        BlockFactory $blockFactory,
        Context $context,
        Http $request
    ) {
        $this->blockFactory = $blockFactory;
        $this->context      = $context;

        parent::__construct($context);

        $this->request = $request;
    }

    public function getProductLabelsHtml(
        ProductInterface $product,
        string $type
    ) {
        return $this->blockFactory->createBlock(ProductLabelBlock\Renderer::class)
            ->setProduct($product)
            ->setType($type)
            ->toHtml();
    }

    public function getProductHtml(
        string $placeholderCode,
        ProductInterface $product,
        string $type,
        string $template,
        ?int $width = null,
        ?int $height = null
    ): string {
        return $this->blockFactory
                ->createBlock(ProductLabelBlock::class)
                ->setType($type)
                ->setTemplate('Mirasvit_CatalogLabel::product/'.$template.'.phtml')
                ->setPlaceholderCode($placeholderCode)
                ->setProduct($product)
                ->setWidth($width)
                ->setHeight($height)
                ->toHtml();
    }

    public function getProductListHtml(
        string $placeholderCode,
        ProductInterface $product,
        ?int $width = null,
        ?int $height = null
    ): string {
        return $this->getProductHtml($placeholderCode, $product, 'list', 'list', $width, $height);
    }

    public function getProductViewHtml(
        string $placeholderCode,
        ProductInterface $product,
        ?int $width = null,
        ?int $height = null
    ): string {
        return $this->getProductHtml($placeholderCode, $product, 'view', 'view', $width, $height);
    }

    public function getFullActionCode(): string
    {
        return strtolower(
            $this->request->getModuleName().
            '_'.$this->request->getControllerName().
            '_'.$this->request->getActionName()
        );
    }
}
