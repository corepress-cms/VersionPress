<?php

namespace Versionist\Git;

class RevertStatus
{
    public const OK = 'ok';
    public const MERGE_CONFLICT = 'merge-conflict';
    public const VIOLATED_REFERENTIAL_INTEGRITY = 'violated-referential-integrity';
    public const NOTHING_TO_COMMIT = 'nothing-to-commit';
    public const NOT_CLEAN_WORKING_DIRECTORY = 'not-clean-working-directory';
    public const REVERTING_MERGE_COMMIT = 'reverting-merge-commit';
}
