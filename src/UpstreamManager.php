<?php

namespace PhpSiteRepositoryTool;

use PhpSiteRepositoryTool\Utils\Git;
use PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException;
use PhpSiteRepositoryTool\Exceptions\Git\GitException;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;

/**
 * Class UpstreamManager.
 *
 * @package PhpSiteRepositoryTool
 */
class UpstreamManager
{
    /**
     * Applies the upstream changes to the local repository.
     *
     * @param string $siteRepoUrl
     * @param string $siteRepoBranch
     * @param string $upstreamRepoUrl
     * @param string $upstreamRepoBranch
     * @param string $strategyOption
     * @param string $workdir
     * @param string $committerName
     * @param string $committerEmail
     * @param string $siteUuid
     * @param string $binding
     * @param string $updateBehavior
     * @param bool $bypassSyncCode
     * @param bool $ff
     * @param bool $clone
     * @param bool $push
     * @param bool $verbose
     *
     * @return array
     *
     * @throws GitException
     */
    public function applyUpstream(
        string $siteRepoUrl,
        string $siteRepoBranch,
        string $upstreamRepoUrl,
        string $upstreamRepoBranch,
        string $strategyOption,
        string $workdir,
        string $committerName,
        string $committerEmail,
        string $siteUuid,
        string $binding,
        string $updateBehavior,
        bool $bypassSyncCode,
        bool $ff,
        bool $clone,
        bool $push,
        bool $verbose
    ): array {
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
            'clone' => false,
            'pull' => false,
            'push' => false,
            'conflicts' => '',
            'errormessage' => '',
        ];

        if ($clone) {
            try {
                $repository->cloneRepository($siteRepoUrl, $siteRepoBranch);
                $result['clone'] = true;
            } catch (NotEmptyFolderException $e) {
                $result['errormessage'] = sprintf("Workdir '%s' is not empty.", $workdir);
                return $result;
            } catch (GitException $e) {
                $result['errormessage'] = sprintf("Error cloning site repo: %s", $e->getMessage());
                return $result;
            }
        }

        try {
            $repository->remoteAdd('upstream', $upstreamRepoUrl);
            $repository->fetch('upstream');
        } catch (GitException $e) {
            $result['errormessage'] = sprintf("Could not fetch upstream. Check that your upstream's git repository is accessible and that Pantheon has any required access tokens: %s", $e->getMessage());
            return $result;
        }

        $commitMessages = [
            $repository->getRemoteMessage($upstreamRepoBranch),
            sprintf('Was: Merged %s into %s.', $upstreamRepoBranch, $siteRepoBranch),
        ];

        $commitAuthor = '';
        try {
            $repository->merge(
                $upstreamRepoBranch,
                'upstream',
                $strategyOption,
                !$ff
            );
        } catch (GitMergeConflictException $e) {
            // WordPress License handling stuff.
            $unmergedFiles = $repository->listUnmergedFiles();
            if (!$this->allUnmergedFilesInAllowList($unmergedFiles)) {
                $result['conflicts'] = $unmergedFiles;
                $result['errormessage'] = sprintf("Merge conflict: %s", $e->getMessage());
                return $result;
            }

            foreach ($unmergedFiles as $file) {
                $repository->remove([$file]);
            }

            $commitMessages = [
                $repository->getRemoteMessage($upstreamRepoBranch),
                'System automatically resolved merge conflict, for more information see:',
                'https://pantheon.io/docs/start-states/wordpress#20220524-1',
            ];
            $commitAuthor = 'Pantheon Automation <bot@getpantheon.com>';
        }

        try {
            $repository->commit($commitMessages, $commitAuthor);
        } catch (GitException $e) {
            if ($e->getCode() > 1) {
                // The check for the exit code is added to mitigate git commit operation error for the case when
                // "nothing to commit, working tree clean" result is returned (which corresponds to exit code value of 1).
                // In terms of py-based site-repository-tool logic, this is not considered as an error.
                // @see https://github.com/pantheon-systems/site-repository-tool/blob/master/siterepositorytool/flow.py#L85
                $result['errormessage'] = sprintf("Error committing to git: %s", $e->getMessage());
                return $result;
            }
        }

        $result['pull'] = true;

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

    /**
     * Checks if all unmerged files are in the allow list.
     *
     * @param array $unmergedFiles
     *   The list of unmerged files.
     *
     * @return bool
     *   TRUE if all unmerged files are in the allow list, FALSE otherwise.
     */
    private function allUnmergedFilesInAllowList(array $unmergedFiles): bool
    {
        $allowPattern = '/wp-content\/themes\/.*\/LICENSE\.md/';

        if (empty($unmergedFiles)) {
            return false;
        }

        foreach ($unmergedFiles as $file) {
            if (!preg_match($allowPattern, $file)) {
                return false;
            }
        }
        return true;
    }
}
