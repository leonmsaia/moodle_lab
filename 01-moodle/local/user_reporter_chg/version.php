<?php
/**
 * Version metadata for the local_user_reporter_chg plugin.
 *
 * This file defines essential information required by Moodle
 * to identify, install, upgrade, and validate the plugin.
 *
 * Metadata fields:
 *  - component: Full frankenstyle name of the plugin.
 *  - version:   Plugin version in YYYYMMDDXX format.
 *               Increment this number when releasing updates.
 *  - requires:  Minimum Moodle version required for this plugin
 *               to run. The value corresponds to the internal
 *               MOODLE_X_Y_VERSION constant.
 *  - maturity:  Development stage of the plugin:
 *                  MATURITY_ALPHA
 *                  MATURITY_BETA
 *                  MATURITY_RC
 *                  MATURITY_STABLE
 *  - release:   Human-readable release label (semantic version recommended).
 *
 * @package     local_user_reporter_chg
 * @category    version
 * @author      Leon. M. Saia
 * @email       leonmsaia@gmail.com
 * @website     https://leonmsaia.com
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_user_reporter_chg';
$plugin->version   = 2025111800;
$plugin->requires  = 2022041900;
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';