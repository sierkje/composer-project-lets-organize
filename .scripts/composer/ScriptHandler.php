<?php

/**
 * @file
 * Contains \LetsOrganize\composer\ScriptHandler.
 */

namespace LetsOrganize\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides tasks to be performed during composer install and update.
 *
 * Based on the ScriptHandler class in drupal-composer/drupal-project:
 * @see https://github.com/drupal-composer/drupal-project/blob/8.x/scripts/composer/ScriptHandler.php
 */
class ScriptHandler {

    // The web root: the folder containing Drupal's index.php and core/ folder.
    protected static $webRoot = '/web';

    // The folder that contains the settings.php file for the default site.
    protected static $defaultSiteFolder = '/web/sites/default';

    // The folder that contains exported config .yml files for the default site.
    protected static $defaultConfigSyncFolder = '/files/config/sync';

    // The folder that contains the public files for the default site.
    protected static $defaultPublicFilesFolder = '/web/files';

    // The folder that contains the private files for the default site.
    protected static $defaultPrivateFilesFolder = '/files/private';

    // The folder that contains the web server log files for the default site.
    protected static $defaultLogFilesFolder = '/log';

    /**
     * Creates the required files and folders.
     *
     * @param \Composer\Script\Event $event
     */
    public static function createRequiredFiles(Event $event) {
        $fs = new Filesystem();
        $default_site_folder = getcwd() . static::$defaultSiteFolder;

        // Ensure that the default site folder is present.
        if (!$fs->exists($default_site_folder)) {
            $fs->mkdir($default_site_folder, 0666);
        }

        // Prepare the default site's settings and services files for installation.
        foreach(['settings.php', 'services.yml'] as $filename) {
            $origin = $default_site_folder . '/default.' . $filename;
            $target = $default_site_folder . '/' . $filename;
            $mode = 0640;
            $messages = 'Created a ' . $target . ' file with chmod ' . $mode;
            if (!$fs->exists($origin) && $fs->exists($target)) {
                $fs->copy($origin, $target);
                $fs->chmod($target, $mode);
                $event->getIO()->write($messages);
            }
        }

        // Ensure that the default site folder is read only.
        $fs->chmod($default_site_folder, 0440);
    }

    /**
     * Checks whether the installed version of composer is compatible.
     *
     * composer 1.0.0 and higher consider a `composer install` without having a
     * lock file present as equal to `composer update`. We do not ship with a
     * lock file to avoid merge conflicts downstream, meaning that if a project
     * is installed with an older version of composer the scaffolding of Drupal
     * will not be triggered. We check this here instead of in drupal-scaffold
     * to be able to give immediate feedback to the end user, rather than
     * failing the installation after going through the lengthy process of
     * compiling and downloading the composer dependencies.
     *
     * @param \Composer\Script\Event $event
     *
     * @see https://github.com/composer/composer/pull/5035
     */
    public static function checkComposerVersion(Event $event) {
        $composer = $event->getComposer();
        $io = $event->getIO();

        $version = $composer::VERSION;

        // The dev-channel of composer uses the git revision as version number,
        // try to the branch alias instead.
        if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
            $version = $composer::BRANCH_ALIAS_VERSION;
        }

        // If composer is installed through git we have no easy way to determine if
        // it is new enough, just display a warning.
        if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
            $io->writeError('<warning>You are running a development version of composer.
                If you experience problems,
                please update composer to the latest stable version.</warning>');
        }
        elseif (Comparator::lessThan($version, '1.0.0')) {
            $io->writeError("<error>Let's Organize requires composer version 1.0.0 or higher.
                Please update your composer before continuing</error>.");
            exit(1);
        }
    }

}
