<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Model\Cron;

class Sync
{
    /**
     * @var \Aitoc\SendGrid\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Aitoc\SendGrid\Model\SyncContacts
     */
    private $syncContacts;

    public function __construct(
        \Aitoc\SendGrid\Model\ConfigProvider $configProvider,
        \Aitoc\SendGrid\Model\SyncContacts $syncContacts
    ) {
        $this->configProvider = $configProvider;
        $this->syncContacts = $syncContacts;
    }

    public function execute()
    {
        if ($this->configProvider->isCronEnabled()) {
            $this->syncContacts->sync();
        }
    }
}
