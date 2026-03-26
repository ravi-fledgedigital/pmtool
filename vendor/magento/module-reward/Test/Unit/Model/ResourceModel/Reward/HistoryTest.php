<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\ResourceModel\Reward;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime;
use Magento\Reward\Model\ResourceModel\Reward\History;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HistoryTest extends TestCase
{
    /**
     * @var DateTime|MockObject
     */
    private DateTime $dateTime;

    /**
     * @var Context|MockObject
     */
    private Context $context;

    /**
     * @var AdapterInterface|AdapterInterface&MockObject|MockObject
     */
    private AdapterInterface $connection;

    /**
     * @var History
     */
    private History $_model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->dateTime = $this->createMock(DateTime::class);
        $this->context = $this->createMock(Context::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $resources = $this->createMock(ResourceConnection::class);
        $resources->expects($this->any())->method('getConnection')->willReturn($this->connection);
        $resources->expects($this->any())->method('getTableName')->willReturn('table');
        $this->context->expects($this->any())->method('getResources')->willReturn($resources);

        $this->_model = new History($this->context, $this->dateTime, 'connection_name');

        parent::setUp();
    }

    /**
     * @return void
     */
    public function testIsExistHistoryUpdate()
    {
        $this->assertEquals(false, $this->_model->isExistHistoryUpdate(1, 1, 1, null));
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    public function testExpirePoints(): void
    {
        $websiteId = 1;
        $expiryType = 'static';
        $limit = 5;
        $date = date('Y-m-d H:i:s');
        $expiredHistoryRow = [
            'history_id' => 1,
            'reward_id' => 1,
            'website_id' => $websiteId,
            'store_id' => 1,
            'action' => '10',
            'entity' => '10',
            'points_balance' => '210',
            'points_delta' => '10',
            'points_used' => '0',
            'points_voided' => '0',
            'currency_amount' => '2100.0000',
            'currency_delta' => '100.0000',
            'base_currency_code' => 'USD',
            'additional_data' => '{"increment_id":"000000010","rate":{"points":"1","currency_amount":"10.0000",
            "direction":"1","currency_code":"USD"}}',
            'comment' => null,
            'created_at' => '2023-09-06 07:07:19',
            'expired_at_static' => '2023-09-06 06:07:19',
            'expired_at_dynamic' => '2023-09-06 07:07:19',
            'is_expired' => '0',
            'is_duplicate_of' => null,
            'notification_sent' => '0',
            'current_balance' => '85'
        ];

        $this->dateTime->expects($this->any())->method('formatDate')->willReturn($date);
        $select = $this->createMock(Select::class);
        $select->expects($this->once())->method('from')->willReturn($select);
        $select->expects($this->once())->method('joinInner')->willReturn($select);
        $select->expects($this->exactly(5))->method('where')->willReturn($select);
        $select->expects($this->once())->method('limit')->willReturn($select);
        $this->connection->expects($this->once())->method('select')->willReturn($select);
        $this->connection->expects($this->exactly(2))->method('update');
        $duplicateRow = $expiredHistoryRow;
        $duplicateRow['created_at'] = $date;
        $duplicateRow['expired_at_static'] = null;
        $duplicateRow['expired_at_dynamic'] = null;
        $duplicateRow['is_expired'] = '1';
        $duplicateRow['is_duplicate_of'] = $duplicateRow['history_id'];
        $duplicateRow['points_delta'] = -10;
        $duplicateRow['points_balance'] = 75;
        $duplicateRow['points_used'] = 0;
        unset($duplicateRow['current_balance']);
        unset($duplicateRow['history_id']);

        $this->connection->expects($this->once())->method('insertMultiple')->with(
            'table',
            [$duplicateRow]
        );
        $statement = $this->createMock(\Zend_Db_Statement_Interface::class);
        $statement->expects($this->exactly(2))->method('fetch')
            ->willReturnOnConsecutiveCalls($expiredHistoryRow, false);
        $this->connection->expects($this->once())
            ->method('query')
            ->with($select, [':website_id' => $websiteId, ':time_now' => $date])
            ->willReturn($statement);

        $this->assertSame($this->_model, $this->_model->expirePoints($websiteId, $expiryType, $limit));
    }
}
