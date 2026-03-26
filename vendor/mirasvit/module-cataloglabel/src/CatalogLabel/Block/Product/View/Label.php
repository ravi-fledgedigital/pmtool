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


namespace Mirasvit\CatalogLabel\Block\Product\View;


use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Helper\Data;
use Magento\Catalog\Block\Product\View\AbstractView;
use Mirasvit\CatalogLabel\Repository\PlaceholderRepository;


class Label extends AbstractView
{
    private $dataHelper;

    private $placeholderRepository;

    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        PlaceholderRepository $placeholderRepository,
        Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $arrayUtils, $data);

        $this->placeholderRepository = $placeholderRepository;
        $this->dataHelper            = $dataHelper;
    }

    /**
     * @param string $html
     * @param ProductInterface|null $product
     * @param string $code
     * @return mixed|string
     */
    protected function _afterToHtml($html, $product = null, $code = 'view')
    {
        if (!$product) {
            $product = $this->getProduct();
        }

        $html .= $this->dataHelper->getProductLabelsHtml($product, $code);

        return $html;
    }

    public function getAdditionalHtmlManually(ProductInterface $product): array
    {
        return $this->_afterToHtml('', $product, 'list');
    }
}
