<?php

namespace Versionist\Git\ChangeInfoPreprocessors;

use Versionist\ChangeInfos\ChangeInfoFactory;
use Versionist\ChangeInfos\ChangeInfoUtils;
use Versionist\Utils\ArrayUtils;

class PostTermSplittingPreprocessor implements ChangeInfoPreprocessor
{
    public function __construct(ChangeInfoFactory $changeInfoFactory)
    {
    }

    public function process($changeInfoList)
    {
        if ($this->containsPostChangeInfo($changeInfoList) && $this->containsTermChangeInfo($changeInfoList)) {
            $filterFn = function ($changeInfo) {
                return ChangeInfoUtils::changeInfoRepresentsEntity($changeInfo, 'term') ||
                ChangeInfoUtils::changeInfoRepresentsEntity($changeInfo, 'term_taxonomy');
            };

            $termChangeInfoList = array_values(array_filter($changeInfoList, $filterFn));
            $restChangeInfoList = array_values(array_filter($changeInfoList, function ($changeInfo) use ($filterFn) {
                return !$filterFn($changeInfo);
            }));

            return [$termChangeInfoList, $restChangeInfoList];
        }

        return [$changeInfoList];
    }

    private function containsPostChangeInfo($changeInfoList)
    {
        return ArrayUtils::any($changeInfoList, function ($changeInfo) {
            return ChangeInfoUtils::changeInfoRepresentsEntity($changeInfo, 'post');
        });
    }

    private function containsTermChangeInfo($changeInfoList)
    {
        return ArrayUtils::any($changeInfoList, function ($changeInfo) {
            return ChangeInfoUtils::changeInfoRepresentsEntity($changeInfo, 'term');
        });
    }
}
