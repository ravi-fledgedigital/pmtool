<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_Smtp
 */


namespace Aitoc\Smtp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

class Log extends AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(
            \Aitoc\Smtp\Setup\InstallSchema::AITOC_SMTP_LOG_TABLE_NAME,
            \Aitoc\Smtp\Model\Log::LOG_ID_TYPE_FIELD
        );
    }
}