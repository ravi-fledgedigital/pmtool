<?php

namespace OnitsukaTiger\Cegid\Model\Response;

class Response implements \OnitsukaTiger\Cegid\Api\Response\ResponseInterface
{

    protected $success;

    /**
     * @param bool $success
     */
    public function __construct(
        bool $success
    ) {
        $this->success = $success;
    }

    /**
     * Get Success
     *
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Set Success
     *
     * @param bool $success
     * @return void
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * To String
     *
     * @return string
     */
    public function toString(): string
    {
        return json_encode($this);
    }
}
