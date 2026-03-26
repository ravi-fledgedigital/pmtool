<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Plugin\Customer\Block\Adminhtml\Group;

use Magento\AdminGws\Model\Role;
use Magento\AdminGws\Plugin\Customer\Block\Adminhtml\Group\IsCustomerAllowedToAddCustomerGroupButtonPlugin;
use Magento\Customer\Block\Adminhtml\Group\AddCustomerGroupButton;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for verifying customer has access for add customer group button
 */
class IsCustomerAllowedToAddCustomerGroupButtonPluginTest extends TestCase
{
    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var AddCustomerGroupButton
     */
    private $subject;

    /**
     * @var IsCustomerAllowedToAddCustomerGroupButtonPlugin
     */
    private $plugin;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->roleMock = $this->createMock(Role::class);
        $this->subject = $this->createMock(AddCustomerGroupButton::class);
        $this->plugin = new IsCustomerAllowedToAddCustomerGroupButtonPlugin($this->roleMock);
    }

    /**
     * Verify that afterGetButtonData plugin works as expected
     *
     * @param bool $isSuperAdmin
     * @param array $buttonData
     * @param array $actualResult
     * @dataProvider afterGetDataDataProvider
     */
    public function testAfterGetButtonData(bool $isSuperAdmin, array $buttonData, array $actualResult): void
    {
        $this->roleMock->expects($this->any())
            ->method('getIsAll')
            ->willReturn($isSuperAdmin);

        $expectedResult = $this->plugin->afterGetButtonData($this->subject, $buttonData);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Data provider for testAfterGetButtonData
     *
     * @return array
     */
    public function afterGetDataDataProvider() : array
    {
        return [
        'for super admin customer group' =>
            [
                true,
                ['label' => 'test', 'class' => 'primary', 'url' => 'http://test.com/', 'sort_order' => 10],
                ['label' => 'test', 'class' => 'primary', 'url' => 'http://test.com/', 'sort_order' => 10]
            ],
        'for restricted admin customer group' =>
            [
                false,
                ['label' => 'test', 'class' => 'primary', 'url' => 'http://test.com/', 'sort_order' => 10],
                []
            ]
        ];
    }
}
