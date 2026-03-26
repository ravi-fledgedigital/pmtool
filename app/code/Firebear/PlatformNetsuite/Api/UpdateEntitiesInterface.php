<?php

namespace Firebear\PlatformNetsuite\Api;


/**
 * Interface UpdateEntitiesInterface
 *
 * @package Firebear\PlatformNetsuite\Api
 */
interface UpdateEntitiesInterface
{
    /**
     * @param string $entityType
     * @param int $netsuiteInternalId
     * @param int $jobId
     * @return mixed
     */
    public function updateEntity($entityType,$netsuiteInternalId, $jobId);

}
