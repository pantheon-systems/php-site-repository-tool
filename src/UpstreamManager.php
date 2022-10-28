<?php

namespace PhpSiteRepositoryTool;

use PhpSiteRepositoryTool\Utils\Git;
use PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException;
use PhpSiteRepositoryTool\Exceptions\Git\GitException;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;

class UpstreamManager
{

    /**
     * Applies the upstream changes to the local repository.
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
        bool $bypassSyncCode,
        bool $ff,
        bool $clone,
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
            'clone' => false,
            'pull' => true,
            'push' => true,
            'conflicts' => '',
            'errormessage' => '',
        ];

        // This will be overriden later if handling a merge conflict.
        $commitAuthor = '';

        if ($clone) {
            try {
                $repository->clone($siteRepoUrl, $siteRepoBranch);
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
            $repository->addRemote('upstream', $upstreamRepoUrl);
            $repository->fetch('upstream');
        } catch (GitException $e) {
            $result['errormessage'] = sprintf("Could not fetch upstream. Check that your upstream's git repository is accessible and that Pantheon has any required access tokens: %s", $e->getMessage());
            return $result;
        }

        $commitMessages = [
            $repository->getRemoteMessage($upstreamRepoBranch),
            sprintf('Was: Merged %s into %s.', $upstreamRepoBranch, $siteRepoBranch),
        ];

        try {
            $repository->merge('upstream', $upstreamRepoBranch, $strategyOption, !$ff);
        } catch (GitMergeConflictException $e) {
            // WordPress License handling stuff.
            $unmergedFiles = $repository->listUnmergedFiles();
            if (!$this->allUnmergedFilesInAllowList($unmergedFiles)) {
                $result['conflicts'] = $unmergedFiles;
                $result['errormessage'] = sprintf("Merge conflict: %s", $e->getMessage());
                $result['pull'] = false;
                return $result;
            }

            foreach ($unmergedFiles as $file) {
                $repository->remove($file);
            }

            $commitMessages = [
                $repository->getRemoteMessage($upstreamRepoBranch),
                'System automatically resolved merge conflict, for more information see:',
                'https://pantheon.io/docs/start-states/wordpress#20220524-1',
            ];
            $commitAuthor = 'Pantheon Automation <bot@getpantheon.com>';
        }

        // @todo Investigate why it was set to true initially.
        $result['push'] = false;

        try {
            $repository->commit($commitMessages, $commitAuthor);
        } catch (GitException $e) {
            $result['errormessage'] = sprintf("Error committing to git: %s", $e->getMessage());
            return $result;
        }

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
