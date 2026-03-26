<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Layout;

/**
 * Class GeneratorPool
 */
class GeneratorPool extends \Magento\Framework\View\Layout\GeneratorPool
{
    /**
     * @inheritdoc
     */
    protected function addGenerators(array $generators)
    {
        $this->generators = [];
    }
}
