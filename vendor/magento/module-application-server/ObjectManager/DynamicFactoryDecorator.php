<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\ObjectManager;

use Magento\Framework\ObjectManager\Factory\Dynamic\Developer;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\ObjectManager\Resetter\ResetterFactory;
use Magento\Framework\ObjectManager\Resetter\ResetterInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Dynamic Factory Decorator for Application Server.
 *
 * Decorator that uses Resetter to track and reset objects
 */
class DynamicFactoryDecorator extends Developer implements ResetAfterRequestInterface
{
    /**
     * @var ResetterInterface
     */
    private ResetterInterface $resetter;

    /**
     * @param array $args
     */
    public function __construct(...$args)
    {
        $this->resetter = ResetterFactory::create();
        parent::__construct(...$args);
    }

    /**
     * @inheritDoc
     */
    public function create($requestedType, array $arguments = [])
    {
        $instance = parent::create($requestedType, $arguments);
        $this->resetter->addInstance($instance);
        return $instance;
    }

    /**
     * Reset state for all instances that we've created
     *
     * @param array $sharedInstances
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function _resetState(array $sharedInstances = []): void
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
