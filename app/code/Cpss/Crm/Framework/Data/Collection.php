<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Cpss\Crm\Framework\Data;

/**
 * Data collection
 *
 * TODO: Refactor use of \Magento\Framework\Option\ArrayInterface in library.
 *
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Data\Collection {

    protected $lastPage = null;

    public function setLastPageNumber($lastPage)
    {
        $this->lastPage = $lastPage;
    }

    /**
     * Retrieve collection last page number
     *
     * @return int
     */
    public function getLastPageNumber()
    {
        if ($this->lastPage != null) {
            return $this->lastPage;
        }

        $collectionSize = (int)$this->getSize();
        if (0 === $collectionSize) {
            return 1;
        } elseif ($this->_pageSize) {
            return (int)ceil($collectionSize / $this->_pageSize);
        }

        return 1;
    }
    
}
