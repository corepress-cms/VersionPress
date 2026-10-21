<?php

namespace Versionist\Initialization;

class InitializerStates
{
    public const START = "Starting up";

    public const DB_TABLES_CREATED = "Helper database tables created";

    public const VPIDS_CREATED = "Entities uniquely identified";

    public const REFERENCES_CREATED = "Cross-references created";

    public const DB_WORK_DONE = "All database work done";

    public const CREATING_GIT_REPOSITORY = "Creating Git repository";

    public const VERSIONIST_ACTIVATED = "Versionist put into active state";

    public const CREATING_INITIAL_COMMIT = "Creating initialization commit in the repository";

    public const FINISHED = "All finished, Versionist is ready";

    public const ABORTED = "Initialization aborted because of reaching maximum execution time";
}
