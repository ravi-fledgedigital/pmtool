<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\AdminGws\Model\CallbackInvoker;
use Magento\AdminGws\Model\ConfigInterface;
use Magento\AdminGws\Model\ForceWhitelistRegistry;
use Magento\AdminGws\Model\Role;
use Magento\Framework\Event\ObserverInterface;

class ValidateModelLoadAfter implements ObserverInterface
{
    /**
     * @var Role
     */
    protected $role;

    /**
     * @var CallbackInvoker
     */
    protected $callbackInvoker;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ForceWhitelistRegistry
     */
    private ForceWhitelistRegistry $forceWhitelistRegistry;

    /**
     * @param Role $role
     * @param CallbackInvoker $callbackInvoker
     * @param ConfigInterface $config
     * @param ForceWhitelistRegistry $forceWhitelistRegistry
     */
    public function __construct(
        Role $role,
        CallbackInvoker $callbackInvoker,
        ConfigInterface $config,
        ForceWhitelistRegistry $forceWhitelistRegistry
    ) {
        $this->callbackInvoker = $callbackInvoker;
        $this->role = $role;
        $this->config = $config;
        $this->forceWhitelistRegistry = $forceWhitelistRegistry;
    }

    /**
     * Initialize a model after loading it
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->role->getIsAll()) {
            return;
        }

        $model = $observer->getEvent()->getObject();

        if (!($callback = $this->config->getCallbackForObject($model, 'model_load_after'))
        ) {
            return;
        }

        if ($this->forceWhitelistRegistry->isLoadingForceAllowed($model)) {
            return;
        }

        $this->callbackInvoker
            ->invoke(
                $callback,
                $this->config->getGroupProcessor('model_load_after'),
                $model
            );
    }
}
