<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Setup\Patch\Data;

use Amasty\CustomTabs\Model\Tabs\Loader;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class TabsLoader implements DataPatchInterface
{
    /**
     * @var Loader
     */
    private $loader;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        State $appState,
        Loader $loader
    ) {
        $this->loader = $loader;
        $this->appState = $appState;
    }

    /**
     * @inheirtDoc
     */
    public function apply(): self
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, [$this->loader, 'execute']);

        return $this;
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
