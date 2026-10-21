<?php

namespace Versionist\Tests\End2End\Posts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Depends;
use Versionist\Tests\End2End\Utils\PostTypeTestCase;
use Versionist\Tests\Utils\DBAsserter;

class PostsTest extends PostTypeTestCase
{

    #[Test]
    #[TestDox("New post creates 'post/create' action")]
    public function addingPostCreatesPostCreateAction()
    {
        $this->runAddPostTest();
    }

    #[Test]
    #[Depends('addingPostCreatesPostCreateAction')]
    #[TestDox("Updating post creates 'post/update' action")]
    public function updatingPostCreatesPostEditAction()
    {
        $this->runUpdatePostTest();
    }

    #[Test]
    #[Depends('updatingPostCreatesPostEditAction')]
    #[TestDox("Updating post via quick edit creates equivalent 'post/update' action")]
    public function updatingPostViaQuickEditWorksEquallyWell()
    {
        $this->runUpdatePostViaQuickEditTest();
    }

    #[Test]
    #[Depends('addingPostCreatesPostCreateAction')]
    #[TestDox("Trashing post creates 'post/trash' action")]
    public function trashingPostCreatesPostTrashAction()
    {
        $this->runTrashPostTest();
    }

    #[Test]
    #[Depends('trashingPostCreatesPostTrashAction')]
    #[TestDox("Undo creates 'post/untrash' action")]
    public function undoCreatesPostUntrashAction()
    {
        $this->runUndoTrashTest();
    }

    #[Test]
    #[Depends('undoCreatesPostUntrashAction')]
    #[TestDox("Deleting post permanenly creates 'post/delete' action")]
    public function deletePermanentlyCreatesPostDeleteAction()
    {
        $this->runDeletePostTest();
    }

    #[Test]
    #[Depends('deletePermanentlyCreatesPostDeleteAction')]
    #[TestDox("Creating draft creates 'post/draft' action")]
    public function creatingDraftCreatesPostDraftAction()
    {
        $this->runDraftTest();
    }

    #[Test]
    #[Depends('creatingDraftCreatesPostDraftAction')]
    #[TestDox('Previewing draft does not create a commit')]
    public function previewingDraftDoesNotCreateCommit()
    {
        $this->runPreviewDraftTest();
    }

    #[Test]
    #[Depends('creatingDraftCreatesPostDraftAction')]
    #[TestDox("Publishing draft creates 'post/publish' action")]
    public function publishingDraftCreatesPostPublishAction()
    {
        $this->runPublishDraftTest();
    }

    #[Test]
    #[TestDox('Previewing unsaved post creates a draft')]
    public function previewingUnsavedPostCreatesDraft()
    {
        $this->runPreviewUnsavedPostTest();
    }

    #[Test]
    #[TestDox('Creating tag in the editation form creates separate commit')]
    public function creatingTagInEditationFormCreatesSeparateCommit()
    {
        self::$worker->prepare_createTagInEditationForm();

        $commitAsserter = $this->newCommitAsserter();

        self::$worker->createTagInEditationForm();

        $commitAsserter->assertNumCommits(2);
        $commitAsserter->assertCommitAction('post/update');
        $commitAsserter->assertCommitAction('term/create', 1);
        $commitAsserter->assertCommitTag("VP-Post-Type", self::$worker->getPostType());
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[TestDox('Setting featured image to an unsaved post does not commit')]
    public function settingFeaturedImageToUnsavedPostDoesNotCommit()
    {
        $this->runSetFeaturedImageForUnsavedPostTest();
    }

    #[Test]
    #[Depends('settingFeaturedImageToUnsavedPostDoesNotCommit')]
    #[TestDox('Turning unsaved post with featured image into a draft saves the featured image')]
    public function turningUnsavedPostWithFeaturedImageIntoDraftSavesTheFeaturedImage()
    {
        $this->runMakeDraftFromUnsavedPostWithFeaturedImageTest();
    }

    #[Test]
    #[TestDox('Changing post format updates its taxonomy')]
    public function changingPostFormatUpdatesItsTaxonomy()
    {
        self::$worker->prepare_changePostFormat();
        $commitAsserter = $this->newCommitAsserter();
        self::$worker->changePostFormat();

        $commitAsserter->ignoreCommits('term/create');
        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction('post/update');
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }

    #[Test]
    #[TestDox('Editation multiple posts creates bulk action')]
    public function editationOfMultiplePostsCreatesBulkAction()
    {
        $this->runEditationOfMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox('Trashing multiple posts creates bulk action')]
    public function trashingOfMultiplePostsCreatesBulkAction()
    {
        $this->runTrashingMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox('Untrashing multiple posts creates bulk action')]
    public function untrashingOfMultiplePostsCreatesBulkAction()
    {
        $this->runUntrashingMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox('Deleting multiple posts creates bulk action')]
    public function deletingOfMultiplePostsCreatesBulkAction()
    {
        $this->runDeletingMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox('Publishing multiple posts creates bulk action')]
    public function publishingOfMultiplePostsCreatesBulkAction()
    {
        $this->runPublishingMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox("Deleting post meta creates 'postmeta/delete' action")]
    public function deletingOfPostmetaCreatesPostmetaDeleteAction()
    {
        self::$worker->prepare_deletePostmeta();
        $commitAsserter = $this->newCommitAsserter();
        self::$worker->deletePostmeta();
        $commitAsserter->assertNumCommits(1);
        $commitAsserter->assertCommitAction('postmeta/delete');
        $commitAsserter->assertCleanWorkingDirectory();
        DBAsserter::assertFilesEqualDatabase();
    }
}
