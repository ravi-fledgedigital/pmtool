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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Observer;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Catalog\Block\Adminhtml\Category\Tab\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Mirasvit\Sorting\Block\Adminhtml\Grid\Column\Filter\PinToTop as PinToTopFilter;
use Mirasvit\Sorting\Block\Adminhtml\Grid\Column\Renderer\PinToTop;
use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * Adds "Pin to top" column to category products grid.
 */
class AddPinToTopColumnObserver implements ObserverInterface
{
    private $pinnedProductService;

    private $moduleManager;

    public function __construct(
        PinnedProductService $pinnedProductService,
        ModuleManager        $moduleManager
    ) {
        $this->pinnedProductService = $pinnedProductService;
        $this->moduleManager        = $moduleManager;
    }

    public function execute(Observer $observer): void
    {
        $grid = $observer->getEvent()->getData('grid');

        if (!$grid instanceof Product) {
            return;
        }

        $categoryId = (int)$grid->getRequest()->getParam('id', 0);

        if ($categoryId && !$grid->getCategory()->getProductsReadonly()) {
            $grid->addColumn(
                'pin_to_top',
                [
                    'type'                      => 'options',
                    'name'                      => 'pin_to_top',
                    'field_name'                => 'pin_to_top',
                    'values'                    => $this->pinnedProductService->getProductIds($categoryId),
                    'options'                   => [1 => __('Yes'), 0 => __('No')],
                    'index'                     => 'pin_to_top',
                    'align'                     => 'center',
                    'header'                    => __('Pin to top'),
                    'header_css_class'          => 'col-select col-massaction',
                    'column_css_class'          => 'col-select col-massaction',
                    'header_export'             => false,
                    'renderer'                  => PinToTop::class,
                    'sortable'                  => !$this->moduleManager->isEnabled('Mirasvit_Merchandiser'),
                    'filter_condition_callback' => [$this, 'filterByPinToTop'],
                    'filter'                    => PinToTopFilter::class,
                ]
            );
        }
    }

    public function filterByPinToTop(AbstractDb $collection, Column $column): void
    {
        $value = $column->getFilter()->getValue();

        if ($value === null || $value === '') {
            return;
        }

        if ($value === '1') {
            $collection->addFieldToFilter('pin_to_top', ['notnull' => true]);
        } else {
            $collection->addFieldToFilter('pin_to_top', ['null' => true]);
        }
    }
}
