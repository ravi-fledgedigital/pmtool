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
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Registry;

class Form extends Action
{
    private $layoutFactory;
    private $rawFactory;
    private $registry;

    public function __construct(
        Action\Context $context,
        LayoutFactory $layoutFactory,
        RawFactory $rawFactory,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->layoutFactory = $layoutFactory;
        $this->rawFactory = $rawFactory;
        $this->registry = $registry;
    }

    public function execute()
    {
        $attributeId = (int)$this->getRequest()->getParam('attribute_id');

        if ($attributeId) {
            $attribute = $this->_objectManager
                ->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
                ->load($attributeId);
            $this->registry->register('entity_attribute', $attribute);
        }

        $this->registry->register('mst_attribute_editor_page', true);

        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->layoutFactory->create();

        $block = $resultLayout->getLayout()
            ->createBlock(\Mirasvit\LayeredNavigation\Block\Adminhtml\Filter\FormContainer::class);

        $resultRaw = $this->rawFactory->create();
        $resultRaw->setContents($block->toHtml());

        return $resultRaw;
    }
}
