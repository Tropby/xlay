<?php

namespace XLay;

class Connection
{
    private array $connections = [];

    public function parse(array & $data)
    {
        $len = Helper::getUInt32($data);
        $connections = Helper::getRawData($data, 4 * $len);
        // TODO: Check what connections are??
    }
}