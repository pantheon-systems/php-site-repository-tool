<?php

namespace PhpSiteRepositoryTool;

use PhpSiteRepositoryTool\Utils\Git;
use PhpSiteRepositoryTool\Exceptions\DirNotEmptyException;
use PhpSiteRepositoryTool\Exceptions\Git\GitException;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class EnvironmentMergeManager.
 *
 * @package PhpSiteRepositoryTool
 */
class EnvironmentMergeManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->setLogger($logger);
    }

    /**
     * Applies the upstream changes to the local repository.
     *
     * @param string $siteRepoUrl
     * @param string $siteRepoBranch
     * @param string $fromBranch
     * @param string $toBranch
     * @param string $strategyOption
     * @param string $workdir
     * @param string $committerName
     * @param string $committerEmail
     * @param string $siteUuid
     * @param string $binding
     * @param bool $bypassSyncCode
     * @param bool $ff
     * @param bool $push
     * @param bool $verbose
     *
     * @return array
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\DirNotCreatedException
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     * @throws \PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException
     */
    public function mergeEnvironment(
        string $siteRepoUrl,
        string $siteRepoBranch,
        string $fromBranch,
        string $toBranch,
        string $strategyOption,
        string $workdir,
        string $committerName,
        string $committerEmail,
        string $siteUuid,
        string $binding,
        bool   $bypassSyncCode,
        bool   $ff,
        bool   $push,
        bool   $verbose
    ): array {
        $git = new Git(
            $this->logger,
            $committerName,
            $committerEmail,
            $workdir,
            $verbose,
            $siteUuid,
            $binding,
            $bypassSyncCode
        );

        $result = [
            'clone' => false,
            'pull' => false,
            'push' => false,
            'logs' => [],
            'conflicts' => '',
            'errormessage' => '',
        ];

        $remote = 'origin';

        try {
            $git->cloneRepository($siteRepoUrl, $siteRepoBranch);
            $result['clone'] = true;
            $result['logs'][] = 'Repository has been cloned';
        } catch (DirNotEmptyException $e) {
            $result['errormessage'] = sprintf("Workdir '%s' is not empty.", $workdir);
            return $result;
        } catch (GitException $e) {
            $result['errormessage'] = sprintf("Error cloning site repo: %s", $e->getMessage());
            return $result;
        }

        try {
            $git->merge($fromBranch, $remote, $strategyOption, !$ff);
            $result['logs'][] = 'Updates have been merged';
        } catch (GitMergeConflictException $e) {
            $result['conflicts'] = $git->listUnmergedFiles();
            $result['errormessage'] = sprintf("Merge conflict: %s", $e->getMessage());
            return $result;
        }

        $commitMessages = [
            $git->getRemoteMessage($fromBranch, $remote),
            sprintf("Merged '%s' into '%s'", $fromBranch, $toBranch),
        ];

        try {
            if ($git->isAnythingToCommit()) {
                $git->commit($commitMessages);
                $result['logs'][] = 'Updates have been committed';
            }
        } catch (GitException $e) {
            $result['errormessage'] = sprintf("Error committing to git: %s", $e->getMessage());
            return $result;
        }

        $result['pull'] = true;

        if ($push) {
            try {
                $git->pushAll();
                $result['push'] = true;
                $result['logs'][] = 'Updates have been pushed';
            } catch (GitException $e) {
                $result['errormessage'] = sprintf("Error during git push: %s", $e->getMessage());
                return $result;
            }
        }

        return $result;
    }
}
