<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TabsSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Amasty\CustomTabs\Api\Data\TabsInterface[]
     */
    public function getItems();

    /**
     * @param \Amasty\CustomTabs\Api\Data\TabsInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
