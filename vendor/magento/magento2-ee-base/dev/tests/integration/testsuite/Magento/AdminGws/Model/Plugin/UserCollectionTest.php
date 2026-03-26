<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model\Plugin;

use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Role as AuthorizationRole;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use Magento\Authorization\Model\ResourceModel\Role\Grid\Collection as UserRoleCollection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use Magento\Store\Model\Store;
use Magento\Backend\Model\Auth\Session;

/**
 * Test class for \Magento\AdminGws\Model\Plugin\UserCollection.
 *
 * @magentoAppArea adminhtml
 */
class UserCollectionTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * Test getting real size of user collection by restricted user
     *
     * @magentoDataFixture Magento/Store/_files/multiple_websites_with_store_groups_stores.php
     * @magentoDataFixture Magento/AdminGws/_files/two_users_with_role.php
     * @magentoDataFixture Magento/User/_files/user_with_custom_role.php
     */
    public function testGetSizeForRestrictedAdmin()
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var \Magento\User\Model\User $currentAdmin */
        $currentAdmin = $objectManager->create(User::class)
            ->loadByUsername('user');
        /** @var \Magento\Backend\Model\Auth\Session $authSession */
        $authSession = $objectManager->create(Session::class);
        $authSession->setUser($currentAdmin);

        /** @var \Magento\Authorization\Model\RoleFactory $roleFactory */
        $roleFactory = $objectManager->create(RoleFactory::class);
        $role = $roleFactory->create()->load(1);

        $gwsRole = $objectManager->get(AdminGwsRole::class);
        $gwsRole->setAdminRole($role);

        $userCollection = $objectManager->create(UserCollection::class);
        $userRoleCollection = $objectManager->create(UserRoleCollection::class);
        $this->assertEquals(4, $userCollection->getSize());
        $this->assertEquals(2, $userRoleCollection->getSize());

        $store = $objectManager->get(Store::class);
        $store->load('third_store_view', 'code');

        $role = $objectManager->get(AuthorizationRole::class);
        $role->load('test_custom_role', 'role_name');
        $roleId = $role->getId();

        $role = $objectManager->get(RoleFactory::class)->create();
        $role->setGwsIsAll(0)
            ->setGwsWebsites('1,'.(int)$store->getWebsiteId())
            ->setRoleId($roleId)
            ->save();

        $role = $objectManager->get(AuthorizationRole::class);
        $role->load($roleId);
        $gwsRole = $objectManager->get(AdminGwsRole::class);
        $gwsRole->setAdminRole($role);
        $userCollection = $objectManager->create(UserCollection::class);
        $userRoleCollection = $objectManager->create(UserRoleCollection::class);
        $this->assertEquals(1, $userCollection->getSize());
        $this->assertEquals(1, $userRoleCollection->getSize());

        $roleFactory = $objectManager->create(RoleFactory::class);
        $role = $roleFactory->create()->load(1);
        $gwsRole = $objectManager->get(AdminGwsRole::class);
        $gwsRole->setAdminRole($role);
    }
}
