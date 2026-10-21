<?php

namespace Versionist\Git\ChangeInfoPreprocessors;

use Versionist\ChangeInfos\ChangeInfo;
use Versionist\ChangeInfos\ChangeInfoFactory;

interface ChangeInfoPreprocessor
{
    public function __construct(ChangeInfoFactory $changeInfoFactory);

    /**
     * Processes the ChangeInfo list and returns one or more
     * new lists.
     *
     * @param ChangeInfo[] $changeInfoList
     * @return ChangeInfo[][]
     */
    public function process($changeInfoList);
}
