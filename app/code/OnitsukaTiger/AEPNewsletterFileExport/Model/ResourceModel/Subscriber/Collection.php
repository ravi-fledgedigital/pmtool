<?php

namespace OnitsukaTiger\AEPNewsletterFileExport\Model\ResourceModel\Subscriber;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Newsletter subscribers collection
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor
     *
     * Configures collection
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \OnitsukaTiger\AEPNewsletterFileExport\Model\Subscriber::class,
            \OnitsukaTiger\AEPNewsletterFileExport\Model\ResourceModel\Subscriber::class
        );
    }
}
