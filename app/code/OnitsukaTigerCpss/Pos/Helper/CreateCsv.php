<?php

namespace OnitsukaTigerCpss\Pos\Helper;

class CreateCsv extends \Cpss\Pos\Helper\CreateCsv
{
    public function convertArrayToShiftjis($data)
    {
        return $data;
    }

}