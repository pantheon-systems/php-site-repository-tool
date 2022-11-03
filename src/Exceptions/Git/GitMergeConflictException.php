<?php

namespace PhpSiteRepositoryTool\Exceptions\Git;

use Throwable;

/**
 * Class GitMergeConflictException.
 *
 * @package PhpSiteRepositoryTool\Exceptions\Git
 */
class GitMergeConflictException extends GitException
{
    private array $unmergedFiles;

    /**
     * @inheritdoc
     *
     * @param array $unmergedFiles
     */
    public function __construct($message = '', $code = 0, $previous = null, $unmergedFiles = [])
    {
        parent::__construct($message, $code, $previous);

        $this->unmergedFiles = $unmergedFiles;
    }

    /**
     * Returns the list of unmerged files (the files with code conflicts).
     *
     * @return array
     */
    public function getUnmergedFiles()
    {
        return $this->unmergedFiles;
    }
}
