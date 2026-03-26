<?php

namespace OnitsukaTiger\EmailToWareHouse\Service;

/**
 * Email
 */
class Email
{
    /**
     * @var array
     */
    protected $variablesData = [];

    /**
     * @param $data
     * @return mixed
     */
    public function setVariablesData($data)
    {
        return $this->variablesData = $data;
    }

    /**
     * @return array
     */
    public function getVariablesData()
    {
        return $this->variablesData;
    }
}
