<?php

namespace VersionPress\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use VersionPress\Actions\ActionsInfo;
use VersionPress\Tests\Utils\HookMock;

class ActionsInfoTest extends TestCase
{
    protected function setUp(): void
    {
        HookMock::setUp();
    }

    #[Test]
    public function actionsInfoCreatesDescriptionForGivenAction()
    {
        $actions = [
            'some-action' => ['message' => 'Some description'],
            'other-action' => ['message' => 'Other description'],
        ];

        $actionsInfo = new ActionsInfo('some-scope', $actions);
        $description1 = $actionsInfo->getDescription('some-action', 'some-id', []);
        $description2 = $actionsInfo->getDescription('other-action', 'some-id', []);

        $this->assertSame('Some description', $description1);
        $this->assertSame('Other description', $description2);
    }

    #[Test]
    public function actionsInfoReplacesTagPlaceholderWithItsValue()
    {
        $tags = ['VP-Tag' => '/'];
        $actions = ['some-action' => ['message' => 'Some description containing %VP-Tag%']];


        $actionsInfo = new ActionsInfo('some-scope', $actions, $tags);

        $description = $actionsInfo->getDescription('some-action', 'some-id', ['VP-Tag' => 'tag value']);
        $this->assertSame('Some description containing tag value', $description);
    }

    #[Test]
    public function actionsInfoReplacesVpidPlaceholderWithVpid()
    {
        $actions = ['some-action' => ['message' => 'Some description containing %VPID%']];

        $actionsInfo = new ActionsInfo('some-scope', $actions);
        $description = $actionsInfo->getDescription('some-action', 'some-id', []);
        $this->assertSame('Some description containing some-id', $description);
    }

    #[Test]
    public function actionsInfoAppliesFilterOnDescription()
    {
        $actions = ['some-action' => ['message' => 'Some description']];

        add_filter('vp_action_description_some-scope', function ($description) {
            $this->assertSame('Some description', $description);
            return 'Filtered description';
        });

        $actionsInfo = new ActionsInfo('some-scope', $actions);
        $description = $actionsInfo->getDescription('some-action', 'some-id', []);

        $this->assertSame('Filtered description', $description);
    }
}
