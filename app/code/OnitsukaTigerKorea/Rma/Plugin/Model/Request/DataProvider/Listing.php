<?php

namespace OnitsukaTigerKorea\Rma\Plugin\Model\Request\DataProvider;

use Magento\Framework\Api\Filter;
use Amasty\Rma\Api\Data\RequestInterface;

class Listing
{
    /**
     * @param \Amasty\Rma\Model\Request\DataProvider\Listing $subject
     * @param Filter $filter
     * @return Filter[]
     */
    public function beforeAddFilter(\Amasty\Rma\Model\Request\DataProvider\Listing $subject, Filter $filter): array
    {
        if ($filter->getField() == RequestInterface::STORE_ID) {
            $filter->setField('main_table.' . RequestInterface::STORE_ID);
        }
        return [$filter];
    }
}
