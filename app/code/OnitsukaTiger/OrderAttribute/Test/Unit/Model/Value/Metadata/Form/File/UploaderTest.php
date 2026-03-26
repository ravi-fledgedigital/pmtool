<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
namespace OnitsukaTiger\OrderAttribute\Test\Unit\Model\Value\Metadata\Form\File;

use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\File\Uploader;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\File\Uploader\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Uploader as FrameworkUploader;
use Magento\Framework\File\UploaderFactory as FrameworkUploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UploaderTest extends TestCase
{
    /**
     * @var WriteInterface|MockObject
     */
    private $mediaDirectoryMock;

    /**
     * @var FrameworkUploaderFactory|MockObject
     */
    private $uploaderFactoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Validator|MockObject
     */
    private $validatorMock;

    /**
     * @var string
     */
    private $basePath = 'onitsukatiger_checkout';

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->mediaDirectoryMock = $this->createMock(WriteInterface::class);
        $this->uploaderFactoryMock = $this->createMock(FrameworkUploaderFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->validatorMock = $this->createMock(Validator::class);
        $filesystemMock = $this->createConfiguredMock(
            Filesystem::class,
            ['getDirectoryWrite' => $this->mediaDirectoryMock]
        );

        $this->uploader = $objectManager->getObject(
            Uploader::class,
            [
                'filesystem' => $filesystemMock,
                'uploaderFactory' => $this->uploaderFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'validator' => $this->validatorMock
            ]
        );
    }

    /**
     * Test saveFile method
     *
     * @throws LocalizedException
     */
    public function testSaveFile()
    {
        $fileId = 'test_file';
        $filename = 'test_file.txt';
        $baseMediaUrl = 'http://example.com/pub/media';
        $storeMock = $this->createMock(Store::class);
        $uploaderMock = $this->getUploaderMock($fileId);
        $result = [
            'file' => $filename,
            'path' => $this->basePath . $filename,
            'url' => $baseMediaUrl . $this->basePath . '/' .$filename
        ];

        $this->validatorMock
            ->expects($this->once())
            ->method('validateAttributeCode')
            ->with($fileId)
            ->willReturn(true);
        $this->mediaDirectoryMock
            ->expects($this->once())
            ->method('getAbsolutePath')
            ->with($this->basePath)
            ->willReturn($this->basePath);
        $uploaderMock
            ->expects($this->once())
            ->method('save')
            ->with($this->basePath)
            ->willReturn($result);
        $uploaderMock
            ->expects($this->once())
            ->method('getUploadedFileName')
            ->willReturn($filename);
        $this->storeManagerMock
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $storeMock
            ->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($baseMediaUrl);
        $this->validatorMock
            ->expects($this->once())
            ->method('validateFile')
            ->with($fileId, $result)
            ->willReturn(true);

        $this->assertSame($result, $this->uploader->saveFile($fileId));
    }

    /**
     * Test saveFile method with exception
     *
     * @param bool $isAttributeValid
     * @param bool $isSaveException
     * @param string $exceptionMessage
     * @throws LocalizedException
     * @dataProvider saveFileWithExceptionProvider
     */
    public function testSaveFileWithException($isAttributeValid, $isSaveException, $exceptionMessage)
    {
        $fileId = 'test_file';
        $filename = 'test_file.txt';
        $baseMediaUrl = 'http://example.com/pub/media';
        $storeMock = $this->createMock(Store::class);
        $result = [
            'file' => $filename,
            'path' => $this->basePath . $filename,
            'url' => $baseMediaUrl . $this->basePath . '/' .$filename
        ];
        $exception = new LocalizedException(__($exceptionMessage));

        $this->validatorMock
            ->expects($this->once())
            ->method('validateAttributeCode')
            ->with($fileId)
            ->willReturn($isAttributeValid);

        if ($isAttributeValid) {
            $uploaderMock = $this->getUploaderMock($fileId);

            $this->mediaDirectoryMock
                ->expects($this->once())
                ->method('getAbsolutePath')
                ->with($this->basePath)
                ->willReturn($this->basePath);
            if ($isSaveException) {
                $uploaderMock
                    ->expects($this->once())
                    ->method('save')
                    ->with($this->basePath)
                    ->willThrowException($exception);
            } else {
                $uploaderMock
                    ->expects($this->once())
                    ->method('save')
                    ->with($this->basePath)
                    ->willReturn($result);
                $uploaderMock
                    ->expects($this->once())
                    ->method('getUploadedFileName')
                    ->willReturn($filename);
                $this->storeManagerMock
                    ->expects($this->once())
                    ->method('getStore')
                    ->willReturn($storeMock);
                $storeMock
                    ->expects($this->once())
                    ->method('getBaseUrl')
                    ->with(UrlInterface::URL_TYPE_MEDIA)
                    ->willReturn($baseMediaUrl);
                $this->validatorMock
                    ->expects($this->once())
                    ->method('validateFile')
                    ->with($fileId, $result)
                    ->willReturn(false);
            }
        }

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->uploader->saveFile($fileId);
    }

    /**
     * Test getFileInfo method
     *
     * @param bool $isFileExist
     * @param int $size
     * @dataProvider getFileInfoProvider
     */
    public function testGetFileInfo($isFileExist, $size)
    {
        $filename = 'test_file.txt';
        $filePath = $this->basePath . '/' . $filename;
        $baseMediaUrl = 'http://example.com/pub/media';
        $url = $baseMediaUrl . $this->basePath . '/' . $filename;
        $storeMock = $this->createMock(Store::class);
        $callsCount = $isFileExist ? 1 : 0;
        $stat = [
            0 => 12333,
            'size' => $size
        ];
        $fileInfo = $isFileExist
            ? [
                'name' => $filename,
                'file_path' => $filePath,
                'file' => $filename,
                'size' => $stat['size'] ?? 0,
                'url' => $url
            ]
            : [];

        $this->mediaDirectoryMock
            ->expects($this->once())
            ->method('isFile')
            ->willReturn($isFileExist);
        $this->mediaDirectoryMock
            ->expects($this->exactly($callsCount))
            ->method('stat')
            ->with($filePath)
            ->willReturn($stat);
        $this->storeManagerMock
            ->expects($this->exactly($callsCount))
            ->method('getStore')
            ->willReturn($storeMock);
        $storeMock
            ->expects($this->exactly($callsCount))
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($baseMediaUrl);

        $this->assertEquals($fileInfo, $this->uploader->getFileInfo($filename));
    }

    /**
     * @return array
     */
    public function saveFileWithExceptionProvider()
    {
        return [
            [true, true, 'Error during file saving.'],
            [true, false, 'File can not be saved to the destination folder.'],
            [false, false, 'Something went wrong while saving the file(s).']
        ];
    }

    /**
     * @return array
     */
    public function getFileInfoProvider()
    {
        return [
            [true, 0],
            [true, 4412312],
            [false, 0]
        ];
    }

    /**
     * Returns configured uploader mock
     *
     * @param string $fileId
     * @return FrameworkUploader|MockObject
     */
    private function getUploaderMock($fileId)
    {
        $uploaderMock = $this->createMock(FrameworkUploader::class);
        $this->uploaderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with(['fileId' => $fileId])
            ->willReturn($uploaderMock);
        $uploaderMock
            ->expects($this->once())
            ->method('setFilesDispersion')
            ->with(true)
            ->willReturnSelf();
        $uploaderMock
            ->expects($this->once())
            ->method('setFilenamesCaseSensitivity')
            ->with(false)
            ->willReturnSelf();
        $uploaderMock
            ->expects($this->once())
            ->method('setAllowRenameFiles')
            ->with(true)
            ->willReturnSelf();

        return $uploaderMock;
    }
}
