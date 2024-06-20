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


define('GLPI_USE_CSRF_CHECK', false);
include('../../../inc/includes.php');

if (!Session::haveRight('user', PURGE)) { 
    Html::header(__('RgpdTools', 'rgpdtools'), $_SERVER['PHP_SELF'], 'tools', 'rgpdtools');
    echo '<h4 class="alert-title">'. __('Access denied', 'glpi') .'</h4>';
    Html::footer();
} else {
    $_POST['_glpi_csrf_token'] = Session::getNewCSRFToken();    
    $PluginRgpdtoolsRgpdtools = new PluginRgpdtoolsRgpdtools();

    if (isset($_REQUEST['generate'])) {
       if ($PluginRgpdtoolsRgpdtools::generateExport($_POST)) {
           Session::addMessageAfterRedirect(__('Export successfully generated.', 'rgpdtools'), true);
       }
       Html::back();
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

    if (isset($_REQUEST['deleteDocuments'])) {
       $nbDeleteDocuments = $PluginRgpdtoolsRgpdtools::deleteUploadedDocuments($_POST);
       Session::addMessageAfterRedirect($nbDeleteDocuments.' '.__('documents were deleted on server and database successfully.', 'rgpdtools'), true);
       Html::back();
    }

    // standard form
    Html::header(__('RgpdTools', 'rgpdtools'), $_SERVER['PHP_SELF'], 'tools', 'rgpdtools');
    $PluginRgpdtoolsRgpdtools = new PluginRgpdtoolsRgpdtools();
    $PluginRgpdtoolsRgpdtools->getFormsForCompleteForm();
    Html::footer();
}
