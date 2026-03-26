<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
namespace OnitsukaTiger\OrderAttribute\Test\Unit\Controller\File;

use OnitsukaTiger\OrderAttribute\Controller\File\Upload;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\File\Uploader;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UploadTest extends TestCase
{
    /**
     * @var RequestInterface|Http|MockObject
     */
    private $requestMock;

    /**
     * @var Uploader|MockObject
     */
    private $fileUploaderMock;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Upload
     */
    private $controller;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->requestMock = $this->createMock(Http::class);
        $this->fileUploaderMock = $this->createMock(Uploader::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->controller = $objectManager->getObject(
            Upload::class,
            [
                'request' => $this->requestMock,
                'fileUploader' => $this->fileUploaderMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test execute method
     *
     * @param string|null $fileName
     * @param string[] $result
     * @dataProvider executeProvider
     */
    public function testExecute($fileName, $result)
    {
        $resultJsonMock = $this->createMock(Json::class);

        $this->resultJsonFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($resultJsonMock);
        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with(Upload::PARAM_NAME)
            ->willReturn($fileName);
        $this->requestMock
            ->expects($this->once())
            ->method('isAjax')
            ->willReturn(true);
        $this->fileUploaderMock
            ->expects($this->exactly($fileName ? 1 : 0))
            ->method('saveFile')
            ->with($fileName)
            ->willReturn($result);
        $resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with($result)
            ->willReturnSelf();

        $this->assertSame($resultJsonMock, $this->controller->execute());
    }

    /**
     * Test execute method when request isn't ajax
     */
    public function testExecuteIsNotAjax()
    {
        $fileName = 'test_file.txt';
        $resultJsonMock = $this->createMock(Json::class);

        $this->resultJsonFactoryMock
            ->expects($this->never())
            ->method('create')
            ->willReturn($resultJsonMock);
        $this->requestMock
            ->expects($this->never())
            ->method('getParam')
            ->with(Upload::PARAM_NAME)
            ->willReturn($fileName);
        $this->requestMock
            ->expects($this->once())
            ->method('isAjax')
            ->willReturn(false);

        $this->assertSame(null, $this->controller->execute());
    }

    /**
     * Test execute method with exception
     *
     * @param string $exceptionClass
     * @param string $errorOutputMsg
     * @dataProvider executeWithExceptionProvider
     */
    public function testExecuteWithException($exceptionClass, $errorOutputMsg)
    {
        $fileName = 'test_file.txt';
        $resultJsonMock = $this->createMock(Json::class);
        $message = __('Error during file saving.');
        $result = ['error' => $errorOutputMsg];
        $exception  = new $exceptionClass($message);

        $this->resultJsonFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($resultJsonMock);
        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with(Upload::PARAM_NAME)
            ->willReturn($fileName);
        $this->requestMock
            ->expects($this->once())
            ->method('isAjax')
            ->willReturn(true);
        $this->fileUploaderMock
            ->expects($this->once())
            ->method('saveFile')
            ->with($fileName)
            ->willThrowException($exception);
        $resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with($result)
            ->willReturnSelf();

        $this->assertSame($resultJsonMock, $this->controller->execute());
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        $fileName = 'test_file.txt';

        return [
            [$fileName, ['name' => $fileName, 'size' => 1024]],
            [null, ['error' => __('File is missing.')]]
        ];
    }

    /**
     * @return array
     */
    public function executeWithExceptionProvider()
    {
        return [
            [LocalizedException::class, 'Error during file saving.'],
            [\Exception::class, 'Something went wrong.']
        ];
    }
}
