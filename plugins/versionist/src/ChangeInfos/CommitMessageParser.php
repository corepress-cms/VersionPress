<?php

namespace Versionist\ChangeInfos;

use Versionist\Actions\ActionsInfoProvider;
use Versionist\Database\DbSchemaInfo;
use Versionist\Git\CommitMessage;

/**
 * Parses ChangeInfoEnvelope from a commit message.
 */
class CommitMessageParser
{
    /** @var DbSchemaInfo */
    private $dbSchema;
    /** @var ActionsInfoProvider */
    private $actionsInfoProvider;

    public function __construct(DbSchemaInfo $dbSchema, ActionsInfoProvider $actionsInfoProvider)
    {
        $this->dbSchema = $dbSchema;
        $this->actionsInfoProvider = $actionsInfoProvider;
    }

    /**
     * @param CommitMessage $commitMessage
     * @return ChangeInfoEnvelope
     */
    public function parse(CommitMessage $commitMessage)
    {
        $fullBody = $commitMessage->getBody();
        $splittedBodies = explode("\n\n", $fullBody);
        $lastBody = $splittedBodies[count($splittedBodies) - 1];
        $changeInfoList = [];
        $version = null;
        $environment = null;

        if (self::containsVersion($lastBody)) {
            $version = self::extractTag(ChangeInfoEnvelope::VP_VERSION_TAG, $lastBody);
            $environment = self::extractTag(ChangeInfoEnvelope::VP_ENVIRONMENT_TAG, $lastBody);
            array_pop($splittedBodies);
        }

        if (!self::isTrackedChangeInfo($fullBody)) {
            return new ChangeInfoEnvelope([new UntrackedChangeInfo($commitMessage)], $version, $environment);
        }

        foreach ($splittedBodies as $body) {
            $partialCommitMessage = new CommitMessage("", $body);

            $actionTag = $partialCommitMessage->getVersionistTag(TrackedChangeInfo::ACTION_TAG);
            list($scope, $action, $id) = array_pad(explode('/', $actionTag, 3), 3, null);

            $tags = $partialCommitMessage->getVersionistTags();
            unset($tags[TrackedChangeInfo::ACTION_TAG]);

            $actionsInfo = $this->actionsInfoProvider->getActionsInfo($scope);

            if ($this->dbSchema->isEntity($scope)) {
                $entityInfo = $this->dbSchema->getEntityInfo($scope);
                $changeInfoList[] = new EntityChangeInfo($entityInfo, $actionsInfo, $action, $id, $tags, []);
            } else {
                $changeInfoList[] = new TrackedChangeInfo($scope, $actionsInfo, $action, $id, $tags, []);
            }
        }

        return new ChangeInfoEnvelope($changeInfoList, $version, $environment);
    }

    private static function containsVersion($lastBody)
    {
        return str_starts_with($lastBody, ChangeInfoEnvelope::VP_VERSION_TAG);
    }

    private static function extractTag($tag, $commitMessageBody)
    {
        $tmpMessage = new CommitMessage("", $commitMessageBody);
        return $tmpMessage->getVersionistTag($tag);
    }

    private static function isTrackedChangeInfo($body)
    {
        return str_starts_with($body, TrackedChangeInfo::ACTION_TAG);
    }
}
