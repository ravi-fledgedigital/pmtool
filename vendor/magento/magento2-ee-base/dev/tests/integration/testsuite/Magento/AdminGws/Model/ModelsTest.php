<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AdminGws\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap as Helper;
use PHPUnit\Framework\TestCase;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Role;
use Magento\TestFramework\Bootstrap;
use Magento\AdminGws\Model\Role as GwsRole;

/**
 * Test for restrictions triggered when using models.
 *
 * @magentoAppArea adminhtml
 */
class ModelsTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var RoleFactory
     */
    private $roleFactory;

    /**
     * @var GwsRole
     */
    private $role;

    /**
     * @var ForceWhitelistRegistry
     */
    private $forceWhitelistRegistry;

    /**
     * @var CustomerRegistry
     */
    private mixed $customerRegistry;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Helper::getObjectManager();
        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $this->websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
        $this->roleFactory = $objectManager->get(RoleFactory::class);
        $this->role = $objectManager->get(GwsRole::class);
        $this->forceWhitelistRegistry = $objectManager->get(ForceWhitelistRegistry::class);
        $this->customerRegistry = $objectManager->get(CustomerRegistry::class);
    }

    /**
     * Test restrictions applying to updating customers.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Store/_files/website.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCustomerSave()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('More permissions are needed to save this item');

        $customer = $this->customerRepository->get('customer@example.com');

        /** @var Role $role */
        $role = $this->roleFactory->create();
        $role = $role->load(Bootstrap::ADMIN_ROLE_NAME, 'role_name');
        //Setting role's scope to test website.
        $testWebsite = $this->websiteRepository->get('test');
        $role->setGwsIsAll(0);
        $role->setGwsWebsites([$testWebsite->getId()]);
        $role->setGwsRelevantWebsites([(int)$testWebsite->getId()]);
        $this->role->setAdminRole($role);

        //Saving customer from restricted website.
        $customer->setWebsiteId($testWebsite->getId());
        $this->customerRepository->save($customer);
    }

    /**
     * Test restrictions applying to updating customers.
     *
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Store/_files/website.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCustomerLoad()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('More permissions are needed to view this item');

        /** @var Role $role */
        $role = $this->roleFactory->create();
        $role = $role->load(Bootstrap::ADMIN_ROLE_NAME, 'role_name');
        //Setting role's scope to test website.
        $testWebsite = $this->websiteRepository->get('test');
        $role->setGwsIsAll(0);
        $role->setGwsWebsites([$testWebsite->getId()]);
        $role->setGwsRelevantWebsites([(int)$testWebsite->getId()]);
        $this->role->setAdminRole($role);

        // Loading customer from restricted website.
        $this->customerRepository->get('customer@example.com');
    }

    /**
     * Test restrictions applying to updating customers.
     *
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Store/_files/website.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     */
    public function testCustomerForceLoad()
    {
        /** @var Role $role */
        $role = $this->roleFactory->create();
        $role = $role->load(Bootstrap::ADMIN_ROLE_NAME, 'role_name');
        //Setting role's scope to test website.
        $testWebsite = $this->websiteRepository->get('test');
        $role->setGwsIsAll(0);
        $role->setGwsWebsites([$testWebsite->getId()]);
        $role->setGwsRelevantWebsites([(int)$testWebsite->getId()]);
        $this->role->setAdminRole($role);

        $this->forceWhitelistRegistry->forceAllowLoading(Customer::class);
        // Loading customer from restricted website. Fires without exception
        $this->customerRepository->get('customer@example.com');

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('More permissions are needed to view this item');
        // Remove whitelist.
        $this->forceWhitelistRegistry->restore(Customer::class);
        $this->customerRegistry->_resetState();
        $this->customerRepository->get('customer@example.com');
    }
}
