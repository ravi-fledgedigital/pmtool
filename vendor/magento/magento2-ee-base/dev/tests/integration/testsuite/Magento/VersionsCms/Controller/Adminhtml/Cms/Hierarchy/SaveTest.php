<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy;

use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Store\Model\Store;
use Magento\Store\Test\Fixture\Group as StoreGroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Checks save cms hierarchy
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    /** @var string */
    protected $uri = 'backend/admin/cms_hierarchy/save';

    /** @var GetPageByIdentifierInterface  */
    private $getPageByIdentifier;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getPageByIdentifier = $this->_objectManager->get(GetPageByIdentifierInterface::class);
        $this->fixtures = $this->_objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/pages.php
     * @return void
     */
    public function testSaveNodesHierarchy(): void
    {
        $parentPage = $this->getPageByIdentifier->execute('page100', Store::DEFAULT_STORE_ID);
        $childPage = $this->getPageByIdentifier->execute('page_design_blank', Store::DEFAULT_STORE_ID);

        $data = [
            'nodes_data' => json_encode([
                '_0' => [
                    'node_id' => '_0',
                    'page_id' => $parentPage->getId(),
                    'parent_node_id' => null,
                    'identifier' => 'node-1',
                    'scope' => 0,
                    'level' => 1,
                    'label' => 'Node 1',
                    'sort_order' => 3,
                    'top_menu_visibility' => 1,
                    'menu_visibility' => 1,
                    'pager_visibility' => 1,
                    'request_url' => $parentPage->getIdentifier(),
                ],
                '_1' => [
                    'node_id' => '_1',
                    'page_id' => $childPage->getId(),
                    'identifier' => 'node-2',
                    'scope' => 0,
                    'level' => 2,
                    'label' => 'Node 2',
                    'sort_order' => 3,
                    'top_menu_visibility' => 1,
                    'menu_visibility' => 1,
                    'pager_visibility' => 1,
                    'parent_node_id' => '_0',
                    'request_url' => $childPage->getIdentifier(),
                ],
            ])
        ];
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($data);
        $this->dispatch($this->uri);
        $this->assertSessionMessages($this->containsEqual(__('You have saved the hierarchy.')));
    }

    /**
     * Test save cms hierarchy with invalid url
     *
     * @return void
     */
    public function testSaveHierarchyWithInvalidUrl(): void
    {
        $identifier = 'admin';
        $reservedWords = 'admin, soap, rest, graphql, standard';
        $data = [
            'nodes_data' => json_encode([
                '_0' => [
                    'node_id' => '_0',
                    'page_id' => null,
                    'parent_node_id' => null,
                    'identifier' => $identifier,
                    'scope' => 0,
                    'level' => 1,
                    'label' => 'Node 3',
                    'sort_order' => 3,
                    'top_menu_visibility' => 1,
                    'menu_visibility' => 1,
                    'pager_visibility' => 1,
                ],
            ]),
            'identifier' => $identifier,
        ];

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($data);
        $this->dispatch($this->uri);
        $this->assertSessionMessages(
            $this->containsEqual(
                __(sprintf(
                    'URL key "%s" matches a reserved endpoint name (%s). Use another URL key.',
                    $identifier,
                    $reservedWords
                ))
            )
        );
    }

    /**
     * Test save cms hierarchy with specific store
     *
     * @return void
     */
    #[
        AppArea('adminhtml'),
        DataFixture(WebsiteFixture::class, as: 'website2'),
        DataFixture(StoreGroupFixture::class, ['website_id' => '$website2.id$'], 'store_group2'),
        DataFixture(StoreFixture::class, ['store_group_id' => '$store_group2.id$'], 'store2'),
    ]
    public function testSaveHierarchyWithSpecificStore(): void
    {
        $storeId = $this->fixtures->get('store2')->getId();
        $storeCode=$this->fixtures->get('store2')->getCode();
        $data = [
            'store' => $storeId,
        ];
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($data);
        $this->dispatch($this->uri);
        $actualUrl = '';
        foreach ($this->getResponse()->getHeaders() as $header) {
            if ($header->getFieldName() == 'Location') {
                $actualUrl = $header->getFieldValue();
                break;
            }
        }
        $this->assertStringNotContainsString('admin/cms_hierarchy/index/store/'.$storeCode, $actualUrl);
        $this->assertStringContainsString('admin/cms_hierarchy/index/store/'.$storeId, $actualUrl);
    }
}
