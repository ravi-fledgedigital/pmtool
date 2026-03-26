<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model;

use Magento\PaypalOnBoarding\Model\MagentoMerchantId;
use Magento\PaypalOnBoarding\Model\ResponseCredentialsValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseCredentialsValidatorTest
 */
#[AllowMockObjectsWithoutExpectations]
class ResponseCredentialsValidatorTest extends TestCase
{
    /**
     * @var MagentoMerchantId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $magentoMerchantIdMock;

    /**
     * @var ResponseCredentialsValidator
     */
    private $responseCredentialsValidator;

    protected function setUp(): void
    {
        $this->magentoMerchantIdMock = $this->createMock(MagentoMerchantId::class);

        $this->responseCredentialsValidator = new ResponseCredentialsValidator($this->magentoMerchantIdMock);
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Model\ResponseCredentialsValidator::validate()
     */
    public function testValidateSuccess()
    {
        $magentoMerchantId = 'qweasd';
        $websiteId = 1;
        $data = [
            'username' => 'username',
            'password' => 'password',
            'signature' => 'signature',
            'website' => $websiteId,
            'magentoMerchantId' => $magentoMerchantId
        ];
        $this->magentoMerchantIdMock->expects(static::once())
            ->method('generate')
            ->with($websiteId)
            ->willReturn($magentoMerchantId);

        $this->assertTrue(
            $this->responseCredentialsValidator->validate(
                $data,
                ['username', 'password', 'signature', 'magentoMerchantId']
            )
        );
    }

    /**
     * @param array $data response params
     * @dataProvider validateFailDataProvider
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    #[DataProvider('validateFailDataProvider')]
    public function testValidateFail(array $data)
    {
        $this->expectException(\Magento\Framework\Exception\ValidatorException::class);
        $this->magentoMerchantIdMock->expects(static::any())
            ->method('generate')
            ->willReturn('right signature');

        $this->responseCredentialsValidator->validate(
            $data,
            ['username', 'password', 'signature', 'magentoMerchantId']
        );
    }

    public static function validateFailDataProvider()
    {
        return [
            'Required param missing' => [
                [
                    'username' => 'username',
                    'password' => 'password'
                ]
            ],
            'Wrong merchant signature' => [
                [
                    'username' => 'username',
                    'password' => 'password',
                    'signature' => 'signature',
                    'magentoMerchantId' => 'wrong signature'
                ]
            ],
        ];
    }
}
