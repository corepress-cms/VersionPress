<?php

namespace Versionist\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Versionist\Storages\Mirror;
use Versionist\Storages\Storage;
use Versionist\Storages\StorageFactory;
use Versionist\Utils\AbsoluteUrlReplacer;

class MirrorTest extends TestCase
{
    #[Test]
    public function mirrorCallsTheReplacerOnSavingEntity()
    {
        $fakeStorageFactory = $this->createStub(StorageFactory::class);
        $fakeReplacer = $this->createMock(AbsoluteUrlReplacer::class);
        $fakeStorage = $this->createStub(Storage::class);

        $fakeStorageFactory->method('getStorage')->willReturn($fakeStorage);
        $fakeReplacer->expects($this->once())->method('replace');

        /**
         * @var StorageFactory $fakeStorageFactory
         * @var AbsoluteUrlReplacer $fakeReplacer
         */
        $mirror = new Mirror($fakeStorageFactory, $fakeReplacer);
        $mirror->save('foo', []);
    }

    #[Test]
    public function mirrorUsesRightStorage()
    {
        $entityName = 'some-entity';
        $someEntity = ['foo' => 'bar'];

        $fakeStorageFactory = $this->createMock(StorageFactory::class);
        $fakeReplacer = $this->createMock(AbsoluteUrlReplacer::class);
        $fakeStorage = $this->createMock(Storage::class);

        $fakeReplacer->expects($this->once())->method('replace')->willReturnArgument(0);
        $fakeStorageFactory->expects($this->once())->method('getStorage')
            ->with($entityName)->willReturn($fakeStorage);
        $fakeStorage->expects($this->once())->method('save')->with($someEntity);

        /**
         * @var StorageFactory $fakeStorageFactory
         * @var AbsoluteUrlReplacer $fakeReplacer
         */
        $mirror = new Mirror($fakeStorageFactory, $fakeReplacer);
        $mirror->save($entityName, $someEntity);
    }
}
