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

namespace Magento\AdobeCommerceWebhooks\Model\Config;

use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\ValidationStateInterface;
use Magento\Framework\Config\Reader\Filesystem;

/**
 * Reader for webhooks.xml configuration files
 */
class Reader extends Filesystem
{
    public const CONFIGURATION_FILE = 'webhooks.xml';

    /**
     * List of id attributes for merge
     *
     * @var array
     */
    protected $_idAttributes = [
        '/config/method' => [Webhook::NAME, Webhook::TYPE],
        '/config/method/hooks/batch' => Webhook\Batch::ORDER,
        '/config/method/hooks/batch/hook' => Webhook\Hook::NAME,
    ];

    /**
     * @param FileResolverInterface $fileResolver
     * @param Converter $converter
     * @param SchemaLocator $schemaLocator
     * @param ValidationStateInterface $validationState
     * @param string $fileName
     *
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState,
        $fileName = self::CONFIGURATION_FILE
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName
        );
    }

    /**
     * Load configuration scope and merge it with primary configuration scope
     *
     * @param string|null $scope
     * @return array
     */
    public function read($scope = null)
    {
        $fileList = $this->_fileResolver->get($this->_fileName, 'primary');
        if (!count($fileList)) {
            return parent::read($scope);
        }

        return array_merge(parent::read($scope), $this->_readFiles($fileList));
    }
}
