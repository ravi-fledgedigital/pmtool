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

namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Filter;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Mirasvit\LayeredNavigation\Block\Adminhtml\Filter\FiltersManager;

class Reload extends Action
{
    private JsonFactory $jsonFactory;
    private LayoutFactory $layoutFactory;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->layoutFactory = $layoutFactory;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $layout = $this->layoutFactory->create();

        /** @var FiltersManager $filtersOrderBlock */
        $filtersOrderBlock = $layout->createBlock(FiltersManager::class);

        $sidebarHtml = $filtersOrderBlock->renderFilterListHtml('sidebar');
        $horizontalHtml = $filtersOrderBlock->renderFilterListHtml('horizontal');

        return $resultJson->setData([
            'success' => true,
            'sidebarHtml' => $sidebarHtml,
            'horizontalHtml' => $horizontalHtml,
        ]);
    }
}
