<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model;

use Magento\AdminGws\Model\ForceWhitelistRegistry;
use PHPUnit\Framework\TestCase;

class ForceWhitelistRegistryTest extends TestCase
{
    /**
     * @var ForceWhitelistRegistry
     */
    private $model;

    public function setUp(): void
    {
        $this->model = new ForceWhitelistRegistry();
    }

    /**
     * Test allow method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testForceAllowLoading(): void
    {
        $this->model->forceAllowLoading(\stdClass::class);
        $this->assertEquals([\stdClass::class => 1], $this->retrieveDisabledList());

        $this->model->forceAllowLoading(\stdClass::class);
        $this->model->forceAllowLoading(\stdClass::class);
        $this->assertEquals([\stdClass::class => 3], $this->retrieveDisabledList());
    }

    /**
     * Test restore method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testRestore(): void
    {
        $this->setDisabledList([\stdClass::class => 1]);
        $this->model->restore(\stdClass::class);
        $this->assertEquals([\stdClass::class => 0], $this->retrieveDisabledList());

        $this->setDisabledList([\stdClass::class => 8]);
        $this->model->restore(\stdClass::class);
        $this->model->restore(\stdClass::class);
        $this->model->restore(\stdClass::class);
        $this->assertEquals([\stdClass::class => 5], $this->retrieveDisabledList());
    }

    /**
     * Test is allowed method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testIsLoadingForceAllowed(): void
    {
        $object = new \StdClass;
        $result = $this->model->isLoadingForceAllowed($object);
        $this->assertFalse($result);

        $this->setDisabledList([\stdClass::class => 1]);
        $result = $this->model->isLoadingForceAllowed($object);
        $this->assertTrue($result);

        $this->setDisabledList([\stdClass::class => 0]);
        $result = $this->model->isLoadingForceAllowed($object);
        $this->assertFalse($result);

        $this->setDisabledList([\stdClass::class => -3]);
        $result = $this->model->isLoadingForceAllowed($object);
        $this->assertFalse($result);
    }

    /**
     * Get private whitelist from class to test methods
     *
     * @return array
     * @throws \ReflectionException
     */
    private function retrieveDisabledList(): array
    {
        $reflection = new \ReflectionClass(get_class($this->model));
        $property = $reflection->getProperty('disabledList');
        $property->setAccessible(true);

        return $property->getValue($this->model);
    }

    /**
     * Set private whitelist value
     *
     * @param array $disabledList
     * @return void
     * @throws \ReflectionException
     */
    private function setDisabledList(array $disabledList): void
    {
        $reflection = new \ReflectionClass(get_class($this->model));
        $property = $reflection->getProperty('disabledList');
        $property->setAccessible(true);

        $property->setValue($this->model, $disabledList);
    }
}
