<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7AsicsIntegration\Model\Import;

use Magento\ImportExport\Model\Import\AbstractSource;

class ArraySource extends AbstractSource
{
    /**
     * @var string[][]
     */
    private array $data;

    /**
     * @param string[][] $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        parent::__construct(['sku', 'scene7_available_image_angles']);
    }

    /**
     * @return string[]|false
     */
    // phpcs:disable VCQP.PHP.ProtectedClassMember.FoundProtected
    // phpcs:disable VCQP.Methods.MethodDeclaration.Underscore
    protected function _getNextRow()
    {
        if (isset($this->data[$this->_key])) {
            return $this->data[$this->_key];
        }

        return false;
    }
}
