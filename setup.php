<?php

/**
 * -------------------------------------------------------------------------
 * RgpdTools plugin for GLPI
 * Copyright (C) 2021 by the RgpdTools Development Team.
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * --------------------------------------------------------------------------
 */
define('PLUGIN_RGPDTOOLS_VERSION', '0.1.3');
define('PLUGIN_RGPDTOOLS_GLPI_MIN_VERSION', '9.5');
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
function plugin_init_rgpdtools()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['rgpdtools'] = true;

    Plugin::registerClass(
        'PluginRgpdtoolsRgpdTools',
        [
          'addtabon' => ['User'],
        ]
    );
    $PLUGIN_HOOKS['menu_toadd']['rgpdtools'] = ['tools'   => 'PluginRgpdtoolsRgpdTools'];
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_rgpdtools()
{
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
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_rgpdtools_check_prerequisites()
{
    return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_rgpdtools_check_config($verbose = false)
{
    if (true) { // Your configuration check
        return true;
    }

    if ($verbose) {
        echo __('Installed / not configured');
    }
    return false;
}
