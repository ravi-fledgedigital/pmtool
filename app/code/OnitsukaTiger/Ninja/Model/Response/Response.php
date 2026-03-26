<?php

namespace OnitsukaTiger\Ninja\Model\Response;

use OnitsukaTiger\Ninja\Api\Response\ResponseInterface;

/**
 * Class Response
 * @package OnitsukaTiger\Ninja\Model\Response
 */
class Response implements ResponseInterface
{
    /**
     * @var bool
     */
    protected $success;

    public function __construct(
        bool $success
    )
    {
        $this->success = $success;
    }

    /**
     * @return bool
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return void
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;
    }

    public function toString()
    {
        return json_encode($this);
    }
}
