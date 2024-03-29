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

include('../../../inc/includes.php');

$PluginRgpdtoolsRgpdtools = new PluginRgpdtoolsRgpdtools();

if (isset($_REQUEST['generate'])) {
   if ($PluginRgpdtoolsRgpdtools::generateExport($_POST)) {
       Session::addMessageAfterRedirect(__('Export successfully generated.', 'rgpdtools'), true);
   }
    //Html::back();
}

if (isset($_REQUEST['deleteItems'])) {
    $nbUnlinkedElmts = $PluginRgpdtoolsRgpdtools::deleteUserLinkItems($_POST);

   if ($nbUnlinkedElmts) {
       $message = $nbUnlinkedElmts.__(' link(s) with the user where deleted successfully', 'rgpdtools');
   } else {
       $message = __('No links matching criteria were founded, no update query were executed.', 'rgpdtools');
   }

    Session::addMessageAfterRedirect(__($message, 'rgpdtools'), true);
    Html::back();
}

if (isset($_REQUEST['purgeUserLogs'])) {
   if ($PluginRgpdtoolsRgpdtools::anonymizeUserLogs($_POST)) {
       Session::addMessageAfterRedirect(__('Logs contains information about the user were anonymize successfully.', 'rgpdtools'), true);
   }
    Html::back();
}

// standard form
if (!isset($_REQUEST['generate'])) {
    Html::header(__('RgpdTools', 'rgpdtools'), $_SERVER['PHP_SELF'], 'tools', 'rgpdtools');
    $PluginRgpdtoolsRgpdtools = new PluginRgpdtoolsRgpdtools();
    $PluginRgpdtoolsRgpdtools->getFormsForCompleteForm();
    Html::footer();
}
