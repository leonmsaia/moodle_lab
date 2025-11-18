<?php
/**
 * Capability definitions for the local_user_reporter_chg plugin.
 *
 * This file declares all permission capabilities used by the plugin.
 * Moodle reads it automatically when installing or upgrading the plugin.
 *
 * Capabilities define which roles may:
 * - Access plugin pages
 * - Execute actions
 * - View or manage data
 *
 * In this plugin we define a single read-only system-level capability,
 * allowing managers and administrators to access the user + course report.
 *
 * @package     local_user_reporter_chg
 * @category    access
 * @author      Leon. M. Saia
 * @email       leonmsaia@gmail.com
 * @website     https://leonmsaia.com
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Capability list for this plugin.
 *
 * Structure:
 *   'local/pluginname:capability' => [
 *       'captype'      => 'read' or 'write',
 *       'contextlevel' => CONTEXT_... constant,
 *       'archetypes'   => default permissions for standard roles,
 *   ]
 *
 * contextlevel:
 *   - CONTEXT_SYSTEM: applies to the whole site
 *   - CONTEXT_COURSE: applies to a specific course
 *   - CONTEXT_USER:   applies to per-user contexts
 *
 * archetypes:
 *   Predefined Moodle roles and their default permissions.
 */
$capabilities = [
    'local/user_reporter_chg:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
            'admin'   => CAP_ALLOW,
        ],
    ],
];
