<?php

/**
 * @file
 * Contains sierkje\LetsOrganizeProject\composer\ScriptHandler.
 */

namespace sierkje\LetsOrganizeProject\Composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;

/**
 * Provides tasks to be performed during composer install and update.
 *
 * Based on the ScriptHandler class in drupal-composer/drupal-project:
 * @see https://github.com/drupal-composer/drupal-project/blob/8.x/scripts/composer/ScriptHandler.php
 */
class ScriptHandler {

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
