<?php

namespace Versionist\Tests\StorageTests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\Stub;
use Versionist\ChangeInfos\ChangeInfoFactory;
use Versionist\ChangeInfos\EntityChangeInfo;
use Versionist\Database\EntityInfo;
use Versionist\Tests\Utils\HookMock;

class StorageTestCase extends TestCase
{
    protected function setUp(): void
    {
        HookMock::setUp();
    }

    protected function tearDown(): void
    {
        HookMock::tearDown();
    }

    /**
     * @param array $fields
     * @param array $methods
     * @return Stub|EntityInfo
     */
    protected function createEntityInfoMock($fields, $methods)
    {
        $entityInfo = $this->createStub(EntityInfo::class);

        foreach ($fields as $field => $value) {
            $entityInfo->{$field} = $value;
        }

        foreach ($methods as $methodName => $returnValue) {
            $entityInfo->method($methodName)->willReturn($returnValue);
        }

        return $entityInfo;
    }

    /**
     * @return Stub|ChangeInfoFactory
     */
    protected function createChangeInfoFactoryMock()
    {
        $changeInfoFactory = $this->createStub(ChangeInfoFactory::class);
        $changeInfoFactory->method('createEntityChangeInfo')->willReturn($this->createStub(EntityChangeInfo::class));

        return $changeInfoFactory;
    }
}
