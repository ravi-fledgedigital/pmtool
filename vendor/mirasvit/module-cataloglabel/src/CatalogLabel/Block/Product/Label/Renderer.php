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
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\Template;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Repository\PlaceholderRepository;

class Renderer extends Template
{
    protected $_template = "Mirasvit_CatalogLabel::labelRenderer.phtml";

    private $placeholderRepository;

    private $blockFactory;

    /** @var ProductInterface|null */
    private $product;

    private $type = 'list';

    public function __construct(
        BlockFactory $blockFactory,
        PlaceholderRepository $placeholderRepository,
        Template\Context $context,
        array $data = []
    ) {
        $this->blockFactory          = $blockFactory;
        $this->placeholderRepository = $placeholderRepository;

        parent::__construct($context, $data);
    }

    public function setProduct(ProductInterface $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }

    public function getPlaceholders(): ?array
    {
        return $this->placeholderRepository->getPositionedItems();
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPlaceholderHtml(PlaceholderInterface $placeholder): string
    {
        return $this->blockFactory->createBlock(Placeholder::class)
            ->setPlaceholder($placeholder)
            ->setType($this->type)
            ->setProduct($this->getProduct())
            ->toHtml();
    }
}
