<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained from
 * Adobe.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * This fixture is enabling AsyncOrder, this is writing 'checkout' => ['async' => 1] in the app/etc/env.php,
 * it is simulating this command bin/magento setup:config:set --checkout-async 1
 */
class AsyncMode implements RevertibleDataFixtureInterface
{
    /**
     * @var Writer
     */
    private Writer $writer;

    /**
     * @var ReinitableConfigInterface
     */
    private ReinitableConfigInterface $reinitableConfig;

    /**
     * @param Writer $writer
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        Writer $writer,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->writer = $writer;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters
     */
    public function apply(array $data = []): ?DataObject
    {
        $configData = [
            'checkout' => [
                'async' => 1
            ]
        ];
        $this->writer->saveConfig([ConfigFilePool::APP_ENV => $configData], true);
        $this->reinitableConfig->reinit();

        return null;
    }

    /**
     * @param DataObject $data
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function revert(DataObject $data): void
    {
        $configData = [
            'checkout' => [
                'async' => 0,
                'deferred_total_calculating' => 0
            ]
        ];
        $this->writer->saveConfig([ConfigFilePool::APP_ENV => $configData], true);
        $this->reinitableConfig->reinit();
    }
}
