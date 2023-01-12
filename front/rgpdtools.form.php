<?php

include('../../../inc/includes.php');
Session::checkRight("user", PURGE);

$PluginRgpdtoolsRgpdtools = new PluginRgpdtoolsRgpdtools();
$includeGlpiDom = true;

if (isset($_REQUEST['generate'])) {
    if ($PluginRgpdtoolsRgpdtools::generateExport($_POST)) {
        Session::addMessageAfterRedirect(__('Export successfully generated.', 'rgpdtools'), true);
        
    }
    $includeGlpiDom = false;
    //Html::back();
}

if (isset($_REQUEST['deleteItems'])) {
    $nbUnlinkedElmts = $PluginRgpdtoolsRgpdtools::deleteUserLinkItems($_POST);
    
    if ($nbUnlinkedElmts) {
        $message = $nbUnlinkedElmts.' link(s) with the user where deleted successfully.';
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
if($includeGlpiDom) {
    // standard form
    Html::header(__('RgpdTools', 'rgpdtools'), $_SERVER['PHP_SELF'], 'tools', 'rgpdtools');
    $PluginRgpdtoolsRgpdtools = new PluginRgpdtoolsRgpdtools();
    $PluginRgpdtoolsRgpdtools->getFormsForCompleteForm();
    Html::footer();
}
