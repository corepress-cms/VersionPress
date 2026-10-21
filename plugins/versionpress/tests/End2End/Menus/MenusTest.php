<?php

namespace VersionPress\Tests\End2End\Menus;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Depends;
use VersionPress\Tests\End2End\Utils\End2EndTestCase;
use VersionPress\Tests\Utils\DBAsserter;

class MenusTest extends End2EndTestCase
{

    /** @var IMenusTestWorker */
    private static $worker;

    #[Test]
    #[TestDox("New menu creates 'term/create' action")]
    public function addingMenuCreatesTermCreateAction()
    {
        self::$worker->prepare_createMenu();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->createMenu();

        $commitAsserter->ignoreCommits("usermeta/create");
        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("term/create");
        $commitAsserter->assertCommitPath('A', "%vpdb%/terms/%VPID%.ini");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[Depends('addingMenuCreatesTermCreateAction')]
    #[TestDox("Renaming menu creates 'term/rename' action")]
    public function renamingMenuCreatesTermRenameAction()
    {
        self::$worker->prepare_editMenu();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->editMenu();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("term/rename");
        $commitAsserter->assertCommitPath('M', "%vpdb%/terms/%VPID%.ini");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[Depends('renamingMenuCreatesTermRenameAction')]
    #[TestDox("Adding menu item creates 'post/create' action")]
    public function addingMenuItemCreatesPostCreateAction()
    {
        self::$worker->prepare_addMenuItem();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->addMenuItem();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("post/create");
        $commitAsserter->assertCommitTag("VP-Post-Type", "nav_menu_item");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[Depends('addingMenuItemCreatesPostCreateAction')]
    #[TestDox("Editing menu item order creates 'post/update' action.")]
    public function editingMenuItemCreatesPostEditAction()
    {
        self::$worker->prepare_editMenuItem();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->editMenuItem();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("post/update");
        $commitAsserter->assertCommitTag("VP-Post-Type", "nav_menu_item");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[Depends('addingMenuItemCreatesPostCreateAction')]
    #[TestDox('Creating menu item draft leaves not clean working directory.')]
    public function creatingMenuItemDraftLeavesNotCleanWorkingDirectory()
    {
        self::$worker->prepare_createMenuItemDraft();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->createMenuItemDraft();

        $commitAsserter->assertNumCommits(0);
        $commitAsserter->assertCountOfUntrackedFiles(1);
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[Depends('creatingMenuItemDraftLeavesNotCleanWorkingDirectory')]
    #[TestDox('Orphaned menu item should be deleted when doing undo')]
    public function orphanedMenuItemShouldBeDeletedWhenDoingUndo()
    {
        self::$worker->prepare_deleteOrphanedMenuItems();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->deleteOrphanedMenuItems();

        $commitAsserter->assertNumCommits(0);
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[Depends('editingMenuItemCreatesPostEditAction')]
    #[TestDox("Removing menu item creates 'post/delete' action")]
    public function removingMenuItemCreatesPostDeleteAction()
    {
        self::$worker->prepare_removeMenuItem();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->removeMenuItem();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("post/delete");
        $commitAsserter->assertCommitTag("VP-Post-Type", "nav_menu_item");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[TestDox('Removing menu item withChildrenUpdatesChildrensParent')]
    public function removingMenuItemWithChildrenUpdatesChildrensParent()
    {
        self::$worker->prepare_removeMenuItemWithChildren();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->removeMenuItemWithChildren();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("post/delete");
        $commitAsserter->assertCommitTag("VP-Post-Type", "nav_menu_item");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[Depends('removingMenuItemCreatesPostDeleteAction')]
    #[TestDox("Deleting menu creates 'term/delete' action")]
    public function deletingMenuCreatesTermDeleteAction()
    {
        self::$worker->prepare_deleteMenu();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->deleteMenu();

        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction("term/delete");
        $commitAsserter->assertCommitPath('D', "%vpdb%/terms/%VPID%.ini");
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }
}
