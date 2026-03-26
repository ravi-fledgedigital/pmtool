<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Api\Data;

/**
 * Defines the logs table data model
 *
 * @api
 */
interface LogInterface
{
    public const FIELD_ID = 'id';
    public const FIELD_MESSAGE = 'message';
    public const FIELD_LEVEL = 'level';
    public const FIELD_TIMESTAMP = 'timestamp';

    /**
     * Get id field
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Get the log message
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Set the log message
     *
     * @param string $message
     * @return LogInterface
     */
    public function setMessage(string $message): LogInterface;

    /**
     * Get the log level
     *
     * @return string
     */
    public function getLevel(): string;

    /**
     * Set the log level
     *
     * @param string $level
     * @return LogInterface
     */
    public function setLevel(string $level): LogInterface;

    /**
     * Get the log timestamp
     *
     * @return string
     */
    public function getTimestamp(): string;

    /**
     * Set the log timestamp
     *
     * @param string $timestamp
     * @return LogInterface
     */
    public function setTimestamp(string $timestamp): LogInterface;
}
