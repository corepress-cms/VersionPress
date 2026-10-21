<?php

namespace VersionPress\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use VersionPress\Utils\Cursor;

class CursorTest extends TestCase
{

    #[Test]
    public function cursorReadsScalarValue()
    {
        $data = 'value';
        $cursor = new Cursor($data, []);
        $this->assertEquals('value', $cursor->getValue());
    }

    #[Test]
    public function cursorReadsFromSimpleArray()
    {
        $data = ['value'];
        $cursor = new Cursor($data, [0]);
        $this->assertEquals('value', $cursor->getValue());
    }

    #[Test]
    public function cursorReadsFromNestedArrays()
    {
        $data = ['key' => [3 => 'value']];
        $cursor = new Cursor($data, ['key', 3]);
        $this->assertEquals('value', $cursor->getValue());
    }

    #[Test]
    public function cursorReadsFromArrayWithMixedKeys()
    {
        $data = ['key' => [3 => 'value'], 'another-key' => 'another value'];
        $cursor = new Cursor($data, ['key', 3]);
        $this->assertEquals('value', $cursor->getValue());
    }

    #[Test]
    public function cursorReadsFromObjectWithArray()
    {
        $data = new \stdClass();
        $data->key = [3 => 'value'];

        $cursor = new Cursor($data, ['key', 3]);

        $this->assertEquals('value', $cursor->getValue());
    }

    #[Test]
    public function cursorReadsFromNestedObjects()
    {
        $data = new \stdClass();
        $data->key = new \stdClass();
        $data->key->subkey = 'value';

        $cursor = new Cursor($data, ['key', 'subkey']);

        $this->assertEquals('value', $cursor->getValue());
    }

    #[Test]
    public function cursorUpdatesScalarValue()
    {
        $data = 'value';

        $cursor = new Cursor($data, []);
        $cursor->setValue('another value');

        $this->assertEquals('another value', $data);
    }

    #[Test]
    public function cursorUpdatesSimpleArray()
    {
        $data = ['value'];

        $cursor = new Cursor($data, [0]);
        $cursor->setValue('another value');

        $this->assertEquals('another value', $data[0]);
    }

    #[Test]
    public function cursorUpdatesNestedArray()
    {
        $data = ['key' => [3 => 'value']];

        $cursor = new Cursor($data, ['key', 3]);
        $cursor->setValue('another value');

        $this->assertEquals('another value', $data['key'][3]);
    }

    #[Test]
    public function cursorUpdatesArrayWithMixedKeys()
    {
        $data = ['key' => [3 => 'value'], 'another-key' => 'another value'];

        $cursor = new Cursor($data, ['key', 3]);
        $cursor->setValue('another value');

        $this->assertEquals('another value', $data['key'][3]);
    }

    #[Test]
    public function cursorUpdatesObjectWithArray()
    {
        $data = new \stdClass();
        $data->key = [3 => 'value'];

        $cursor = new Cursor($data, ['key', 3]);
        $cursor->setValue('another value');

        $this->assertEquals('another value', $data->key[3]);
    }

    #[Test]
    public function cursorUpdatesNestedObjects()
    {
        $data = new \stdClass();
        $data->key = new \stdClass();
        $data->key->subkey = 'VP is cool';

        $cursor = new Cursor($data, ['key', 'subkey']);
        $cursor->setValue('another value');

        $this->assertEquals('another value', $data->key->subkey);
    }
}
