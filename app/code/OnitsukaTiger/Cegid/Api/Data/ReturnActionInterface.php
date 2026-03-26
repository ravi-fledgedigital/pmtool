<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Api\Data;

interface ReturnActionInterface
{

    const TYPE = 'type';
    const STATUS = 'status';
    const RETURNACTION_ID = 'returnaction_id';
    const NUMBER = 'number';
    const REQUEST_ID = 'request_id';
    const STUB = 'stub';


    /**
     * @return mixed
     */
    public function getReturnactionId(): mixed;


    /**
     * @param $returnactionId
     * @return mixed
     */
    public function setReturnactionId($returnactionId): mixed;

    /**
     * Get number
     * @return string|null
     */
    public function getNumber(): ?string;


    /**
     * @param $number
     * @return mixed
     */
    public function setNumber($number): mixed;

    /**
     * Get stub
     * @return string|null
     */
    public function getStub(): ?string;


    /**
     * @param $stub
     * @return mixed
     */
    public function setStub($stub): mixed;

    /**
     * Get type
     * @return string|null
     */
    public function getType(): ?string;


    /**
     * @param $type
     * @return mixed
     */
    public function setType($type): mixed;

    /**
     * Get status
     * @return string|null
     */
    public function getStatus(): ?string;


    /**
     * @param $status
     * @return mixed
     */
    public function setStatus($status): mixed;

    /**
     * Get request_id
     * @return string|null
     */
    public function getRequestId(): ?string;


    /**
     * @param $requestId
     * @return mixed
     */
    public function setRequestId($requestId): mixed;
}
