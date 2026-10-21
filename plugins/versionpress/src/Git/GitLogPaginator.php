<?php

namespace VersionPress\Git;

/**
 * Useful for pagination of git log. Uses the `GitStatic` class.
 */
class GitLogPaginator
{
    /**
     * @var GitRepository
     */
    private $repository;
    private $commitsPerPage = 25;
    private $isLastPage = false;
    private $numberOfCommits;
    private $query = '';

    public function __construct(GitRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Returns a subset of commits ordered from newest to oldest.
     *
     * @param $pageNumber
     * @return Commit[]
     */
    public function getPage($pageNumber)
    {
        $this->numberOfCommits = $this->repository->getNumberOfCommits($this->query);

        $firstCommitIndex = $pageNumber * $this->commitsPerPage;
        $lastCommitIndex = ($pageNumber + 1) * $this->commitsPerPage;

        if ($lastCommitIndex >= $this->numberOfCommits) {
            $this->isLastPage = true;
        } else {
            $this->isLastPage = false;
        }

        $query = $this->query .
            ' --skip=' . $firstCommitIndex .
            ' --max-count=' . $this->commitsPerPage;

        return $this->repository->log($query);
    }

    /**
     * Returns true if the last loaded page was the last one.
     *
     * @return boolean
     */
    public function isLastPage()
    {
        return $this->isLastPage;
    }

    /**
     * @param int $commitsPerPage
     */
    public function setCommitsPerPage($commitsPerPage)
    {
        $this->commitsPerPage = $commitsPerPage;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * Number of commits matching the query of the last loaded page.
     *
     * @return int
     */
    public function getNumberOfCommits()
    {
        return (int)$this->numberOfCommits;
    }

    /**
     * Number of pages for the query of the last loaded page.
     *
     * @return int
     */
    public function getPageCount()
    {
        return (int)ceil($this->numberOfCommits / $this->commitsPerPage);
    }
}
