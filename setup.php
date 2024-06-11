<?php
/**
 * ---------------------------------------------------------------------
 *  rgpdTools is a plugin to manage RGPD user informations
 *  ---------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of rgpdTools.
 *
 *  rgpdTools is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  rgpdTools is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 *  ---------------------------------------------------------------------
 *  @copyright Copyright © 2022-2023 probeSys'
 *  @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 *  @link      https://github.com/Probesys/glpi-plugins-rgpdtools
 *  @link      https://plugins.glpi-project.org/#/plugin/rgpdtools
 *  ---------------------------------------------------------------------
 */

define('PLUGIN_RGPDTOOLS_VERSION', '1.1.2');
define('PLUGIN_RGPDTOOLS_GLPI_MIN_VERSION', '9.5');
define('PLUGIN_RGPDTOOLS_GLPI_MAX_VERSION', '11');
if (!defined("PLUGIN_RGPDTOOLS_DIR")) {
    define('PLUGIN_RGPDTOOLS_DIR', Plugin::getPhpDir('rgpdtools'));
}
if (!defined("PLUGIN_RGPDTOOLS_WEB_DIR")) {
    define("PLUGIN_RGPDTOOLS_WEB_DIR", Plugin::getWebDir('rgpdtools'));
}

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_rgpdtools() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['rgpdtools'] = true;

    Plugin::registerClass(
        'PluginRgpdtoolsRgpdTools',
        [
          'addtabon' => ['User'],
        ]
    );
    if (Session::haveRight('user', PURGE)) {
        $PLUGIN_HOOKS['menu_toadd']['rgpdtools'] = ['tools'   => 'PluginRgpdtoolsRgpdTools'];
    }
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_rgpdtools() {
    return [
        'name' => __('RgpdTools', 'rgpdtools'),
        'version' => PLUGIN_RGPDTOOLS_VERSION,
        'author' => '<a href="http://www.probesys.com">Probesys</a>',
        'license' => '<a href="' . Plugin::getPhpDir('rgpdtools', false) . '/LICENSE" target="_blank">AGPLv3</a>',
        'homepage' => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_RGPDTOOLS_GLPI_MIN_VERSION,
            ]
        ]
    ];
}

/**
 * Check plugin's prerequisites before installation
 */
function plugin_rgpdtools_check_prerequisites() {
   if (version_compare(GLPI_VERSION, PLUGIN_RGPDTOOLS_GLPI_MIN_VERSION, 'lt') || version_compare(GLPI_VERSION, PLUGIN_RGPDTOOLS_GLPI_MAX_VERSION, 'ge')) {
       echo __('This plugin requires GLPI >= ' . PLUGIN_RGPDTOOLS_GLPI_MIN_VERSION . ' and GLPI < ' . PLUGIN_RGPDTOOLS_GLPI_MAX_VERSION . '<br>');
   } else {
       return true;
   }
    return false;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_rgpdtools_check_config($verbose = false) {
   if (true) { // Your configuration check
       return true;
   }

   if ($verbose) {
       echo __('Installed / not configured');
   }
    return false;
}
