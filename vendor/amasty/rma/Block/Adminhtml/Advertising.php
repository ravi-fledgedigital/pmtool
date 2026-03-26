<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml;

use Amasty\Base\Model\Feed\ExtensionsProvider;
use Amasty\Base\Model\ModuleInfoProvider;
use Magento\Backend\Block\Template;
use Magento\Framework\Module\Manager;

class Advertising extends Template
{
    public const SEO_PARAMS = '?utm_source=extension&utm_medium=backend&utm_campaign=from_core_to_export_orders_m2';

    /**
     * @var string
     */
    protected $_template = 'Amasty_Rma::advertising.phtml';

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var ExtensionsProvider
     */
    private $extensionsProvider;

    /**
     * @var ModuleInfoProvider
     */
    private $moduleInfoProvider;

    public function __construct(
        Template\Context $context,
        Manager $moduleManager,
        ExtensionsProvider $extensionsProvider,
        ModuleInfoProvider $moduleInfoProvider,
        array $data = []
    ) {
        $this->moduleManager = $moduleManager;
        $this->extensionsProvider = $extensionsProvider;
        $this->moduleInfoProvider = $moduleInfoProvider;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if ($this->moduleManager->isEnabled('Amasty_OrderExport')) {
            return '';
        }

        return parent::toHtml();
    }

    public function getLink(): string
    {
        $link = $this->extensionsProvider->getFeedModuleData('Amasty_OrderExport')['url'] ?? '';

        if ($link && !$this->moduleInfoProvider->isOriginMarketplace()) {
            $link .= self::SEO_PARAMS;
        }

        return $link;
    }
}
