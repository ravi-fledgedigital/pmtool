<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\ResourceModel\Grid;

use Amasty\Rma\Api\Data\RequestInterface;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    public const DAYS_EXPRESSION = 'datediff(main_table.' . RequestInterface::MODIFIED_AT
        . ', main_table.' . RequestInterface::CREATED_AT . ')';

    /**
     * @return Collection
     */
    public function addLeadTime()
    {
        $this->getSelect()->columns(
            ['days' => new \Zend_Db_Expr(self::DAYS_EXPRESSION)]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        //need for csv grid export, current page and batch size sets in SearchCriteria
        //but no automatic applying for SearchResult, so need to reload items of collection
        $this->_setIsLoaded(false);
        $this->_items = [];
        $searchCriteria = $this->getSearchCriteria();
        $this->setPageSize($searchCriteria->getPageSize());
        $this->setCurPage($searchCriteria->getCurrentPage());

        return parent::getItems();
    }
}
