<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTAdobeDataLayer\Model\DataLayer;

use Vaimo\OTAdobeDataLayer\Api\Data\DataLayerResponseInterface;
use Vaimo\OTAdobeDataLayer\Api\Data\DataLayerResponseInterfaceFactory;
use Vaimo\OTAdobeDataLayer\Api\DataLayerProviderInterface;
use Vaimo\OTAdobeDataLayer\Model\DataLayer\Component\ComponentInterface;

class Provider implements DataLayerProviderInterface
{
    private DataLayerResponseInterfaceFactory $dataLayerResponseFactory;

    /**
     * @var array<string, ComponentInterface>
     */
    private array $dataLayerComponents;

    /**
     * @param array<string, ComponentInterface> $dataLayerComponents
     */
    public function __construct(
        DataLayerResponseInterfaceFactory $dataLayerResponseFactory,
        array $dataLayerComponents = []
    ) {
        $this->dataLayerResponseFactory = $dataLayerResponseFactory;
        $this->dataLayerComponents = $dataLayerComponents;
    }

    /**
     * @param string[] $requestedComponents
     * @return DataLayerResponseInterface
     */
    public function getDataLayer(array $requestedComponents): DataLayerResponseInterface
    {
        $result = [];

        foreach ($requestedComponents as $componentName) {
            if (!isset($this->dataLayerComponents[$componentName])) {
                continue;
            }

            $result[$componentName] = $this->dataLayerComponents[$componentName]->getComponentData();
        }

        return $this->dataLayerResponseFactory->create(['data' => $result]);
    }
}
