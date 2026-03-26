<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\WebhookRunner\Request;

use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\SensitiveDataSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see SensitiveDataSanitizer
 */
class SensitiveDataSanitizerTest extends TestCase
{
    public function testSanitizeWithSensitiveDataRegexp(): void
    {
        $sanitizer = new SensitiveDataSanitizer([], ['/password/i', '/token/i']);
        $expected = [
            'username' => 'John',
            'password' => '***',
            'oldPassword' => '***',
            'token' => '***',
            'address' => [
                'street' => 'Main Street',
                'city' => 'New York',
                'password' => '***',
                'customer' => [
                    'password_hash' => '***'
                ]
            ]
        ];

        $this->assertEquals($expected, $sanitizer->sanitize($this->getDataToSanitize()));
    }

    public function testSanitizeWithSensitiveDataNoRegexp(): void
    {
        $sanitizer = new SensitiveDataSanitizer(['password'], []);
        $expected = [
            'username' => 'John',
            'password' => '***',
            'oldPassword' => 'password123',
            'token' => 'token123',
            'address' => [
                'street' => 'Main Street',
                'city' => 'New York',
                'password' => '***',
                'customer' => [
                    'password_hash' => 'password123'
                ]
            ]
        ];

        $this->assertEquals($expected, $sanitizer->sanitize($this->getDataToSanitize()));
    }

    /**
     * Returns data to sanitize
     *
     * @return array
     */
    private function getDataToSanitize(): array
    {
        return [
            'username' => 'John',
            'password' => 'password123',
            'oldPassword' => 'password123',
            'token' => 'token123',
            'address' => [
                'street' => 'Main Street',
                'city' => 'New York',
                'password' => 'password123',
                'customer' => [
                    'password_hash' => 'password123'
                ]
            ]
        ];
    }
}
