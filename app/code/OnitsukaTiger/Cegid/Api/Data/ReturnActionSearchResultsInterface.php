<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Api\Data;

interface ReturnActionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get ReturnAction list.
     * @return \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface[]
     */
    public function getItems(): array;

    /**
     * Set number list.
     * @param \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface[] $items
     * @return $this
     */
    public function setItems(array $items): static;
}
