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
    ) {
        $repository = new Git(
            $committerName,
            $committerEmail,
            $workdir,
            $verbose,
            $siteUuid,
            $binding,
            $bypassSyncCode
        );

        $result = [
            'clone' => true,
            'pull' => true,
            'push' => true,
            'conflicts' => '',
            'errormessage' => '',
        ];

        try {
            $repository->cloneRepository($siteRepoUrl, $siteRepoBranch);
            $result['clone'] = true;
        } catch (NotEmptyFolderException $e) {
            $result['clone'] = false;
            $result['errormessage'] = sprintf("Workdir '%s' is not empty.", $workdir);
            return $result;
        } catch (GitException $e) {
            $result['clone'] = false;
            $result['errormessage'] = sprintf("Error cloning site repo: %s", $e->getMessage());
            return $result;
        }

        try {
            $repository->merge($fromBranch, 'origin', $strategyOption, !$ff);
        } catch (GitMergeConflictException $e) {
            $result['conflicts'] = $repository->listUnmergedFiles();
            $result['errormessage'] = sprintf("Merge conflict: %s", $e->getMessage());
            $result['pull'] = false;
            return $result;
        }


        $commitMessages = [
            $repository->getRemoteMessage($fromBranch, 'origin'),
            sprintf("Merged '%s' into '%s'", $fromBranch, $toBranch),
        ];

        try {
            $repository->commit($commitMessages);
        } catch (GitException $e) {
            if ($e->getCode() > 1) {
                $result['errormessage'] = sprintf("Error committing to git: %s", $e->getMessage());
                return $result;
            }
        }

         // @todo Investigate why it was set to true initially.
         $result['push'] = false;

        if ($push) {
            try {
                $repository->pushAll();
                $result['push'] = true;
            } catch (GitException $e) {
                $result['errormessage'] = sprintf("Error during git push: %s", $e->getMessage());
                return $result;
            }
        }

        return $result;
    }
}
