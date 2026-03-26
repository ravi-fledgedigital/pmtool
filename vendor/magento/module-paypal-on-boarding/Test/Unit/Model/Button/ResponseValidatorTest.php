<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model\Button;

use Laminas\Http\Response;
use Magento\PaypalOnBoarding\Model\Button\ResponseValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseValidatorTest
 */
class ResponseValidatorTest extends TestCase
{
    /**
     * @var Response|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var \Magento\PaypalOnBoarding\Model\Button\ResponseValidator
     */
    private $responseButtonValidator;

    protected function setUp(): void
    {
        $this->responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBody', 'getStatusCode'])
            ->getMock();

        $this->responseButtonValidator = new ResponseValidator();
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Model\Button\ResponseValidator::validate()
     */
    public function testValidateSuccess()
    {
        $this->responseMock->expects(static::once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->responseMock->expects(static::once())
            ->method('getBody')
            ->willReturn(json_encode(['sandboxButtonUrl' => 'sandboxUrl', 'liveButtonUrl' => 'liveUrl']));

        $this->assertTrue(
            $this->responseButtonValidator->validate(
                $this->responseMock,
                ['sandboxButtonUrl', 'liveButtonUrl']
            )
        );
    }

    /**
     * @param string $responseCode response code
     * @param string $responseBody response body
     * @dataProvider validateFailDataProvider
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    #[DataProvider('validateFailDataProvider')]
    public function testValidateFail($responseCode, $responseBody)
    {
        $this->expectException(\Magento\Framework\Exception\ValidatorException::class);
        $this->responseMock->expects(static::once())
            ->method('getStatusCode')
            ->willReturn($responseCode);

        $this->responseMock->expects(static::once())
            ->method('getBody')
            ->willReturn($responseBody);

        $this->responseButtonValidator->validate(
            $this->responseMock,
            ['sandboxButtonUrl', 'liveButtonUrl']
        );
    }

    public static function validateFailDataProvider()
    {
        return [
            [400, json_encode(['sandboxButtonUrl' => 'sandboxUrl', 'liveButtonUrl' => 'liveUrl'])],
            [200, 'Not JSON string'],
            [200, json_encode(['wrongField' => 'wrongValue'])],
            [200, json_encode(['sandboxButtonUrl' => 'sandboxUrl'])],
        ];
    }
}
