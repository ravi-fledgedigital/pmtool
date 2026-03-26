<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Test\Unit\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Logging\Model\Event\ChangesFactory;
use Magento\Logging\Model\Processor;
use Magento\Logging\Model\ResourceModel\Event;
use Magento\Logging\Model\ResourceModel\Event\Changes;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;
use Magento\LoginAsCustomerLogging\Model\LogValidation;
use Magento\LoginAsCustomerLogging\Observer\LogSaveCustomerObserver;
use Magento\Logging\Model\Event as EventModel;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Logging\Model\ResourceModel\Event as EventLogger;
use Magento\Logging\Model\Event\Changes as EventChanges;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LogSaveCustomerObserverTest extends TestCase
{
    /**
     * @var LogSaveCustomerObserver
     */
    private LogSaveCustomerObserver $logSaveCustomerObserver;

    /**
     * @var GetEventForLogging|MockObject
     */
    private $getEventForLogging;

    /**
     * @var Session|MockObject
     */
    private $sessionMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    /**
     * @var Changes|MockObject
     */
    private $changesMock;

    /**
     * @var LogValidation|MockObject
     */
    private LogValidation $logValidationMock;

    /**
     * @var GetLoggedAsCustomerAdminIdInterface|MockObject
     */
    private $getLoggedAsCustomerAdminIdMock;

    /**
     * @var ChangesFactory|MockObject
     */
    private $changesFactoryMock;

    /**
     * @var Processor|MockObject
     */
    private $processorMock;

    /**
     * @var Observer|MockObject
     */
    private $observerMock;

    /**
     * @var EventModel|MockObject
     */
    private $eventModelMock;

    /**
     * @var Customer|MockObject
     */
    private $customerMock;

    /**
     * @var EventLogger|MockObject
     */
    private $eventLogger;

    /**
     * @var EventChanges|MockObject
     */
    private $eventChangesMock;

    protected function setUp(): void
    {
        $this->getEventForLogging = $this->createMock(GetEventForLogging::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->changesMock = $this->getMockBuilder(Changes::class)
            ->onlyMethods(['save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logValidationMock = $this->createMock(LogValidation::class);
        $this->getLoggedAsCustomerAdminIdMock = $this->createMock(GetLoggedAsCustomerAdminIdInterface::class);
        $this->changesFactoryMock = $this->getMockBuilder(ChangesFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->processorMock = $this->createMock(Processor::class);
        $this->eventLogger = $this->createMock(EventLogger::class);

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventMock = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerDataObject', 'getOrigCustomerDataObject'])
            ->getMock();

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);

        $this->eventChangesMock = $this->getMockBuilder(EventChanges::class)
            ->addMethods(['setOriginalData', 'setResultData', 'setEventId', 'setSourceName', 'setSourceId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logSaveCustomerObserver = new LogSaveCustomerObserver(
            $this->getEventForLogging,
            $this->sessionMock,
            $this->eventLogger,
            $this->changesMock,
            $this->logValidationMock,
            $this->getLoggedAsCustomerAdminIdMock,
            $this->changesFactoryMock,
            $this->processorMock
        );
    }

    /**
     * Test `execute` method.
     *
     * @param array $customerArray
     * @param array|null $customerOrigArray
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $customerArray, array|null $customerOrigArray): void
    {
        $this->logValidationMock->expects($this->once())->method('shouldBeLogged')->willReturn(true);
        $this->eventModelMock = $this->createMock(EventModel::class);
        $this->getEventForLogging->expects($this->once())
            ->method('execute')
            ->willReturn($this->eventModelMock);
        $this->sessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);
        $this->customerMock = $this->createMock(Customer::class);
        $this->sessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $customerDataObject = $this->getMockBuilder(CustomerInterface::class)
            ->addMethods(['__toArray'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $customerDataObject->expects($this->any())
            ->method('__toArray')
            ->willReturn($customerArray);

        $this->eventMock->expects($this->any())
            ->method('getCustomerDataObject')
            ->willReturn($customerDataObject);

        if ($customerOrigArray === null) {
            $this->eventMock->expects($this->any())
                ->method('getOrigCustomerDataObject')
                ->willReturn($customerOrigArray);
        } else {
            $this->eventMock->expects($this->any())
                ->method('getOrigCustomerDataObject')
                ->willReturn($customerDataObject);
        }

        $this->eventMock->expects($this->any())
            ->method('getOrigCustomerDataObject')
            ->willReturn(null);

        $this->eventChangesMock->expects($this->any())
            ->method('setOriginalData')
            ->willReturnSelf();

        $this->changesFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->eventChangesMock);

        $this->logSaveCustomerObserver->execute($this->observerMock);
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        $customerArray = [
            'website_id' => "1",
            'email' => 'customer@email.com',
            "group_id" => "1",
            "store_id" => "1",
            "created_at" => "2023-05-24 08:03:08",
            "updated_at" => "2023-05-24 08:03:08",
            "disable_auto_group_change" => "0",
            "created_in" => "Default Store View",
            "firstname" => "Customer Firstname",
            "lastname" => "Customer Lastname",
            "id" => "1"
        ];
        return [
            'testCasesWithTwoArray' => [
                $customerArray,
                $customerArray,
            ],
            'testCasesWithOneArrayAndNull' => [
                $customerArray,
                null
            ]
        ];
    }
}
