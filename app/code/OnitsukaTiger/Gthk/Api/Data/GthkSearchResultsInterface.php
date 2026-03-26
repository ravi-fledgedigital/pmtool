<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Gthk\Api\Data;

interface GthkSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Gthk list.
     * @return \OnitsukaTiger\Gthk\Api\Data\GthkInterface[]
     */
    public function getItems();

    /**
     * Set Order_id list.
     * @param \OnitsukaTiger\Gthk\Api\Data\GthkInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

