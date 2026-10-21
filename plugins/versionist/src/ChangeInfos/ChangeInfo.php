<?php

namespace Versionist\ChangeInfos;

use Versionist\Git\CommitMessage;

/**
 * Represents one logical change in the WP site - one row in the main Versionist table.
 *
 * ChangeInfo is first created from a hook that observes some action (see Hooks\ChangeTracking, or for
 * {@link Versionist\ChangeInfos\EntityChangeInfo}s the initiator is usually some
 * {@link Versionist\Storages\Storage}). The ChangeInfo is then persisted to a commit message by {@link Committer}
 * and later reconstructed from it again when the main Versionist table is being displayed (see admin/index.php
 * and {@link Versionist\ChangeInfos\ChangeInfoFactory}).
 *
 * There are two main classes of ChangeInfo objects: tracked and untracked ones, and the tracked change infos
 * further have many specific types to represent various actions (for instance, post changes display different
 * messages than comment messages). More docs on it in {@link Versionist\ChangeInfos\TrackedChangeInfo}.
 */
interface ChangeInfo
{
    /**
     * Creates a commit message from this ChangeInfo. Used by Committer.
     *
     * @see Committer::commit()
     * @return CommitMessage
     */
    public function getCommitMessage();

    /**
     * Text displayed in the main Versionist table (see admin/index.php). Also used
     * to construct commit message subject (first line) when the commit is first
     * physically created.
     *
     * @return string
     */
    public function getChangeDescription();

    public function getPriority();
}
