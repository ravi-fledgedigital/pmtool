<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Catalog\ResolverCache;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogGraphQl\Model\Resolver\Product\MediaGallery;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWriteInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQlResolverCache\Model\Resolver\Result\CacheKey\Calculator\ProviderInterface;
use Magento\GraphQlResolverCache\Model\Resolver\Result\Type as GraphQlResolverCache;
use Magento\ImportExport\Model\Import;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\Integration;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQl\ResolverCacheAbstract;

/**
 * Tests Media Gallery resolver cache invalidation on product import.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MediaGalleryImportTest extends ResolverCacheAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GraphQlResolverCache
     */
    private $graphQlResolverCache;

    /**
     * @var IntegrationServiceInterface
     */
    private $integrationService;

    /**
     * @var Integration
     */
    private $integration;

    /**
     * @var DirectoryWriteInterface
     */
    private $mediaDirectory;

    /**
     * @var string[]
     */
    private $filesToRemove = [];

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->graphQlResolverCache = $this->objectManager->get(GraphQlResolverCache::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->integrationService = $this->objectManager->get(IntegrationServiceInterface::class);
        $filesystem = $this->objectManager->get(Filesystem::class);
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach ($this->filesToRemove as $fileToRemove) {
            $this->mediaDirectory->delete($fileToRemove);
        }

        $this->mediaDirectory->delete('import/images');

        parent::tearDown();
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => 'product1', 'media_gallery_entries' => [[]]], as: 'product'),
        DataFixture(ProductFixture::class, ['sku' => 'product2', 'media_gallery_entries' => [[]]], as: 'product2'),
    ]
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCacheIsInvalidatedWhenUpdatingMediaGalleryEntriesOnAProductViaImport()
    {
        // first, create an integration so that cache is not cleared in
        // Magento\TestFramework\Authentication\OauthHelper::_createIntegration before making the API call
        $integration = $this->getOauthIntegration();

        $product1 = $this->productRepository->get('product1');

        $this->assertCount(
            1,
            $product1->getMediaGalleryEntries()
        );

        $query = $this->getProductWithMediaGalleryQuery($product1);
        $response = $this->graphQlQuery($query);

        $this->assertCount(
            1,
            $response['products']['items'][0]['media_gallery']
        );

        $this->assertMediaGalleryResolverCacheRecordExists($product1);

        // query product2's media gallery to create a separate cache record
        $product2 = $this->productRepository->get('product2');
        $query = $this->getProductWithMediaGalleryQuery($product2);
        $this->graphQlQuery($query);
        $this->assertMediaGalleryResolverCacheRecordExists($product2);

        // make API call to update product1's media gallery
        $serviceInfo = $this->getServiceInfo();

        ///////
        /// assert product1's media gallery resolver cache is invalidated when adding new entry

        // move test image into media directory
        $destinationDir = $this->mediaDirectory->getAbsolutePath() . 'import/images';

        $this->mediaDirectory->create($destinationDir);

        $sourcePathname = dirname(__FILE__) . '/_files/magento_image.jpg';
        $destinationFilePathname = $this->mediaDirectory->getAbsolutePath("$destinationDir/magento_image.jpg");

        $this->mediaDirectory->getDriver()->filePutContents(
            $destinationFilePathname,
            file_get_contents($sourcePathname)
        );

        // add file to list of files to remove after test finishes
        $this->filesToRemove[] = $destinationFilePathname;

        $requestData = [
            'source' => [
                'entity' => 'catalog_product',
                'behavior' => 'append',
                'validationStrategy' => 'validation-stop-on-errors',
                'allowedErrorCount' => '10',
                Import::FIELD_NAME_IMG_FILE_DIR => $destinationDir,
                'csvData' => base64_encode("sku,base_image\nproduct1,magento_image.jpg\n"),
            ],
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData, 'rest', null, $integration);

        $this->assertEquals(
            'Entities Processed: 1',
            $response[0]
        );

        // assert product1 has updated media gallery entry count
        $this->productRepository->cleanCache();
        $updatedProduct = $this->productRepository->get('product1');
        $this->assertCount(
            2,
            $updatedProduct->getMediaGalleryEntries()
        );

        // assert media gallery resolver cache for product1 is invalidated
        $this->assertMediaGalleryResolverCacheRecordDoesNotExist($product1);

        // assert media gallery resolver cache for product2 is NOT invalidated
        $this->assertMediaGalleryResolverCacheRecordExists($product2);

        // assert product1's cache id is not orphaned in any of the tag files
        $this->assertCacheIdIsNotOrphanedInTagsForProduct($product1);

        ///////
        /// assert product1's media gallery resolver cache is invalidated when changing label on existing entry

        // re-prime product1's media gallery cache
        $query = $this->getProductWithMediaGalleryQuery($product1);
        $this->graphQlQuery($query);
        $this->assertMediaGalleryResolverCacheRecordExists($product1);

        // update product1's media gallery entry label
        $requestData = [
            'source' => [
                'entity' => 'catalog_product',
                'behavior' => 'append',
                'validationStrategy' => 'validation-stop-on-errors',
                'allowedErrorCount' => '10',
                Import::FIELD_NAME_IMG_FILE_DIR => $destinationDir,
                'csvData' => base64_encode(
                    "sku,base_image,image_label\nproduct1,magento_image.jpg,TEST\n"
                ),
            ],
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData, 'rest', null, $integration);

        $this->assertEquals(
            'Entities Processed: 1',
            $response[0]
        );

        // assert product1 has the same media gallery entry count
        $this->productRepository->cleanCache();
        $updatedProduct = $this->productRepository->get('product1');
        $this->assertCount(
            2,
            $updatedProduct->getMediaGalleryEntries()
        );

        $this->assertEquals(
            'TEST',
            $updatedProduct->getMediaGalleryEntries()[1]->getLabel()
        );

        // assert media gallery resolver cache for product1 is invalidated
        $this->assertMediaGalleryResolverCacheRecordDoesNotExist($product1);

        // assert media gallery resolver cache for product2 is NOT invalidated
        $this->assertMediaGalleryResolverCacheRecordExists($product2);
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => 'product1', 'media_gallery_entries' => [[]]], as: 'product'),
        DataFixture(ProductFixture::class, ['sku' => 'product2', 'media_gallery_entries' => [[]]], as: 'product2'),
    ]
    public function testCacheIsInvalidatedWhenDeletingProductEntryViaImport()
    {
        // first, create an integration so that cache is not cleared in
        // Magento\TestFramework\Authentication\OauthHelper::_createIntegration before making the API call
        $integration = $this->getOauthIntegration();

        $product1 = $this->productRepository->get('product1');

        $this->assertCount(
            1,
            $product1->getMediaGalleryEntries()
        );

        $query = $this->getProductWithMediaGalleryQuery($product1);
        $response = $this->graphQlQuery($query);

        $this->assertCount(
            1,
            $response['products']['items'][0]['media_gallery']
        );

        $this->assertMediaGalleryResolverCacheRecordExists($product1);

        // query product2's media gallery to create a separate cache record
        $product2 = $this->productRepository->get('product2');
        $query = $this->getProductWithMediaGalleryQuery($product2);
        $this->graphQlQuery($query);
        $this->assertMediaGalleryResolverCacheRecordExists($product2);

        // delete product1 via import
        $requestData = [
            'source' => [
                'entity' => 'catalog_product',
                'behavior' => 'delete',
                'validationStrategy' => 'validation-stop-on-errors',
                'allowedErrorCount' => '10',
                'csvData' => base64_encode(
                    "sku\nproduct1\n"
                ),
            ],
        ];

        $response = $this->_webApiCall(
            $this->getServiceInfo(),
            $requestData,
            'rest',
            null,
            $integration
        );

        $this->assertEquals(
            'Entities Processed: 1',
            $response[0]
        );

        $this->productRepository->cleanCache();

        try {
            $this->productRepository->get('product1');
            $this->fail('Product1 should have been deleted');
        } catch (NoSuchEntityException $e) {
            // expected
        }

        // assert media gallery resolver cache for product1 is invalidated
        $this->assertMediaGalleryResolverCacheRecordDoesNotExist($product1);

        // assert media gallery resolver cache for product2 is NOT invalidated
        $this->assertMediaGalleryResolverCacheRecordExists($product2);
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => 'product1', 'media_gallery_entries' => [[]]], as: 'product'),
        DataFixture(ProductFixture::class, ['sku' => 'product2', 'media_gallery_entries' => [[]]], as: 'product2'),
    ]
    public function testCacheIsInvalidatedWhenReplacingProductEntryViaImport()
    {
        // first, create an integration so that cache is not cleared in
        // Magento\TestFramework\Authentication\OauthHelper::_createIntegration before making the API call
        $integration = $this->getOauthIntegration();

        $product1 = $this->productRepository->get('product1');

        $this->assertCount(
            1,
            $product1->getMediaGalleryEntries()
        );

        $query = $this->getProductWithMediaGalleryQuery($product1);
        $response = $this->graphQlQuery($query);

        $this->assertCount(
            1,
            $response['products']['items'][0]['media_gallery']
        );

        $this->assertMediaGalleryResolverCacheRecordExists($product1);

        // query product2's media gallery to create a separate cache record
        $product2 = $this->productRepository->get('product2');
        $query = $this->getProductWithMediaGalleryQuery($product2);
        $this->graphQlQuery($query);
        $this->assertMediaGalleryResolverCacheRecordExists($product2);

        // replace product1 via import
        $requestData = [
            'source' => [
                'entity' => 'catalog_product',
                'behavior' => 'replace',
                'validationStrategy' => 'validation-stop-on-errors',
                'allowedErrorCount' => '10',
                'csvData' => base64_encode(
                    "sku,product_type,attribute_set_code,name,price\nproduct1,simple,Default,newName,50\n"
                ),
            ],
        ];

        $response = $this->_webApiCall(
            $this->getServiceInfo(),
            $requestData,
            'rest',
            null,
            $integration
        );

        $this->assertEquals(
            'Entities Processed: 1',
            $response[0]
        );

        $this->productRepository->cleanCache();

        $updatedProduct = $this->productRepository->get('product1');
        $this->assertEquals(
            'newName',
            $updatedProduct->getName()
        );

        // assert media gallery resolver cache for product1 is invalidated
        $this->assertMediaGalleryResolverCacheRecordDoesNotExist($product1);

        // assert media gallery resolver cache for product2 is NOT invalidated
        $this->assertMediaGalleryResolverCacheRecordExists($product2);
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => 'product1', 'media_gallery_entries' => [[]]], as: 'product'),
    ]
    public function testCacheIsNotInvalidatedWhenUpdatingProductSpecificAttributeViaImport()
    {
        // first, create an integration so that cache is not cleared in
        // Magento\TestFramework\Authentication\OauthHelper::_createIntegration before making the API call
        $integration = $this->getOauthIntegration();

        $product = $this->productRepository->get('product1');

        $this->assertCount(
            1,
            $product->getMediaGalleryEntries()
        );

        $query = $this->getProductWithMediaGalleryQuery($product);
        $response = $this->graphQlQuery($query);

        $this->assertCount(
            1,
            $response['products']['items'][0]['media_gallery']
        );

        $this->assertMediaGalleryResolverCacheRecordExists($product);

        $serviceInfo = $this->getServiceInfo();

        $requestData = [
            'source' => [
                'entity' => 'catalog_product',
                'behavior' => 'append',
                'validationStrategy' => 'validation-stop-on-errors',
                'allowedErrorCount' => '10',
                'csvData' => base64_encode("sku,name\nproduct1,NEW_NAME\n"),
            ],
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData, 'rest', null, $integration);

        $this->assertEquals(
            'Entities Processed: 1',
            $response[0]
        );

        // assert product has new product name
        $this->productRepository->cleanCache();
        $updatedProduct = $this->productRepository->get('product1');

        $this->assertEquals(
            'NEW_NAME',
            $updatedProduct->getName()
        );

        // assert media gallery resolver cache is NOT invalidated
        $this->assertMediaGalleryResolverCacheRecordExists($product);
    }

    /**
     * Assert that cache id is not present in any of the cache tag files for the $product.
     *
     * @param ProductInterface $product
     * @return void
     * @throws \Zend_Cache_Exception
     */
    private function assertCacheIdIsNotOrphanedInTagsForProduct(ProductInterface $product)
    {
        $cacheKey = $this->getCacheKeyForMediaGalleryResolver($product);
        $cacheLowLevelFrontend = $this->graphQlResolverCache->getLowLevelFrontend();
        $cacheIdPrefix = $cacheLowLevelFrontend->getOption('cache_id_prefix');
        $cacheBackend = $cacheLowLevelFrontend->getBackend();
        $this->assertFalse(
            $this->graphQlResolverCache->test($cacheIdPrefix . $cacheKey),
            'Cache id is still present after invalidation'
        );

        $this->assertNotContains(
            $cacheIdPrefix . $cacheKey,
            $cacheBackend->getIdsMatchingTags([
                $cacheIdPrefix . 'GQL_MEDIA_GALLERY_' . strtoupper($product->getSku()),
            ]),
            sprintf(
                'Cache id is still present in GQL_MEDIA_GALLERY_%s tag file after invalidation',
                strtoupper($product->getSku())
            )
        );
    }

    /**
     *
     * @return Integration
     * @throws \Magento\Framework\Exception\IntegrationException
     */
    private function getOauthIntegration(): Integration
    {
        if (!isset($this->integration)) {
            $params = [
                'all_resources' => true,
                'status' => Integration::STATUS_ACTIVE,
                'name' => 'Integration' . microtime()
            ];

            $this->integration = $this->integrationService->create($params);
        }

        return $this->integration;
    }

    /**
     * Assert that media gallery cache record exists for the $product.
     *
     * @param ProductInterface $product
     * @return void
     */
    private function assertMediaGalleryResolverCacheRecordExists(ProductInterface $product)
    {
        $cacheKey = $this->getCacheKeyForMediaGalleryResolver($product);
        $cacheEntry = $this->graphQlResolverCache->load($cacheKey);

        $this->assertNotFalse(
            $cacheEntry,
            sprintf('Media gallery cache entry for product with sku "%s" does not exist', $product->getSku())
        );

        $cacheEntryDecoded = json_decode($cacheEntry, true);

        $this->assertEquals(
            $product->getMediaGalleryEntries()[0]->getLabel(),
            $cacheEntryDecoded[0]['label']
        );
    }

    /**
     * Assert that media gallery cache record does not exist for the $product.
     *
     * @param ProductInterface $product
     * @return void
     */
    private function assertMediaGalleryResolverCacheRecordDoesNotExist(ProductInterface $product)
    {
        $cacheKey = $this->getCacheKeyForMediaGalleryResolver($product);
        $this->assertFalse(
            $this->graphQlResolverCache->test($cacheKey),
            sprintf('Media gallery cache entry for product with sku "%s" exists', $product->getSku())
        );
    }

    /**
     * Get cache key for media gallery resolver based on the $product
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getCacheKeyForMediaGalleryResolver(ProductInterface $product): string
    {
        $resolverMock = $this->getMockBuilder(MediaGallery::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ProviderInterface $cacheKeyCalculatorProvider */
        $cacheKeyCalculatorProvider = $this->objectManager->get(ProviderInterface::class);

        $cacheKeyFactor = $cacheKeyCalculatorProvider
            ->getKeyCalculatorForResolver($resolverMock)
            ->calculateCacheKey(['model' => $product]);

        $cacheKeyQueryPayloadMetadata = MediaGallery::class . '\Interceptor[]';

        $cacheKeyParts = [
            GraphQlResolverCache::CACHE_TAG,
            $cacheKeyFactor,
            sha1($cacheKeyQueryPayloadMetadata)
        ];

        // strtoupper is called in \Magento\Framework\Cache\Frontend\Adapter\Zend::_unifyId
        return strtoupper(implode('_', $cacheKeyParts));
    }

    private function getServiceInfo(): array
    {
        return  [
            'rest' => [
                'resourcePath' => '/V1/import/csv',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
        ];
    }

    /**
     * Compile GraphQL query for getting product data with media gallery
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getProductWithMediaGalleryQuery(ProductInterface $product): string
    {
        return <<<QUERY
{
  products(filter: {sku: {eq: "{$product->getSku()}"}}) {
    items {
      small_image {
        url
      }
      media_gallery {
      	label
        url
        position
        disabled
        ... on ProductVideo {
              video_content {
                  media_type
                  video_provider
                  video_url
                  video_title
                  video_description
                  video_metadata
              }
          }
      }
    }
  }
}
QUERY;
    }
}
