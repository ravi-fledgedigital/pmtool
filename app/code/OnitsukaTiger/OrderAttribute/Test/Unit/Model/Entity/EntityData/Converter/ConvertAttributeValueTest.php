<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Test\Unit\Model\Entity\EntityData\Converter;

use OnitsukaTiger\OrderAttribute\Api\Data\AttributeValueInterface;
use OnitsukaTiger\OrderAttribute\Api\Data\AttributeValueInterfaceFactory;
use OnitsukaTiger\OrderAttribute\Model\Entity\EntityData\Converter\CanConvertAttributeValue;
use OnitsukaTiger\OrderAttribute\Model\Entity\EntityData\Converter\ConvertAttributeValue;
use OnitsukaTiger\OrderAttribute\Model\Entity\EntityData\Converter\GetOptionLabels;
use Magento\Framework\Api\AttributeInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see ConvertAttributeValue
 * @covers ConvertAttributeValue::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ConvertAttributeValueTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CanConvertAttributeValue|MockObject
     */
    private $canConvertAttributeValueMock;

    /**
     * @var GetOptionLabels|MockObject
     */
    private $getOptionLabelsMock;

    /**
     * @var AttributeValueInterface|MockObject
     */
    private $attributeValueMock;

    /**
     * @var ConvertAttributeValue
     */
    private $subject;

    protected function setUp(): void
    {
        $this->canConvertAttributeValueMock = $this->createMock(CanConvertAttributeValue::class);
        $this->getOptionLabelsMock = $this->createMock(GetOptionLabels::class);

        $this->attributeValueMock = $this->createMock(AttributeValueInterface::class);
        $attributeValueFactoryMock = $this->createConfiguredMock(
            AttributeValueInterfaceFactory::class,
            ['create' => $this->attributeValueMock]
        );

        $this->subject = new ConvertAttributeValue(
            $this->canConvertAttributeValueMock,
            $this->getOptionLabelsMock,
            $attributeValueFactoryMock
        );
    }

    public function testExecuteNotConvertibleFrontendInput(): void
    {
        $attributeValueMock = $this->createConfiguredMock(
            AttributeInterface::class,
            ['getAttributeCode' => 'test_code', 'getValue' => 'text']
        );

        $this->attributeValueMock
            ->expects($this->once())
            ->method('setAttributeCode')
            ->with('test_code')
            ->willReturnSelf();
        $this->attributeValueMock
            ->expects($this->once())
            ->method('setValue')
            ->with('text')
            ->willReturnSelf();

        $this->canConvertAttributeValueMock
            ->expects($this->once())
            ->method('execute')
            ->with('test_code')
            ->willReturn(false);

        $this->attributeValueMock->expects($this->never())->method('setLabel');
        $this->getOptionLabelsMock->expects($this->never())->method('execute');
        $this->assertEquals($this->attributeValueMock, $this->subject->execute($attributeValueMock));
    }

    public function testExecuteLabelNotFound(): void
    {
        $attributeValueMock = $this->createConfiguredMock(
            AttributeInterface::class,
            ['getAttributeCode' => 'test_code', 'getValue' => '1,2']
        );

        $this->attributeValueMock
            ->expects($this->once())
            ->method('setAttributeCode')
            ->with('test_code')
            ->willReturnSelf();
        $this->attributeValueMock
            ->expects($this->once())
            ->method('setValue')
            ->with('1,2')
            ->willReturnSelf();
        $this->attributeValueMock
            ->expects($this->once())
            ->method('setLabel')
            ->with(null)
            ->willReturnSelf();

        $this->canConvertAttributeValueMock
            ->expects($this->once())
            ->method('execute')
            ->with('test_code')
            ->willReturn(true);

        $this->getOptionLabelsMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn([1 => [0 => 'A']]);

        $this->assertEquals($this->attributeValueMock, $this->subject->execute($attributeValueMock));
    }

    /**
     * @param string $originalValue
     * @param array $optionLabels
     * @param string $expectedLabel
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(string $originalValue, array $optionLabels, string $expectedLabel): void
    {
        $attributeValueMock = $this->createConfiguredMock(
            AttributeInterface::class,
            ['getAttributeCode' => 'test_code', 'getValue' => $originalValue]
        );

        $this->attributeValueMock
            ->expects($this->once())
            ->method('setAttributeCode')
            ->with('test_code')
            ->willReturnSelf();
        $this->attributeValueMock
            ->expects($this->once())
            ->method('setValue')
            ->with($originalValue)
            ->willReturnSelf();
        $this->attributeValueMock
            ->expects($this->once())
            ->method('setLabel')
            ->with($expectedLabel)
            ->willReturnSelf();

        $this->canConvertAttributeValueMock
            ->expects($this->once())
            ->method('execute')
            ->with('test_code')
            ->willReturn(true);

        $this->getOptionLabelsMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($optionLabels);

        $this->assertEquals($this->attributeValueMock, $this->subject->execute($attributeValueMock));
    }

    public function executeDataProvider(): array
    {
        return [
            [
                '1',
                [1 => [0 => 'A']],
                'A'
            ],
            [
                '1,2',
                [1 => [0 => 'A'], 2 => [0 => 'B']],
                'A,B'
            ]
        ];
    }
}
