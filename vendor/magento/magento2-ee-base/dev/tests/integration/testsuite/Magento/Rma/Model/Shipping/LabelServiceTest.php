<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\Shipping;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Rma\Api\Data\TrackInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Api\TrackRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Shipping;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject as MockObject;

/**
 * @magentoAppArea adminhtml
 */
class LabelServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var LabelService
     */
    private $service;

    /**
     * @var CurlFactory|MockObject
     */
    private $curlFactory;

    /**
     * @var Curl
     */
    private $curlClient;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->curlFactory = $this->getMockBuilder(CurlFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->curlClient = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHeaders', 'getBody', 'post'])
            ->getMock();

        $this->objectManager->addSharedInstance($this->curlFactory, CurlFactory::class);

        $this->service = $this->objectManager->create(LabelService::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        $this->objectManager->removeSharedInstance(ClientFactory::class);
    }

    /**
     * Checks a case when carrier can return multiple tracking codes during shipping label generation.
     *
     * @magentoConfigFixture current_store carriers/fedex/active 1
     * @magentoConfigFixture current_store carriers/fedex/api_key apikey
     * @magentoConfigFixture current_store carriers/fedex/secret_key secretkey
     * @magentoConfigFixture current_store carriers/fedex/sandbox_mode 1
     * @magentoConfigFixture current_store carriers/fedex/active_rma 1
     * @magentoConfigFixture current_store sales/magento_rma/use_store_address 0
     * @magentoConfigFixture current_store general/store_information/country_id US
     * @magentoConfigFixture current_store general/store_information/phone 321789821
     * @magentoConfigFixture current_store general/store_information/name Company
     * @magentoConfigFixture current_store sales/magento_rma/city Los Angeles
     * @magentoConfigFixture current_store sales/magento_rma/address1 Street 1
     * @magentoConfigFixture current_store sales/magento_rma/zip 11111
     * @magentoConfigFixture current_store sales/magento_rma/region_id 12
     * @magentoConfigFixture current_store sales/magento_rma/country_id US
     * @magentoConfigFixture current_store sales/magento_rma/store_name Default
     * @magentoDataFixture Magento/Rma/_files/rma.php
     */
    public function testCreateShippingLabel()
    {
        $expNumbers = ['794953535000'];
        $response = $this->getJsonData(__DIR__ . '/../../Fixtures/LabelResponse.json');

        $data = [
            'code' => 'fedex_SMART_POST',
            'carrier_title' => 'Federal Express',
            'method_title' => 'Smart Post',
            'price' => '9.04',
            'packages' => [
                [
                    'params' => [
                        'container' => 'YOUR_PACKAGING',
                        'weight' => '1',
                        'customs_value' => '9.99',
                        'length' => '20',
                        'width' => '20',
                        'height' => '20',
                        'weight_units' => 'POUND',
                        'dimension_units' => 'INCH',
                        'delivery_confirmation' => 'NO_SIGNATURE_REQUIRED',
                    ],
                    'items' => [
                        '1' => [
                            'qty' => '1',
                            'customs_value' => '9.99',
                            'price' => '9.9900',
                            'name' => 'Simple Product 1',
                            'weight' => '1.0000',
                            'product_id' => '1',
                            'order_item_id' => '1',
                        ],
                    ],
                ],
            ],
        ];

        $accessTokenResponse = [
            'access_token' => 'TestAccessToken',
            'token_type'=>'bearer',
            'expires_in' => 3600,
            'scope'=>'CXS'
        ];

        $this->curlFactory->method('create')
            ->willReturn($this->curlClient);

        $this->curlClient->method('post')
            ->willReturnSelf();

        $this->curlClient->method('getBody')
            ->willReturnOnConsecutiveCalls(json_encode($accessTokenResponse), $response);

        $rmaModel = $this->getRma('1');
        self::assertTrue($this->service->createShippingLabel($rmaModel, $data));

        $rmaTracks = $this->getRmaTracks((int)$rmaModel->getEntityId());

        self::assertCount(1, $rmaTracks);

        $actualNumbers = [];
        /** @var TrackInterface $track */
        foreach ($rmaTracks as $track) {
            $actualNumbers[] = $track->getTrackNumber();
        }
        self::assertEquals($expNumbers, $actualNumbers);
    }

    /**
     * Loads RMA entity by increment ID.
     *
     * @param string $incrementId
     * @return Rma
     */
    private function getRma(string $incrementId): Rma
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $incrementId)
            ->create();

        /** @var RmaRepositoryInterface $repository */
        $repository = $this->objectManager->get(RmaRepositoryInterface::class);
        $items = $repository->getList($searchCriteria)
            ->getItems();

        return array_pop($items);
    }

    /**
     * Gets list of RMA items.
     *
     * @param int $rmaId
     * @return array
     */
    private function getRmaTracks(int $rmaId): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('rma_entity_id', $rmaId)
            ->addFilter('is_admin', Shipping::IS_ADMIN_STATUS_ADMIN_LABEL_TRACKING_NUMBER)
            ->create();

        /** @var TrackRepositoryInterface $repository */
        $repository = $this->objectManager->get(TrackRepositoryInterface::class);
        return $repository->getList($searchCriteria)
            ->getItems();
    }

    /**
     * Gets Json document by provided path.
     *
     * @param string $filePath
     * @return string
     */
    private function getJsonData(string $filePath): string
    {
        return  file_get_contents($filePath);
    }
}
