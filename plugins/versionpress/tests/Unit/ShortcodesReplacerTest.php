<?php

namespace VersionPress\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use VersionPress\Database\ShortcodesInfo;
use VersionPress\Database\ShortcodesReplacer;
use VersionPress\Database\VpidRepository;

class ShortcodesReplacerTest extends TestCase
{

    private $shortcodeSchemaValueMap = [
        ['foo', ['id' => 'some-entity', 'ids' => 'another-entity']],
        ['bar', ['id' => 'some-entity']],
    ];

    private static $idValueMap = [
        ['some-entity', '1', '123ABCD45EF'],
        ['another-entity', '1', '1234567890'],
        ['another-entity', '7', 'BOND007'],
    ];

    private $postShortcodesSchema = ["post" => ["post_content"]];

    private $shortcodesInfo;

    private $vpidRepository;

    protected function setUp(): void
    {
        if (!function_exists('get_shortcode_regex')) {
            // Tests run inside wp-env where the plugin lives in wp-content/plugins of a real WordPress install.
            include_once __DIR__ . '/../../../../../wp-includes/shortcodes.php';
        }

        $this->shortcodesInfo = $this->createStub(ShortcodesInfo::class);
        $this->shortcodesInfo
            ->method('getAllShortcodeNames')
            ->willReturn(array_column($this->shortcodeSchemaValueMap, 0));

        $this->shortcodesInfo
            ->method('getShortcodeInfo')
            ->willReturnMap($this->shortcodeSchemaValueMap);

        $this->shortcodesInfo
            ->method('getShortcodeLocations')
            ->willReturn($this->postShortcodesSchema);

        $this->vpidRepository = $this->createStub(VpidRepository::class);
        $this->vpidRepository
            ->method('getVpidForEntity')
            ->willReturnMap(self::$idValueMap);

        $vpidValueMap = array_map(function ($idValueMapItem) {
            return [$idValueMapItem[2], $idValueMapItem[1]];
        }, self::$idValueMap);

        $this->vpidRepository
            ->method('getIdForVpid')
            ->willReturnMap($vpidValueMap);


    }

    #[Test]
    #[DataProvider('shortcodeProvider')]
    public function replacerReplacesIdForVpidsInEntity($input, $expectedReplacedOutput)
    {
        $shortcodesReplacer = new ShortcodesReplacer($this->shortcodesInfo, $this->vpidRepository);
        $testingPost = [
            'post_title' => $input,
            'post_content' => $input
        ];
        $expectedPost = [
            'post_title' => $input,
            'post_content' => $expectedReplacedOutput
        ];
        $entityWithReplacedShortCodes = $shortcodesReplacer->replaceShortcodesInEntity('post', $testingPost);
        $this->assertEquals($expectedPost, $entityWithReplacedShortCodes);
    }

    #[Test]
    #[DataProvider('shortcodeProvider')]
    public function replacerRestoresIdsFromVpidsInEntity($expectedReplacedOutput, $input)
    {
        $shortcodesReplacer = new ShortcodesReplacer($this->shortcodesInfo, $this->vpidRepository);
        $testingPost = [
            'post_title' => $input,
            'post_content' => $input
        ];
        $expectedPost = [
            'post_title' => $input,
            'post_content' => $expectedReplacedOutput
        ];
        $entityWithReplacedShortCodes = $shortcodesReplacer->restoreShortcodesInEntity('post', $testingPost);
        $this->assertEquals($expectedPost, $entityWithReplacedShortCodes);
    }

    public static function shortcodeProvider()
    {
        /** @var callable $ids */
        $ids = [self::class, 'getVpidForId'];

        return [
            ['[foo id="1"]', '[foo id="' . $ids('some-entity', 1) . '"]'],
            ['[foo ids="1"]', '[foo ids="' . $ids('another-entity', 1) . '"]'],
            ['[foo ids="1,7"]', '[foo ids="' . $ids('another-entity', 1, 7) . '"]'],

            [
                'Some text [foo id="1"] with shortcode',
                'Some text [foo id="' . $ids('some-entity', 1) . '"] with shortcode'
            ],
            [
                'More [foo id="1"] shortcodes [bar id="1"]',
                'More [foo id="' . $ids('some-entity', 1) . '"] shortcodes [bar id="' . $ids('some-entity', 1) . '"]'
            ],
        ];
    }

    public static function getVpidForId($entity, $id1, $id2 = null)
    {
        $args = func_get_args();
        array_shift($args);

        $foundIds = [];
        foreach ($args as $id) {
            foreach (self::$idValueMap as $idValueMapItem) {
                if ($idValueMapItem[0] === $entity && $idValueMapItem[1] == $id) {
                    $foundIds[] = $idValueMapItem[2];
                    continue 2;
                }
            }

            $foundIds[] = $id; // not found
        }

        return join(',', $foundIds);
    }
}
