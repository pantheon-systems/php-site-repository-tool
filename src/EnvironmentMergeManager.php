<?php

namespace PhpSiteRepositoryTool;

use PhpSiteRepositoryTool\Utils\Git;
use PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException;
use PhpSiteRepositoryTool\Exceptions\Git\GitException;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;

/**
 * Class EnvironmentMergeManager.
 *
 * @package PhpSiteRepositoryTool
 */
class EnvironmentMergeManager
{
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
     * @throws GitException
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
        bool $bypassSyncCode,
        bool $ff,
        bool $push,
        bool $verbose
    ): array {
        $git = new Git(
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
            'conflicts' => '',
            'errormessage' => '',
        ];

        try {
            $git->cloneRepository($siteRepoUrl, $siteRepoBranch);
            $result['clone'] = true;
        } catch (NotEmptyFolderException $e) {
            $result['errormessage'] = sprintf("Workdir '%s' is not empty.", $workdir);
            return $result;
        } catch (GitException $e) {
            $result['errormessage'] = sprintf("Error cloning site repo: %s", $e->getMessage());
            return $result;
        }

        try {
            $git->merge($fromBranch, 'origin', $strategyOption, !$ff);
        } catch (GitMergeConflictException $e) {
            $result['conflicts'] = $git->listUnmergedFiles();
            $result['errormessage'] = sprintf("Merge conflict: %s", $e->getMessage());
            return $result;
        }

        $commitMessages = [
            $git->getRemoteMessage($fromBranch, 'origin'),
            sprintf("Merged '%s' into '%s'", $fromBranch, $toBranch),
        ];

        try {
            $git->commit($commitMessages);
        } catch (GitException $e) {
            if ($e->getCode() > 1) {
                // The check for the exit code is added to mitigate git commit operation error for the case when
                // "nothing to commit, working tree clean" result is returned (which corresponds to exit code value of 1).
                // In terms of py-based site-repository-tool logic, this is not considered as an error.
                // @see https://github.com/pantheon-systems/site-repository-tool/blob/master/siterepositorytool/flow.py#L159
                $result['errormessage'] = sprintf("Error committing to git: %s", $e->getMessage());
                return $result;
            }
        }

        $result['pull'] = true;

        if ($push) {
            try {
                $git->pushAll();
                $result['push'] = true;
            } catch (GitException $e) {
                $result['errormessage'] = sprintf("Error during git push: %s", $e->getMessage());
                return $result;
            }
        }

        return $result;
    }
}
