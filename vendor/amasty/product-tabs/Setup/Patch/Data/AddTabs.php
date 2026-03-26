<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Setup\Patch\Data;

use Amasty\Base\Helper\Deploy;
use Amasty\CustomTabs\Setup\Operation\AddPredefinedTab;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddTabs implements DataPatchInterface
{
    /**
     * @var AddPredefinedTab
     */
    private $addPredefinedTab;

    /**
     * @var Deploy
     */
    private $pubDeployer;

    public function __construct(
        AddPredefinedTab $addPredefinedTab,
        Deploy $pubDeployer
    ) {
        $this->addPredefinedTab = $addPredefinedTab;
        $this->pubDeployer = $pubDeployer;
    }

    /**
     * @inheirtDoc
     */
    public function apply(): self
    {
        $this->addPredefinedTab->execute();
        $this->pubDeployer->deployFolder(__DIR__ . '/../../../pub');

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
        return [
            TabsLoader::class,
            AddTabAnchors::class
        ];
    }
}
