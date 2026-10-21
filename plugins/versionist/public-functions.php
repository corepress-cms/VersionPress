<?php

use Versionist\Actions\ActionsInfoProvider;
use Versionist\ChangeInfos\TrackedChangeInfo;
use Versionist\DI\VersionistServices;
use Versionist\Git\Committer;

/**
 * Temporary facade for $committer->forceChangeInfo. It will be replaced by less "invasive" function - Commmitter will
 * have multiple equiqalent sources (not only Mirror).
 *
 * @param string $scope
 * @param string $action
 * @param string|null $id
 * @param array $tags
 * @param array $files
 */
function vp_force_action($scope, $action, $id = '', $tags = [], $files = [])
{
    global $versionPressContainer;
    /** @var Committer $committer */
    $committer = $versionPressContainer->resolve(VersionistServices::COMMITTER);
    /** @var ActionsInfoProvider $actionsInfoProvider */
    $actionsInfoProvider = $versionPressContainer->resolve(VersionistServices::ACTIONSINFO_PROVIDER_ACTIVE_PLUGINS);
    $actionsInfo = $actionsInfoProvider->getActionsInfo($scope);

    $changeInfo = new TrackedChangeInfo($scope, $actionsInfo, $action, $id, $tags, $files);
    $committer->forceChangeInfo($changeInfo);
}
