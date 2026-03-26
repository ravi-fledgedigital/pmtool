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

namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Filter;

use Magento\Backend\Block\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\LayeredNavigation\Block\Adminhtml\Attribute\Edit\Tab\Navigation as NavigationTab;

class FormContainer extends Template
{
    protected $_template = 'Mirasvit_LayeredNavigation::attribute/form.phtml';

    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context);
    }

    protected function _prepareLayout()
    {
        $this->addChild(
            'navigation_form',
            NavigationTab::class
        );
        return parent::_prepareLayout();
    }

    public function getNavigationFormHtml(): string
    {
        return $this->getChildHtml('navigation_form');
    }
}
