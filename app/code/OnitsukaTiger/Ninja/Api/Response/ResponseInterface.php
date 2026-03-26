<?php

namespace OnitsukaTiger\Ninja\Api\Response;

/**
 * Interface ResponseInterface
 * @package OnitsukaTiger\Ninja\Api\Response
 */
interface ResponseInterface
{
    /**
     * @return bool
     */
    public function getSuccess();

    /**
     * @param bool $success
     * @return void
     */
    public function setSuccess(bool $success);

    /**
     * @return string
     */
    public function toString();
}
