<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManager\Factory\Compiled;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\ObjectManager\Resetter\ResetterFactory;
use Magento\Framework\ObjectManager\Resetter\ResetterInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Compiled Factory Preferences Decorator for Application Server.
 *
 * Decorator for Compiled Factory that uses preferences for create & get methods
 */
class CompiledFactoryPreferencesDecorator extends Compiled implements ResetAfterRequestInterface
{

    /**
     * @var ResetterInterface
     */
    private ResetterInterface $resetter;

    /**
     * @param ConfigInterface $config
     * @param array $sharedInstances
     * @param array $globalArguments
     */
    public function __construct(
        ConfigInterface $config,
        &$sharedInstances = [],
        $globalArguments = []
    ) {
        $this->resetter = ResetterFactory::create();
        parent::__construct($config, $sharedInstances, $globalArguments);
    }

    /**
     * Gets preference for type from config recursively
     *
     * @param string $requestedType
     * @return string
     */
    private function getPreferenceForType(string $requestedType) : string
    {
        for ($preferredType = $requestedType, $type=''; $type != $preferredType; $preferredType =
            $this->config->getPreference($type)) {
            $type = $preferredType;
        }
        return $preferredType;
    }

    /**
     * @inheritDoc
     *
     * Decorator that uses preference for requested type, and tracks ResetAfterRequestInterface objects
     */
    public function create($requestedType, array $arguments = [])
    {
        $preferredType = $this->getPreferenceForType($requestedType);
        $instance = parent::create($preferredType, $arguments);
        $this->resetter->addInstance($instance);
        return $instance;
    }

    /**
     * @inheritDoc
     *
     * Decorator that uses preference for requested type
     */
    protected function get($requestedType)
    {
        return parent::get($this->getPreferenceForType($requestedType));
    }

    /**
     * Reset state for all instances that we've created
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function _resetState(): void
    {
        $this->resetter->_resetState();
    }

    /**
     * @inheritDoc
     */
    public function setObjectManager(ObjectManagerInterface $objectManager)
    {
        parent::setObjectManager($objectManager);
        $this->resetter->setObjectManager($objectManager);
    }

    /**
     * Gets resetter
     *
     * @return ResetterInterface
     */
    public function getResetter() : ResetterInterface
    {
        return $this->resetter;
    }
}
