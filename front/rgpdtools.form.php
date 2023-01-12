<?php

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
