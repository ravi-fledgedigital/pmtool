<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Model;

use Magento\Framework\Model\AbstractModel;
use OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface;

class ReturnAction extends AbstractModel implements ReturnActionInterface
{
    const STATUS_UNSENT_CEGID = 0;
    const STATUS_SENT_CEGID = 1;

    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init(\OnitsukaTiger\Cegid\Model\ResourceModel\ReturnAction::class);
    }


    /**
     * @return array|mixed|null
     */
    public function getReturnactionId(): mixed
    {
        return $this->getData(self::RETURNACTION_ID);
    }


    /**
     * @param $returnactionId
     * @return mixed|ReturnAction
     */
    public function setReturnactionId($returnactionId) : mixed
    {
        return $this->setData(self::RETURNACTION_ID, $returnactionId);
    }


    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->getData(self::NUMBER);
    }


    /**
     * @param $number
     * @return mixed
     */
    public function setNumber($number): mixed
    {
        return $this->setData(self::NUMBER, $number);
    }


    /**
     * @return string|null
     */
    public function getStub(): ?string
    {
        return $this->getData(self::STUB);
    }


    /**
     * @param $stub
     * @return mixed
     */
    public function setStub($stub): mixed
    {
        return $this->setData(self::STUB, $stub);
    }


    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getData(self::TYPE);
    }


    /**
     * @param $type
     * @return mixed
     */
    public function setType($type): mixed
    {
        return $this->setData(self::TYPE, $type);
    }


    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }


    /**
     * @param $status
     * @return mixed
     */
    public function setStatus($status): mixed
    {
        return $this->setData(self::STATUS, $status);
    }


    /**
     * @return string|null
     */
    public function getRequestId(): ?string
    {
        return $this->getData(self::REQUEST_ID);
    }


    /**
     * @param $requestId
     * @return mixed
     */
    public function setRequestId($requestId): mixed
    {
        return $this->setData(self::REQUEST_ID, $requestId);
    }
}
