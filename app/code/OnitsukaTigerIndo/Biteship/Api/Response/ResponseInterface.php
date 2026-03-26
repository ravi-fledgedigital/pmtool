<?php


namespace OnitsukaTigerIndo\Biteship\Api\Response;

interface ResponseInterface
{
    /**
     * Gets the success.
     *
     * @return bool
     */
    public function getSuccess();

    /**
     * Success function
     *
     * @param bool $success
     * @return void
     */
    public function setSuccess(bool $success);

    /**
     * Function string
     *
     * @return string
     */
    public function toString();
}
