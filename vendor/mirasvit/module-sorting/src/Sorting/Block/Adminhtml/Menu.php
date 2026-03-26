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

namespace Mirasvit\Sorting\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Mirasvit\Core\Block\Adminhtml\AbstractMenu;

class Menu extends AbstractMenu
{
    protected $urlBuilder;

    public function __construct(
        Context $context
    ) {
        $this->visibleAt(['sorting']);

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildMenu()
    {
        $this->addItem([
            'resource' => 'Mirasvit_Sorting::sorting',
            'title'    => __('Sorting Criteria'),
            'url'      => $this->urlBuilder->getUrl('sorting/criterion'),
        ]);

        $this->addItem([
            'resource' => 'Mirasvit_Sorting::sorting',
            'title'    => __('Ranking Factors'),
            'url'      => $this->urlBuilder->getUrl('sorting/rankingFactor'),
        ]);

        $this->addItem([
            'resource' => 'Mirasvit_Sorting::sorting',
            'title'    => __('Sorting Settings'),
            'url'      => $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/mst_sorting'),
        ]);

        return $this;
    }
}
