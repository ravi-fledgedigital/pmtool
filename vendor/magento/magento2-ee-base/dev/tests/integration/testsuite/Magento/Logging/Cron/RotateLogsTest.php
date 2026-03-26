<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */

declare(strict_types=1);

namespace Magento\Logging\Cron;

use Magento\Framework\Flag\FlagResource;
use Magento\Logging\Model\FlagFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea crontab
 */
class RotateLogsTest extends TestCase
{
    /**
     * @var RotateLogs
     */
    private $subject;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @inheritDoc
     */

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->subject = $this->objectManager->create(RotateLogs::class);

        // Remove previous records if exist
        $resource = $this->objectManager->get(FlagResource::class);
        $resource->getConnection()->delete($resource->getMainTable(), ['flag_code = ?' => 'log_rotation']);
    }

    /**
     * Test Cron job for logs rotation
     *
     * Ensure tha flag update is performed if Log rotation frequency is set to Weekly (7 days).
     *
     * Since we cannot set present date to future date, then Flag dates will be saved
     * as the past dates relative to present.
     *
     * @magentoConfigFixture default/system/rotation/frequency 7
     */
    public function testExecute()
    {
        $sevenDaysAgo = time() - (3600 * 24 * 7);

        $flagFactory = $this->objectManager->get(FlagFactory::class);
        $flag = $flagFactory->create();

        $flag->setFlagData($sevenDaysAgo)->save();
        $this->subject->execute();
        $loadedFlagAfterSevenDays = $flagFactory->create()->loadSelf();

        $this->assertNotEquals(
            $sevenDaysAgo,
            $loadedFlagAfterSevenDays->getFlagData(),
            'Loaded flag date should not equal seven days ago, rotation frequency was met, flag date was updated'
        );
    }
}
