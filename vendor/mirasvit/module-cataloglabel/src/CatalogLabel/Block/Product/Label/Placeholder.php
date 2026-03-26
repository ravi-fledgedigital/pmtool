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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\BlockFactory;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Block\Product\Label\Display as DisplayBlock;
use Mirasvit\CatalogLabel\Model\ConfigProvider;
use Mirasvit\CatalogLabel\Model\ResourceModel\Index\Collection;
use Mirasvit\CatalogLabel\Model\ResourceModel\Index\CollectionFactory;
use Mirasvit\CatalogLabel\Repository\DisplayRepository;
use Mirasvit\CatalogLabel\Repository\PlaceholderRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Placeholder extends Template
{
    protected $_template = 'Mirasvit_CatalogLabel::label/placeholder.phtml';

    private $productRepository;

    private $displayRepository;

    private $placeholderRepository;

    private $blockFactory;

    private $indexCollectionFactory;

    /** @var PlaceholderInterface|null */
    private $placeholder;

    private $customerSession;

    /** @var ProductInterface|null */
    private $product;

    private $context;

    private $config;

    private $type = 'list';

    public function __construct(
        ProductRepositoryInterface $productRepository,
        DisplayRepository $displayRepository,
        PlaceholderRepository $placeholderRepository,
        CollectionFactory $indexCollectionFactory,
        BlockFactory $blockFactory,
        Session $customerSession,
        Template\Context $context,
        ConfigProvider $config,
        array $data = []
    ) {
        $this->productRepository      = $productRepository;
        $this->displayRepository      = $displayRepository;
        $this->placeholderRepository  = $placeholderRepository;
        $this->blockFactory           = $blockFactory;
        $this->indexCollectionFactory = $indexCollectionFactory;
        $this->customerSession        = $customerSession;
        $this->config                 = $config;
        $this->context                = $context;

        parent::__construct($context, $data);
    }

    public function setPlaceholder(PlaceholderInterface $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function setPlaceholderByCode(string $code): self
    {
        if ($this->placeholder && $this->placeholder->getCode() == $code) {
            return $this;
        }

        $this->placeholder = $this->placeholderRepository->getByCode($code);

        return $this;
    }

    public function getPlaceholder(): ?PlaceholderInterface
    {
        return $this->placeholder;
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

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getClassList(): string
    {
        $placeholder = $this->getPlaceholder();

        if (!$placeholder) {
            return '';
        }

        return 'cataloglabel cataloglabel-placeholder placeholder-' . $placeholder->getCode()
            . ' position-' . $placeholder->getPosition() . ' direction-' . $placeholder->getLabelsDirection();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array|DisplayInterface[]
     */
    public function getDisplays(): array
    {
        if (!$this->getPlaceholder() || !$this->getProduct()) {
            return [];
        }

        if ($this->isIgnoredPage()) {
            return [];
        }

        $displayIds = $this->getDisplayIds($this->product);

        if (!$displayIds) {
            $displayIds = [];
        }

        if ($this->config->isApplyForParent() && $this->product->getTypeId() == Configurable::TYPE_CODE) {
            $productIds = $this->product->getTypeInstance()->getChildrenIds($this->product->getId());

            if (!empty($productIds[0])) {
                foreach ($productIds[0] as $productId) {
                    $product = $this->productRepository->getById($productId);
                    $childDisplayIds = $this->getDisplayIds($product);

                    if (!empty($childDisplayIds)) {
                        $displayIds = array_unique(array_merge($displayIds, $childDisplayIds));
                    }
                }
            }
        }

        if (!count($displayIds)) {
            return [];
        }

        return $this->displayRepository->getByData(
            [
                DisplayInterface::PLACEHOLDER_ID => $this->getPlaceholder()->getId(),
                DisplayInterface::TYPE           => $this->type,
                DisplayInterface::ID             => $displayIds
            ],
            (int)$this->getPlaceholder()->getLabelsLimit()
        );
    }

    public function getDisplayHtml(DisplayInterface $display): string
    {
        /** @var DisplayBlock $displayBlock */
        $displayBlock = $this->blockFactory->createBlock(DisplayBlock::class);

        return $displayBlock->setDisplay($display)
            ->setProduct($this->getProduct())
            ->toHtml();
    }

    public function isIgnoredPage(): bool
    {
        return $this->config->isIgnoredPage($this->context->getRequest()->getFullActionName(), $this->getCurrentUrl());
    }

    private function getCurrentUrl(): string
    {
        $baseUrl    = $this->context->getUrlBuilder()->getBaseUrl();
        $currentUrl = $this->context->getUrlBuilder()->getCurrentUrl();

        return str_replace($baseUrl, '', $currentUrl);
    }

    public function isApplyLabelForChildProducts(): bool
    {
        return $this->config->isApplyForChild();
    }

    private function getDisplayIds(ProductInterface $product): ?array
    {
        $displayIds = $product->getData('mst_product_display_ids');

        if (!$displayIds) {
            $customerGroupId = $this->customerSession->getCustomerGroupId() ?? 0;

            /** @var Collection $indexCollection */
            $indexCollection = $this->indexCollectionFactory->create();
            $indexCollection->addFieldToFilter('product_id', $product->getId())
                ->addFieldToFilter('store_id', $product->getStoreId())
                ->addFieldToFilter('customer_groups', ['finset' => $customerGroupId])
                ->setOrder('sort_order');

            $displayIds = [];

            foreach ($indexCollection as $item) {
                $displayIds[] = $item->getData('display_ids');
            }

            $displayIds = implode(',', $displayIds);
        } else {
            $orderDisplays = array_filter(explode('|', $displayIds));
            $withOrders    = [];

            foreach ($orderDisplays as $od) {
                $od = explode('-', $od);
                if (!isset($withOrders[$od[0]])) {
                    $withOrders[$od[0]] = [];
                }

                $d = array_merge($withOrders[$od[0]], explode(',', $od[1]));

                $withOrders[$od[0]] = $d;
            }

            krsort($withOrders);

            foreach ($withOrders as $k => $v) {
                asort($v);
                $withOrders[$k] = implode(',', $v);
            }

            $displayIds = implode(',', array_values($withOrders));
        }

        return $displayIds ? array_filter(array_unique(explode(',', $displayIds))) : null;
    }
}
