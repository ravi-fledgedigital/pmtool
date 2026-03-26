<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model;

use Amasty\Base\Model\ConfigProviderAbstract;

class ConfigProvider extends ConfigProviderAbstract
{
    /**
     * @var string
     */
    protected $pathPrefix = 'amcustomtabs/';

    /**#@+
     * Constants defined for xpath of system configuration
     */
    public const XPATH_ENABLED = 'general/enabled';
    public const ALLOW_EDIT_DEFAULT_TABS = 'general/allow_default';
    public const ACCORDION_VIEW = 'general/accordion_view';
    public const OPEN_ALL_TABS = 'general/open_all_tabs';

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isSetFlag(self::XPATH_ENABLED);
    }

    /**
     * @return bool
     */
    public function isChangeDefaultTabsAllowed(): bool
    {
        return $this->isSetFlag(self::ALLOW_EDIT_DEFAULT_TABS);
    }

    /**
     * @return bool
     */
    public function isAccordionView(): bool
    {
        return $this->isSetFlag(self::ACCORDION_VIEW) && $this->isEnabled();
    }

    /**
     * @return bool
     */
    public function getIsOpenAllTabs(): bool
    {
        return $this->isSetFlag(self::OPEN_ALL_TABS) && $this->isAccordionView();
    }
}
