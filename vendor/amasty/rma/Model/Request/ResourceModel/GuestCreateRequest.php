<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\ResourceModel;

use Amasty\Rma\Api\Data\GuestCreateRequestInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class GuestCreateRequest extends AbstractDb
{
    public const TABLE_NAME = 'amasty_rma_guest_create_request';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, GuestCreateRequestInterface::CREATE_ID);
    }

    /**
     * @param string $secretKey
     *
     * @return bool|int
     */
    public function findOrderBySecretKey($secretKey)
    {
        $select = $this->getConnection()->select()->from(['create_guest_request' => $this->getMainTable()])
            ->where('create_guest_request.' . GuestCreateRequestInterface::SECRET_CODE . ' = ?', $secretKey)
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns('create_guest_request.' . GuestCreateRequestInterface::ORDER_ID);

        if ($orderId = $this->getConnection()->fetchOne($select)) {
            return (int)$orderId;
        }

        return false;
    }

    /**
     * @param string $secretKey
     *
     * @return void
     */
    public function deleteBySecretKey($secretKey)
    {
        if (!empty($secretKey)) {
            $this->getConnection()->delete(
                $this->getMainTable(),
                [GuestCreateRequestInterface::SECRET_CODE . ' = ?' => $secretKey]
            );
        }
    }
}
