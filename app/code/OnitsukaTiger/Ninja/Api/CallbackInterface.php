<?php

namespace OnitsukaTiger\Ninja\Api;

use OnitsukaTiger\Ninja\Api\Response\ResponseInterface;

interface CallbackInterface
{
    /**
     * Status update from Ninja
     * @param string $countryCode
     * @return ResponseInterface
     */
    public function updateStatus($countryCode);

    /**
     * Complete from Ninja
     * @param string $countryCode
     * @return ResponseInterface
     */
    public function complete($countryCode);

    /**
     * Return from Ninja
     * @param string $countryCode
     * @return ResponseInterface
     */
    public function return($countryCode);
}
