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


namespace Mirasvit\CatalogLabel\Block\Adminhtml;


use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\DataObject;
use Mirasvit\Core\Block\Adminhtml\AbstractMenu;


class Menu extends AbstractMenu
{
    public function __construct(Context $context)
    {
        $this->visibleAt(['cataloglabel']);

        parent::__construct($context);
    }

    protected function buildMenu()
    {
        $this->addItem([
            'resource' => 'Mirasvit_CatalogLabel::cataloglabel_labels',
            'title'    => (string)__('Manage Labels'),
            'url'      => $this->urlBuilder->getUrl('cataloglabel/label'),
        ])->addItem([
            'resource' => 'Mirasvit_CatalogLabel::cataloglabel_placeholders',
            'title'    => (string)__('Manage Placeholders'),
            'url'      => $this->urlBuilder->getUrl('cataloglabel/placeholder'),
        ])->addItem([
            'resource' => 'Mirasvit_CatalogLabel::cataloglabel_template',
            'title'    => (string)__('Manage Templates'),
            'url'      => $this->urlBuilder->getUrl('cataloglabel/template'),
        ]);
    }
}
