<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

use Magento\AdminUiSdkCustomFees\Model\ResourceModel\SalesOrder\Collection;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\AdminUiSdkCustomFees\Model\CustomFees;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesRepository;
use Magento\AdminUiSdkCustomFees\Model\ResourceModel\CustomFees as ResourceModel;
use Magento\AdminUiSdkCustomFees\Model\CustomFeesFactory;
use Magento\AdminUiSdkCustomFees\Model\ResourceModel\SalesOrder\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Test class for CustomFeesRepository
 */
class CustomFeesRepositoryTest extends TestCase
{
    /**
     * @var ResourceModel|MockObject
     */
    private $resourceModel;

    /**
     * @var CustomFeesFactory|MockObject
     */
    private $customFeesFactory;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var CustomFeesRepository
     */
    private $customFeesRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->resourceModel = $this->createMock(ResourceModel::class);
        $this->customFeesFactory = $this->createMock(CustomFeesFactory::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);

        $this->customFeesRepository = new CustomFeesRepository(
            $this->resourceModel,
            $this->customFeesFactory,
            $this->collectionFactory
        );
    }

    /**
     * Test getById method
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetById()
    {
        $customFees = $this->createMock(CustomFees::class);
        $customFees->method('getId')->willReturn('1');

        $this->customFeesFactory->method('create')->willReturn($customFees);
        $this->resourceModel->method('load')->with($customFees, 1);

        $result = $this->customFeesRepository->getById(1);

        $this->assertEquals($customFees, $result);
    }

    /**
     * Test getById method throws exception when sales order not found
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetByIdThrowsExceptionWhenNotFound()
    {
        $customFees = $this->createMock(CustomFees::class);
        $customFees->method('getId')->willReturn(null);

        $this->customFeesFactory->method('create')->willReturn($customFees);
        $this->resourceModel->method('load')->with($customFees, 1);

        $this->expectException(NoSuchEntityException::class);

        $this->customFeesRepository->getById(1);
    }

    /**
     * Test save method
     *
     * @return void
     * @throws LocalizedException
     */
    public function testSave()
    {
        $customFees = $this->createMock(CustomFees::class);

        $this->resourceModel->expects($this->once())
            ->method('save')
            ->with($customFees);

        $this->customFeesRepository->save($customFees);
    }

    /**
     * Test getByOrderId method
     *
     * @return void
     */
    public function testGetByOrderId()
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('addFieldToFilter')->willReturn($collection);

        $this->collectionFactory->method('create')->willReturn($collection);

        $result = $this->customFeesRepository->getByOrderId('1');
        $this->assertEquals($collection, $result);
    }

    /**
     * Test addInvoicedAmount method
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function testAddInvoiceAmount()
    {
        $customFees = $this->createMock(CustomFees::class);
        $customFees->method('getId')->willReturn('1');

        $this->customFeesFactory->method('create')->willReturn($customFees);
        $this->resourceModel->method('load')->with($customFees, 1);

        $customFees->expects($this->once())->method('setInvoiceId')->with('2');
        $customFees->expects($this->once())->method('setCustomFeeAmountInvoiced')->with(10.0);
        $customFees->expects($this->once())->method('setBaseCustomFeeAmountInvoiced')->with(12.0);

        $this->resourceModel->expects($this->once())->method('save')->with($customFees);

        $this->customFeesRepository->addInvoicedAmount(1, '2', 10.0, 12.0);
    }

    /**
     * Test addRefundedAmountAndCreditMemoId method
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function testAddRefundedAmountAndCreditMemoId()
    {
        $customFees = $this->createMock(CustomFees::class);
        $customFees->method('getId')->willReturn('1');

        $this->customFeesFactory->method('create')->willReturn($customFees);
        $this->resourceModel->method('load')->with($customFees, 1);

        $customFees->expects($this->once())->method('setCreditMemoId')->with('2');
        $customFees->expects($this->once())->method('setCustomFeeAmountRefunded')->with(10.0);
        $customFees->expects($this->once())->method('setBaseCustomFeeAmountRefunded')->with(12.0);

        $this->resourceModel->expects($this->once())->method('save')->with($customFees);

        $this->customFeesRepository->addRefundedAmountAndCreditMemoId(1, 10.0, 12.0, '2');
    }
}
