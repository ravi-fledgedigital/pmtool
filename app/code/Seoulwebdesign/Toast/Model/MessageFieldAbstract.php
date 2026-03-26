<?php

namespace Seoulwebdesign\Toast\Model;

abstract class MessageFieldAbstract
{

    /**
     * Get Available Variables
     *
     * @return string[][]
     */
    abstract public function getAvailableVariables();

    /**
     * Get Ref Field List
     *
     * @return array
     */
    abstract public function getRefFieldList();
}
