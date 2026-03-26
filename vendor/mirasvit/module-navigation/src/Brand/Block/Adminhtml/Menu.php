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

namespace Mirasvit\Brand\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Service\BrandAttributeService;
use Mirasvit\Core\Block\Adminhtml\AbstractMenu;

class Menu extends AbstractMenu
{
    protected $urlBuilder;

    private   $brandAttributeService;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Context $context,
        Config $config,
        BrandAttributeService $brandAttributeService
    ) {
        $this->visibleAt(['brand']);
        $this->config                = $config;
        $this->brandAttributeService = $brandAttributeService;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildMenu()
    {
        $this->addItem([
            'resource' => 'Mirasvit_Brand::brand_brand',
            'title'    => __('Brand Pages'),
            'url'      => $this->urlBuilder->getUrl('brand/brand/index'),
        ]);

        $this->addItem([
            'resource' => 'Mirasvit_Brand::brand_settings',
            'title'    => __('Brand Settings'),
            'url'      => $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/brand'),
        ]);

        $this->addSeparator();

        $this->addItem([
            'resource' => 'Mirasvit_Brand::brand_get_support',
            'title'    => __('Get Support'),
            'url'      => 'https://mirasvit.com/support/',
        ]);

        return $this;
    }
}
