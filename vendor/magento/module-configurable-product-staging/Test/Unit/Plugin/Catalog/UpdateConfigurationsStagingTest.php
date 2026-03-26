<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductStaging\Test\Unit\Plugin\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as InitializationHelper;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\VariationHandler;
use Magento\ConfigurableProductStaging\Plugin\Catalog\UpdateConfigurationsStaging;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateConfigurationsStagingTest extends TestCase
{
    /**
     * @var UpdateConfigurationsStaging
     */
    public $plugin;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryMock;

    /**
     * @var VariationHandler|MockObject
     */
    private $variationHandlerMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var ProductStagingInterface|MockObject
     */
    private $productStagingMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->onlyMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->onlyMethods(['getById'])
            ->getMockForAbstractClass();
        $this->variationHandlerMock = $this->createMock(VariationHandler::class);
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->updateRepositoryMock = $this->createMock(UpdateRepositoryInterface::class);
        $this->productStagingMock = $this->createMock(ProductStagingInterface::class);
        $this->jsonMock = $this->createMock(Json::class);

        $this->plugin = new UpdateConfigurationsStaging(
            $this->requestMock,
            $this->productRepositoryMock,
            $this->variationHandlerMock,
            $this->versionManagerMock,
            $this->updateRepositoryMock,
            $this->productStagingMock,
            $this->jsonMock
        );
    }

    /**
     * @param array $configurableMatrix
     * @param array $configurations
     * @return void
     *
     * @dataProvider getConfigurableMatrix
     */
    public function testCannotAddToUpdate(array $configurableMatrix, array $configurations)
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['configurable-matrix-serialized'] => json_encode($configurableMatrix),
                ['store'] => 0,
                ['staging'] => ['stagingData']
            });

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturn($configurableMatrix);
        $this->variationHandlerMock->expects($this->atLeastOnce())
            ->method('duplicateImagesForVariations')
            ->with($configurations)
            ->willReturn($configurations);
        $this->variationHandlerMock->expects($this->any())
            ->method('processMediaGallery')
            ->willReturn($configurations);
        $simple = $this->createMock(Product::class);
        $this->productRepositoryMock->expects($this->any())->method('getById')->willReturn($simple);
        $currentVesion = $this->createMock(\Magento\Staging\Api\Data\UpdateInterface::class);
        $currentVesion->expects($this->any())->method('getId')->willReturn('2');
        $currentVesion->expects($this->any())->method('getIsCampaign')->willReturn(true);
        $this->versionManagerMock->expects($this->any())->method('getCurrentVersion')->willReturn($currentVesion);
        $this->versionManagerMock->expects($this->any())->method('isPreviewVersion')->willReturn(false);
        $helperMock = $this->createMock(InitializationHelper::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId'])
            ->getMock();
        $productMock->expects($this->any())->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $this->updateRepositoryMock->expects($this->never())->method('save');
        $this->productStagingMock->expects($this->never())->method('schedule');
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->plugin->afterInitialize($helperMock, $productMock);
    }

    /**
     * @return array[]
     */
    public static function getConfigurableMatrix(): array
    {
        return [
            [
                [[
                    'newProduct' => false,
                    'id' => 'product2',
                    'status' => 'simple2_status',
                    'sku' => 'simple2_sku',
                    'name' => 'simple2_name',
                    'price' => '3.33',
                    'configurable_attribute' => 'simple2_configurable_attribute',
                    'weight' => '5.55',
                    'media_gallery' => [
                        'images' => [
                            ['file' => 'test']
                        ],
                    ],
                    'swatch_image' => 'simple2_swatch_image',
                    'small_image' => 'simple2_small_image',
                    'thumbnail' => 'simple2_thumbnail',
                    'image' => 'simple2_image',
                    'was_changed' => true,
                ],
                [
                    'newProduct' => false,
                    'id' => 'product3',
                    'qty' => '3',
                    'was_changed' => false,
                ]],
                ['product2' => [
                    'status' => 'simple2_status',
                    'sku' => 'simple2_sku',
                    'name' => 'simple2_name',
                    'price' => '3.33',
                    'configurable_attribute' => 'simple2_configurable_attribute',
                    'weight' => '5.55',
                    'media_gallery' => ['images' => [['file' => 'test']]],
                    'swatch_image' => 'simple2_swatch_image',
                    'small_image' => 'simple2_small_image',
                    'thumbnail' => 'simple2_thumbnail',
                    'image' => 'simple2_image',
                    'type_id' => 'simple',
                    'product_has_weight' => 1,
                ]]
            ]
        ];
    }
}
