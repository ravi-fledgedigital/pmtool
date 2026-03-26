<?php
/**
* phpcs:ignoreFile
*/

namespace OnitsukaTigerIndo\Biteship\Model\Response;

/**
 * Response Class for printing output
 */
class Response implements \OnitsukaTigerIndo\Biteship\Api\Response\ResponseInterface
{
    /**
     * Constructs a new instance.
     *
     * @param      bool  $success  The success
     */
    public function __construct(
        bool $success
    ) {
        $this->success = $success;
    }

    /**
     * Gets the success.
     *
     * @return     <type>  The success.
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Sets the success.
     *
     * @param      bool  $success  The success
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;
    }

    /**
     * Returns a string representation of the object.
     *
     * @return     <type>  String representation of the object.
     */
    public function toString()
    {
        return json_encode($this);
    }
}
