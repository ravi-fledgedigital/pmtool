<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\ApplicationServer\Plugin;

use WeakMap;
use Magento\Framework\Session\SessionManagerInterface;

/*
 * Keeps track of SessionManagers and starts them all.
 *
 * This is required because the start() method of all the SessionManagers gets called at construction, but in our case,
 * the session data needs to loaded at the start of each request's processing.
 */
class SessionMangerStarter
{
    /** @var WeakMap */
    private WeakMap $sessionManagers;

    /**
     * This is a constructor!
     */
    public function __construct()
    {
        $this->sessionManagers = new WeakMap();
    }

    /**
     * Plugin that tracks SessionManagers that start
     *
     * @param SessionManagerInterface $sessionManager
     * @param mixed $arguments
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeStart(SessionManagerInterface $sessionManager, ...$arguments)
    {
        $this->sessionManagers[$sessionManager] = true;
        return null;
    }

    /**
     * Starts SessionManagers that have already previously started
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function startSessions(): void
    {
        foreach ($this->sessionManagers as $sessionManager => $value) {
            if (!$sessionManager) {
                continue;
            }
            $sessionManager->start();
        }
    }
}
