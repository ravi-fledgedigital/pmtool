<?php

namespace OnitsukaTiger\Cegid\Api\Response;

interface ResponseInterface
{
    /**
     * Get Success
     *
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * Set Success
     *
     * @param bool $success
     * @return void
     */
    public function setSuccess(bool $success): void;

    /**
     * To String
     *
     * @return string
     */
    public function toString(): string;
}
