<?php

namespace Versionist\Tests\End2End\Pages;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Depends;
use Versionist\Tests\End2End\Utils\PostTypeTestCase;

class PagesTest extends PostTypeTestCase
{

    #[Test]
    #[TestDox("New page creates 'post/create' action")]
    public function addingPageCreatesPostCreateAction()
    {
        $this->runAddPostTest();
    }

    #[Test]
    #[Depends('addingPageCreatesPostCreateAction')]
    #[TestDox("Updating page content creates 'post/update' action")]
    public function updatingPageCreatesPostEditAction()
    {
        $this->runUpdatePostTest();
    }

    #[Test]
    #[Depends('updatingPageCreatesPostEditAction')]
    #[TestDox("Updating page via quick edit creates equivalent 'post/update' action")]
    public function updatingPageViaQuickEditWorksEquallyWell()
    {
        $this->runUpdatePostViaQuickEditTest();
    }

    #[Test]
    #[Depends('addingPageCreatesPostCreateAction')]
    #[TestDox("Trashing page creates 'post/trash' action")]
    public function trashingPageCreatesPostTrashAction()
    {
        $this->runTrashPostTest();
    }

    #[Test]
    #[Depends('trashingPageCreatesPostTrashAction')]
    #[TestDox("Undo creates 'post/untrash' action")]
    public function undoCreatesPostUntrashAction()
    {
        $this->runUndoTrashTest();
    }

    #[Test]
    #[Depends('undoCreatesPostUntrashAction')]
    #[TestDox("Deleting page permanenly creates 'post/delete' action")]
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
    #[TestDox('Editation multiple pages creates bulk action')]
    public function editationOfMultiplePagesCreatesBulkAction()
    {
        $this->runEditationOfMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox('Trashing multiple pages creates bulk action')]
    public function trashingOfMultiplePagesCreatesBulkAction()
    {
        $this->runTrashingMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox('Untrashing multiple pages creates bulk action')]
    public function untrashingOfMultiplePagesCreatesBulkAction()
    {
        $this->runUntrashingMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox('Deleting multiple pages creates bulk action')]
    public function deletingOfMultiplePagesCreatesBulkAction()
    {
        $this->runDeletingMultiplePostsCreatesBulkAction();
    }

    #[Test]
    #[TestDox('Publishing multiple pages creates bulk action')]
    public function publishingOfMultiplePagesCreatesBulkAction()
    {
        $this->runPublishingMultiplePostsCreatesBulkAction();
    }
}
