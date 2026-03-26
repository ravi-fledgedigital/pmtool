<?php


namespace OnitsukaTiger\NetSuite\Model\Response;


class Response implements \OnitsukaTiger\NetSuite\Api\Response\ResponseInterface
{

    /**
     * @var bool
     */
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
