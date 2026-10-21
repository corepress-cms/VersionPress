<?php

namespace Versionist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Versionist\ChangeInfos\BulkChangeInfo;
use Versionist\ChangeInfos\ChangeInfo;
use Versionist\ChangeInfos\ChangeInfoEnvelope;
use Versionist\ChangeInfos\EntityChangeInfo;
use Versionist\ChangeInfos\TrackedChangeInfo;
use Versionist\Database\EntityInfo;

class ChangeInfoEnvelopeTest extends TestCase
{


    /**
     * @param $inputChangeInfosSample
     * @param $sortedChangeInfosSample
     */
    #[Test]
    #[DataProvider('samePriorityExamples')]
    public function changeInfosWithSamePriorityMaintainOrder($inputChangeInfosSample, $sortedChangeInfosSample)
    {
        $changeInfoEnvelope = new ChangeInfoEnvelope($inputChangeInfosSample, "1.0");
        $sortedByChangeInfoEnvelope = $changeInfoEnvelope->getReorganizedInfoList();
        $sortedByChangeInfoEnvelope = $this->ungroupChangeInfos($sortedByChangeInfoEnvelope);

        $this->assertEquals($sortedChangeInfosSample, $sortedByChangeInfoEnvelope);
    }

    #[Test]
    public function entityChangeInfoWithCreateActionHasHigherPriorityThanOtherActions()
    {

        $entityInfo = self::createEntityInfo('some_entity');

        $lowerPriorityChangeInfo = new EntityChangeInfo($entityInfo, null, 'update', 'vpid', [], [], 12);
        $normalPriorityChangeInfo = new EntityChangeInfo($entityInfo, null, 'create', 'vpid');

        $input = [$lowerPriorityChangeInfo, $normalPriorityChangeInfo];
        $expectedSorted = [$normalPriorityChangeInfo, $lowerPriorityChangeInfo];

        $changeInfoEnvelope = new ChangeInfoEnvelope($input, "1.0");
        $sortedByChangeInfoEnvelope = $changeInfoEnvelope->getReorganizedInfoList();

        $this->assertEquals($expectedSorted, $sortedByChangeInfoEnvelope);
    }

    #[Test]
    public function bulkChangeInfoDoesNotAffectChangeInfoOrder()
    {
        $entityInfo = self::createEntityInfo('some_entity');

        $normalPriorityChangeInfo = new EntityChangeInfo($entityInfo, null, 'create', 'vpid');
        $lowerPriorityChangeInfo1 = new EntityChangeInfo($entityInfo, null, 'update', 'vpid', [], [], 12);
        $lowerPriorityChangeInfo2 = new EntityChangeInfo($entityInfo, null, 'update', 'vpid', [], [], 12);

        $input = [$normalPriorityChangeInfo, $lowerPriorityChangeInfo1, $lowerPriorityChangeInfo2];
        $changeInfoEnvelope = new ChangeInfoEnvelope($input, "1.0");
        $sortedByChangeInfoEnvelope = $changeInfoEnvelope->getReorganizedInfoList();
        $sortedByChangeInfoEnvelope = $this->ungroupChangeInfos($sortedByChangeInfoEnvelope);
        $this->assertEquals($input, $sortedByChangeInfoEnvelope);
    }

    /**
     * @param $changeInfos
     */
    #[Test]
    #[DataProvider('changeInfosRepresentingBulkActions')]
    public function bulkActionsAreGroupedIntoBulkChangeInfo($changeInfos)
    {
        $changeInfoEnvelope = new ChangeInfoEnvelope($changeInfos, '1.0');
        $groupedChangeInfoList = $changeInfoEnvelope->getReorganizedInfoList();
        $this->assertCount(1, $groupedChangeInfoList);
        $this->assertInstanceOf(BulkChangeInfo::class, $groupedChangeInfoList[0]);
    }

    //------------------------------------
    // Data providers
    //------------------------------------


    /**
     * Data provider
     *
     * @return array First item in the nested array is the input array, second is the expected sorted array
     */
    public static function samePriorityExamples()
    {

        $entityName = 'some_entity';

        $entityInfoMock = self::createEntityInfo($entityName);

        $wordpressUpdateChangeInfo1 = new TrackedChangeInfo('wordpress', null, 'update', '4.0', [], [], 12);
        $wordPressUpdateChangeInfo2 = new TrackedChangeInfo('wordpress', null, 'update', '4.1', [], [], 12);

        $normalPriorityPostChangeInfo1 = new EntityChangeInfo($entityInfoMock, null, 'update', 'vpid');
        $normalPriorityPostChangeInfo2 = new EntityChangeInfo($entityInfoMock, null, 'update', 'another vpid');

        return [
            [
                [$wordpressUpdateChangeInfo1, $wordPressUpdateChangeInfo2],
                [$wordpressUpdateChangeInfo1, $wordPressUpdateChangeInfo2]
            ],

            [
                [$normalPriorityPostChangeInfo1, $normalPriorityPostChangeInfo2],
                [$normalPriorityPostChangeInfo1, $normalPriorityPostChangeInfo2],
            ],

        ];
    }

    public static function changeInfosRepresentingBulkActions()
    {
        $entityInfoMock = self::createEntityInfo('some_entity');

        return [
            [
                [
                    new EntityChangeInfo($entityInfoMock, null, 'update', '1st vpid'),
                    new EntityChangeInfo($entityInfoMock, null, 'update', '2nd vpid'),
                ]
            ],
            [
                [
                    new EntityChangeInfo($entityInfoMock, null, 'update', '1st vpid'),
                    new EntityChangeInfo($entityInfoMock, null, 'update', '2nd vpid'),
                    new EntityChangeInfo($entityInfoMock, null, 'update', '3rd vpid'),
                ]
            ],
        ];
    }

    /**
     * @param ChangeInfo[] $changeInfos
     * @return ChangeInfo[]
     */
    private function ungroupChangeInfos($changeInfos)
    {
        $ungrouped = [];
        foreach ($changeInfos as $changeInfo) {
            if ($changeInfo instanceof BulkChangeInfo) {
                foreach ($changeInfo->getChangeInfos() as $innerChangeInfo) {
                    $ungrouped[] = $innerChangeInfo;
                }
            } else {
                $ungrouped[] = $changeInfo;
            }
        }

        return $ungrouped;
    }

    /**
     * @param string $entityName
     * @return EntityInfo
     */
    private static function createEntityInfo($entityName)
    {
        return new EntityInfo([$entityName => ['id' => 'id']]);
    }
}
