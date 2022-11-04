<?php

/**
 * @file
 * Contains \DrupalComposerManaged\ComposerScripts.
 */

namespace PhpSiteRepositoryTool;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ComposerScripts
{
    public static function configureForPhpVersion(Event $event)
    {
        $scenario = static::determineScenario();
        $command = "composer scenario $scenario";
        print "> $command\n";
        passthru($command);
    }

    private static function determineScenario()
    {
        switch (PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) {
            case '5.6':
            case '7.0':
                return 'php56';

            case '7.1':
            case '7.2':
            case '7.3':
                return 'default';

            case '7.4':
                return 'php74';

            case '8.0':
            case '8.1':
                return 'php80';
        }

        return 'default';
    }
}
