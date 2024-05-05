<?php

namespace XLay;

class Trailer
{
    private int $activbeBoardTab;
    private string $projectName;
    private string $projectAuthor;
    private string $projectCompany;
    private string $comment;

    public function parse(array & $data)
    {
        $this->activbeBoardTab = Helper::getUInt32($data);
        $this->projectName = Helper::getString($data, 100);
        $this->projectAuthor = Helper::getString($data, 100);
        $this->projectCompany = Helper::getString($data, 100);

        $len = Helper::getUInt32($data);
        $this->comment = Helper::getRawString($data, $len);
    }
}