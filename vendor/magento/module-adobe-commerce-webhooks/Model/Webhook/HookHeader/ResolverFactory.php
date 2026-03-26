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

namespace Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader;

use Magento\AdobeCommerceWebhooks\Model\HeaderResolverInterface;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;
use Exception;

/**
 * Creates instance of the header resolver
 */
class ResolverFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(private ObjectManagerInterface $objectManager)
    {
    }

    /**
     * Creates instance of the header resolver by provided class name.
     *
     * Throws an exception if resolver class can't be created or doesn't implement @see HeaderResolverInterface
     *
     * @param string $className
     * @return HeaderResolverInterface
     * @throws InvalidArgumentException
     */
    public function create(string $className): HeaderResolverInterface
    {
        try {
            $resolver = $this->objectManager->get($className);
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                __('Can\'t create resolver class "%1": "%2"', $className, $e->getMessage())
            );
        }

        if (!$resolver instanceof HeaderResolverInterface) {
            throw new InvalidArgumentException(
                __('Resolver class "%1" doesn\'t implement "%2"', $className, HeaderResolverInterface::class)
            );
        }

        return $resolver;
    }
}
