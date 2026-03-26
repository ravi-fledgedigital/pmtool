<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Controller\Index;

use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Tests for editing a customer gift registry
 */
class EditPostTest extends AbstractController
{
    /**
     * Test successful gift registry edit
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_person_simple.php
     * @return void
     * @throws NoSuchEntityException|LocalizedException
     */
    public function testGiftRegistryEdit(): void
    {
        /** @var \Magento\Store\Model\StoreManager $store */
        $store = $this->_objectManager->get(\Magento\Store\Model\StoreManager::class);
        $session = $this->_objectManager->get(Session::class);
        $customerRegistry = $this->_objectManager->get(CustomerRegistry::class);

        $customer = $customerRegistry->retrieveByEmail(
            'john.doe@magento.com',
            $store->getDefaultStoreView()->getWebsiteId()
        );
        $customer->setPassword('password');
        $customer->save();
        $customerRegistry->remove($customer->getId());
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'login' => [
                'username' => 'john.doe@magento.com',
                'password' => 'password',
            ],
        ]);
        $this->dispatch('customer/account/loginPost');
        $this->assertTrue($session->isLoggedIn());
        $session->setCustomerId($customer->getId());

        $samplePost = $this->getSampleGiftRegistryPost();
        unset($samplePost['registrant']);
        $this->getRequest()->setDispatched(true);
        $this->getRequest()->setActionName('editPost');
        $this->getRequest()->setRouteName('magento_giftregistry');
        $this->getRequest()->setControllerName('index');

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($samplePost);
        $this->dispatch('/giftregistry/index/editPost/');
        $this->assertRedirect();
        $this->assertSessionMessages(
            $this->equalTo(['You saved this gift registry.']),
            MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * Test add registrant to non-existing gift registry
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_person_simple.php
     * @magentoConfigFixture current_store customer/startup/redirect_dashboard 0
     * @magentoConfigFixture current_store customer/captcha/enable 0
     * @return void
     * @throws LocalizedException
     * @throws \Exception
     */
    #[
        DataFixture(
            \Magento\Customer\Test\Fixture\Customer::class,
            [
                'email' => 'customer_1@example.com'
            ],
            as: 'customer1'
        ),
        DataFixture(
            \Magento\GiftRegistry\Test\Fixture\GiftRegistry::class,
            [
                'type_id' => 1,
                'customer_id' => '$customer1.id$',
                'website_id' => '$customer1.websiteId$',
                'is_public' => 0,
                'title' => 'Gift Registry',
                'is_active' => true,
            ],
            as: 'customer1GiftRegistry'
        )
    ]
    public function testExecuteNonExistingGiftRegistryEntity(): void
    {
        /** @var \Magento\Store\Model\StoreManager $store */
        $store = $this->_objectManager->get(\Magento\Store\Model\StoreManager::class);
        $session = $this->_objectManager->get(Session::class);
        $customerRegistry = $this->_objectManager->get(CustomerRegistry::class);
        $fixtures = DataFixtureStorageManager::getStorage();

        $firstGiftRegistryEntity = $fixtures->get('customer1GiftRegistry');
        $customer = $customerRegistry->retrieveByEmail(
            'john.doe@magento.com',
            $store->getDefaultStoreView()->getWebsiteId()
        );
        $customer->setPassword('password');
        $customer->save();
        $customerRegistry->remove($customer->getId());
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'login' => [
                'username' => 'john.doe@magento.com',
                'password' => 'password',
            ],
        ]);
        $this->dispatch('customer/account/loginPost');
        $this->assertTrue($session->isLoggedIn());
        $session->setCustomerId($customer->getId());

        $samplePost = $this->getSampleGiftRegistryPost();
        $samplePost['entity_id'] = $firstGiftRegistryEntity->getId() + 1;

        $this->getRequest()->setDispatched(true);
        $this->getRequest()->setActionName('editPost');
        $this->getRequest()->setRouteName('magento_giftregistry');
        $this->getRequest()->setControllerName('index');

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($samplePost);
        $this->dispatch('/giftregistry/index/editPost/');
        $this->assertSessionMessages(
            $this->equalTo(["The gift registry ID is incorrect. Verify the ID and try again."]),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Test add registrant to gift registry with wrong person_id
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/GiftRegistry/_files/gift_registry_person_simple.php
     * @magentoConfigFixture current_store customer/startup/redirect_dashboard 0
     * @magentoConfigFixture current_store customer/captcha/enable 0
     * @return void
     * @throws LocalizedException
     * @throws \Exception
     */
    #[
        DataFixture(
            \Magento\Customer\Test\Fixture\Customer::class,
            [
                'email' => 'customer_1@example.com'
            ],
            as: 'customer1'
        ),
        DataFixture(
            \Magento\GiftRegistry\Test\Fixture\GiftRegistry::class,
            [
                'type_id' => 1,
                'customer_id' => '$customer1.id$',
                'website_id' => '$customer1.websiteId$',
                'is_public' => 0,
                'title' => 'Gift Registry',
                'is_active' => true,
            ],
            as: 'customer1GiftRegistry'
        ),
        DataFixture(
            \Magento\GiftRegistry\Test\Fixture\GiftRegistryPerson::class,
            [
                'entity_id' => '$customer1GiftRegistry.entityId$',
                'firstname' => 'First',
                'lastname' => 'Last',
                'role' => 'Role',
                'custom' => [
                    'key' => 'value',
                ],
            ],
            as: 'person1GiftRegistry'
        )
    ]
    public function testExecuteWrongPersonId(): void
    {
        /** @var \Magento\Store\Model\StoreManager $store */
        $store = $this->_objectManager->get(\Magento\Store\Model\StoreManager::class);
        $session = $this->_objectManager->get(Session::class);
        $customerRegistry = $this->_objectManager->get(CustomerRegistry::class);
        $fixtures = DataFixtureStorageManager::getStorage();

        $firstGiftRegistryEntity = $fixtures->get('customer1GiftRegistry');
        $firstRegistrant = $fixtures->get('person1GiftRegistry');

        $customer = $customerRegistry->retrieveByEmail(
            'john.doe@magento.com',
            $store->getDefaultStoreView()->getWebsiteId()
        );
        $customer->setPassword('password');
        $customer->save();
        $customerRegistry->remove($customer->getId());

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'login' => [
                'username' => 'john.doe@magento.com',
                'password' => 'password',
            ],
        ]);
        $this->dispatch('customer/account/loginPost');
        $this->assertTrue($session->isLoggedIn());
        $session->setCustomerId($customer->getId());

        $samplePost = $this->getSampleGiftRegistryPost();
        $samplePost['entity_id'] = $firstGiftRegistryEntity->getId();
        $samplePost['registrant']['person_id'] = $firstRegistrant->getId();

        $this->getRequest()->setDispatched(true);
        $this->getRequest()->setActionName('editPost');
        $this->getRequest()->setRouteName('magento_giftregistry');
        $this->getRequest()->setControllerName('index');

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($samplePost);
        $this->dispatch('/giftregistry/index/editPost/');
        $this->assertSessionMessages(
            $this->equalTo(["The gift registry ID is incorrect. Verify the ID and try again."]),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @return array
     */
    private function getSampleGiftRegistryPost(): array
    {
        return [
            'type_id' => 1,
            'title' => 'title',
            'message' => 'message',
            'is_public' => 0,
            'is_active' => 0,
            'event_country' => 'US',
            'event_country_region' => '16',
            'event_country_region_text' => '',
            'event_date' => date('Y-m-d'),
            'registrant' => [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => 'registrant@domain.com'
            ],
            'address' => [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'company' => '',
                'street' => [
                    '1st Street'
                ],
                'city' => 'Montgomery',
                'region_id' => '1',
                'region' => 'Alabama',
                'postcode' => '12345',
                'country_id' => 'US',
                'telephone' => '1234567890',
                'fax' => ''
            ]
        ];
    }
}
