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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    echo __('Error during installation of the rgpdtools plugin, please run "php composer.phar install --no-dev" in the plugin tree', 'rgpdtools');
    die();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;

class PluginRgpdtoolsRgpdtools
{
   public function __construct() {
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item::getType()) {
         case User::getType():
              return __('RgpdTools', 'rgpdtools');
              break;
      }
       return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($item::getType()) {
         case User::getType():
            self::displayTabContentForUser($item);
              break;
      }

       return true;
   }

   public static function getMenuName() {
       return __('RgpdTools', 'rgpdtools');
   }

   public static function getIcon() {
       return "fa fa-user-shield";
   }

   public static function getMenuContent() {
       $menu = [
           'title' => self::getMenuName(),
           'page' => Plugin::getPhpDir('rgpdtools', false) . '/front/rgpdtools.form.php',
           'icon' => self::getIcon(),
       ];

       return $menu;
   }

   public static function anonymizeUserLogs($POST) {
       $userID = $POST['userID'];
      if (!$userID) {
          Session::addMessageAfterRedirect(__("user is required", 'rgpdtools'), true, WARNING, true);
          Html::redirect('rgpdtools.form.php');
      }
       $retentionPeriod = $POST['userLogRetentionPeriod'];
       self::anonymizeUserLogActivity($userID, $retentionPeriod);

       return true;
   }

   public static function deleteUserLinkItems($POST) {
       $userID = $POST['userID'];
       $allUser = array_key_exists('allUser', $POST);
      if (!$userID && !$allUser) {
          Session::addMessageAfterRedirect(__("user is required or all user checkbox", 'rgpdtools'), true, WARNING, true);
          Html::redirect('rgpdtools.form.php');
      }
       $deleteItemTypes = $POST['deleteItemTypes'];
       $retentionPeriods = $POST['retentionPeriods'];
       $nbUnlinkedElmts = 0;
      foreach ($deleteItemTypes as $itemType) {
          $nbUnlinkedElmts += self::deleteDocumentsToDate($userID, $itemType, $retentionPeriods[$itemType], $allUser);
      }

       return $nbUnlinkedElmts;
   }

   public static function generateExport($POST) {
       $userID = $POST['userID'];
      if (!$userID) {
          Session::addMessageAfterRedirect(__("user is required", 'rgpdtools'), true, WARNING, true);
          Html::redirect('rgpdtools.form.php');
      }
       $user = new User();
       $user->getFromDB($userID);
       $now = new DateTime();
       $rand = mt_rand();
       $filename = 'export-rgpd-data_' . $user->getField('name') . '_' . $now->format('d-m-Y') . '_' . $rand . '.ods';

       $spreadsheet = new Spreadsheet();

       //First tab for user infos
       $nbWorkSheet = 0;
       $ws_user = new Worksheet($spreadsheet, 'User');
       $spreadsheet->addSheet($ws_user, $nbWorkSheet);
       $spreadsheet->setActiveSheetIndex($nbWorkSheet);
       $objectInfos = self::getUserInfos($user);
       self::injectRowHeader($spreadsheet, $objectInfos, 'User');
       self::injectRowValues($spreadsheet, $objectInfos, 2, 'User');

       // récupération des éléments associés au user
       $allUsedItems = self::getAllUsedItemsForUser($userID);
       // pour chaque élément séléctionné ajout d'un onglet
       $itemTypes = $POST['itemTypes'];
      foreach ($itemTypes as $itemType) {
          $nbWorkSheet++;
          $new_ws = new Worksheet($spreadsheet, $itemType);
          $spreadsheet->addSheet($new_ws, $nbWorkSheet);
          $spreadsheet->setActiveSheetIndex($nbWorkSheet);
         if (array_key_exists($itemType, $allUsedItems)) {
             $objectItems = $allUsedItems[$itemType];
             // inject header
             self::injectRowHeader($spreadsheet, $objectItems[0], $itemType);
             // inject values
             $row = 2;
            foreach ($objectItems as $objectInfos) {
               self::injectRowValues($spreadsheet, $objectInfos, $row, $itemType);
               $row++;
            }
         }
      }

       header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
       header('Content-Disposition: attachment;filename="' . $filename . '"');
       header("Pragma: no-cache");
       header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
       header("Cache-Control: post-check=0, pre-check=0", false);
       $writer = new Ods($spreadsheet);
       $writer->save('php://output');
   }


   public static function deleteUploadedDocuments($POST) {
       $userID = $POST['userID'];
       $allUser = array_key_exists('allUser', $POST);
      if (!$userID && !$allUser) {
           Session::addMessageAfterRedirect(__("user is required or all user checkbox", 'rgpdtools'), true, WARNING, true);
           Html::redirect('rgpdtools.form.php');
      }
       $deleteItemTypes = $POST['deleteItemTypes'];
       $retentionPeriods = $POST['retentionPeriods'];
       $nbDeleteDocuments = 0;
      foreach ($deleteItemTypes as $itemType) {
          $nbDeleteDocuments += self::deleteDocumentsToDate($userID, $itemType, $retentionPeriods[$itemType], $allUser);
      }

       return $nbDeleteDocuments;
   }

   private static function displayTabContentForUser(User $item) {
       $users_id = $item->getField('id');
       $itemsTypes = self::getUserAssociableItemTypes();
       $html = '';
       $html .= self::generateExportForm($users_id, $itemsTypes);
       $html .= self::generateUnlinkItemsForm($users_id, $itemsTypes);
       $html .= self::generateAnonymiseForm($users_id);

       echo $html;
   }

   public function getFormsForCompleteForm() {
       $itemsTypes = self::getUserAssociableItemTypes();
       $html = '<div class="row">';
       $users_id = null;

       $html .= self::generateExportForm($users_id, $itemsTypes);
       $html .= self::generateAnonymiseForm($users_id);
       $html .= self::generateUnlinkItemsForm($users_id, $itemsTypes);
       $html .= self::generateDeleteDocumentsForm($users_id, $itemsTypes);

       $html .= '</div>';

       echo $html;
   }

   private static function generateExportForm($users_id, $itemsTypes) {
       $html = '';
       $rand = mt_rand();
       $idForm = "useritemsexport_form$rand";

       $html .= "<div class='col-md-6'>";
       $html .= "<div class='center card card-sm mb-3'>";
       $html .= "<form method='post' name='$idForm' id='$idForm' target='_blank'
                  action=\"" . Plugin::getWebDir('rgpdtools') . "/front/rgpdtools.form.php\" onsubmit=\"return confirm('" . __('Are you sure you want to execute this operation', 'rgpdtools') . "?');\">";
       $html .= '<div class="spaced">';
       $html .= '<div class="card-body">';
       $html .= '<h5 class="card-title">' . __('Export users data', 'rgpdtools') . '</h5>';
       $html .= "<table class='tab_cadre_fixe'>";
       $html .= '<tbody>';
       $html .= self::getUserIdBlock($users_id);
       $html .= '<tr class="tab_bg_2">';
       $html .= '<th>';
       $html .= '<div class="form-group-checkbox">
                  <input title="' . __('Check all') . '" type="checkbox" class="new_checkbox" name="_checkall_' . $rand . '" id="checkall_' . $rand . '" onclick="if ( checkAsCheckboxes(\'checkall_' . $rand . '\', \'' . $idForm . '\')) {return true;}">
                  <label class="label-checkbox" for="checkall_' . $rand . '" title="' . __('Check all') . '">
                     <span class="check"></span>
                     <span class="box"></span>
                  </label>
               </div>
               <script type="text/javascript">
            //<![CDATA[
            $(function() {$(\'#' . $idForm . ' input[type="checkbox"]\').shiftSelectable();});
            //]]>
            </script></th>' . "\n";
       $html .= '</th>';
       $html .= '<th colspan="3">' . __('Choosing what to export', 'rgpdtools') . '</th>';
       $html .= '</tr>';
      foreach ($itemsTypes as $itemType) {
          $html .= '<tr class="tab_bg_2">';
          $html .= '<td>';
          $html .= '<span class="form-group-checkbox">';
          $html .= '<input type="checkbox" class="new_checkbox" id="itemTypes_' . $itemType . '" name="itemTypes[]" value="' . $itemType . '" />';
          $html .= '<label class="label-checkbox" title="" for="itemTypes_' . $itemType . '"> <span class="check"></span> <span class="box"></span>&nbsp;</label>';
          $html .= '</span>';
          $html .= '</td>';
          $html .= '<td>' . __($itemType) . '</td>';
          $html .= '<td colspan="2"></td>';
          $html .= '</tr>';
      }

       $html .= "<tr class='tab_bg_2'>";
       $html .= "<th colspan='4' class='center'><input type='submit' name='generate' value=\"" . __('Export') . "\" class='vsubmit'></th>";
       $html .= "</tr>";
       $html .= '</tbody>';
       $html .= "</table>";
       $html .= '</div>';
       $html .= Html::closeForm(false);
       $html .= "</div>";
       $html .= "</div>";
       $html .= "</div>";

       return $html;
   }

   private static function generateUnlinkItemsForm($users_id, $itemsTypes) {
       $html = '';
       $rand = mt_rand();

       $config = [
           'value' => 6,
           'display' => false,
           'values' => range(1, 100),
           'class' => 'required',
           'noselect2' => false
       ];
       $values = [];
       for ($i = 0; $i < 100; $i++) {
           $values[$i] = $i . ' ' . __('month');
       }

       $idForm = "useritemsdelete_form$rand";
       $html .= "<div class='col-md-6'>";
       $html .= "<div class='center card card-sm mb-3'>";
       $html .= "<form method='post' name='$idForm' id='$idForm'
                  action=\"" . Plugin::getWebDir('rgpdtools') . "/front/rgpdtools.form.php\" onsubmit=\"return confirm('" . __('Are you sure you want to execute this operation', 'rgpdtools') . "?');\">";
       $html .= '<div class="spaced">';
       $html .= '<div class="card-body">';
       $html .= '<h5 class="card-title">' . __('Removal of links to the user', 'rgpdtools'). '</h5>';
       $html .= "<table class='tab_cadre_fixe'>";
       $html .= '<tbody>';
       $html .= self::getUserIdBlock($users_id, true);
       $html .= '</tbody>';
       $html .= "</table>";
       $html .= "<table class='tab_cadre_fixe' id='" . $idForm . "-checkable'>";
       $html .= '<tbody>';
       $html .= '<tr class="tab_bg_2">';
       $html .= '<th>';
       $html .= '<div class="form-group-checkbox">
                  <input title="' . __('Check all') . '" type="checkbox" class="new_checkbox" name="_checkall_' . $rand . '" id="checkall_' . $rand . '" onclick="if ( checkAsCheckboxes(\'checkall_' . $rand . '\', \'' . $idForm . '-checkable\')) {return true;}">
                  <label class="label-checkbox" for="checkall_' . $rand . '" title="' . __('Check all') . '">
                     <span class="check"></span>
                     <span class="box"></span>
                  </label>
               </div>
               <script type="text/javascript">
            //<![CDATA[
            $(function() {$(\'#' . $idForm . '-checkable input[type="checkbox"] \').shiftSelectable();});
            //]]>
            </script></th>' . "\n";
       $html .= '</th>';
       $html .= '<th>' . __('Choice of elements for which to remove links to the user', 'rgpdtools') . '</th>';
       $html .= '<th colspan="2">' . __('For each item, retention period', 'rgpdtools') . '</th>';
       $html .= '</tr>';

       foreach ($itemsTypes as $itemType) {
           $html .= '<tr class="tab_bg_2">';
           $html .= '<td>';
           $html .= '<span class="form-group-checkbox">';
           $html .= '<input type="checkbox" class="new_checkbox" id="deleteItemTypes_' . $itemType . '" name="deleteItemTypes[]" value="' . $itemType . '" />';
           $html .= '<label class="label-checkbox" title="" for="deleteItemTypes_' . $itemType . '"> <span class="check"></span> <span class="box"></span>&nbsp;</label>';
           $html .= '</span>';
           $html .= '</td>';
           $html .= '<td>' . __($itemType) . '</td>';
           $html_parts = Dropdown::showFromArray('retentionPeriods[' . $itemType . ']', $values, $config);
           $html .= '<td colspan="2">' . $html_parts . '</td>';
           $html .= '</tr>';
       }
       $html .= "<tr class='tab_bg_2'>";
       $html .= "<th colspan='4' class='center'><input type='submit' name='deleteItems' value=\"" . __('Delete') . "\" class='vsubmit'></th>";
       $html .= "</tr>";
       $html .= '</tbody>';
       $html .= "</table>";
       $html .= '</div>';
       $html .= Html::closeForm(false);
       $html .= "</div>";
       $html .= "</div>";
       $html .= "</div>";

       return $html;
   }

   private static function getUserIdBlock($users_id, $withAlluserCheckbox = false) {
       $user = '';
       $html = '';
      if ($users_id) {
          $html .= "<input type='hidden' name='userID' value='$users_id'>";
      } else {
          $html .= '<tr class="tab_bg_2">';
          $html .= '<td colspan="3">' . _n('User', 'User', 2) . '</td>';
          $html .= '<td>';
          $userSelectorOptions = [
              'name' => 'userID',
              //'used' => '',
              'right' => 'all',
              'comments' => false,
              'display' => false,
          ];
          if (!$withAlluserCheckbox) {
              $userSelectorOptions['specific_tags'] = ['required' => 'required'];
          }
          $html .= User::dropdown($userSelectorOptions);
          if ($withAlluserCheckbox) {
              $html .= '<br/><input type="checkbox" name="allUser" value="1">&nbsp;' . __('Apply to all users', 'rgpdtools');
          }
          $html .= '</td>';
          $html .= '</tr>';
      }
       return $html;
   }

   private static function generateAnonymiseForm($users_id) {
       $html = '';
       $rand = mt_rand();
       $config = [
           'value' => 6,
           'display' => false,
           'values' => range(1, 100),
           'class' => 'required',
           'noselect2' => false
       ];
       $values = [];
       for ($i = 0; $i < 100; $i++) {
           $values[$i] = $i . ' ' . __('month');
       }
       $idForm = "userpurgelogs_form$rand";
       $html .= "<div class='col-md-6'>";
       $html .= "<div class='center card card-sm mb-3'>";
       $html .= "<form method='post' name='$idForm' id='$idForm'
                  action=\"" . Plugin::getWebDir('rgpdtools') . "/front/rgpdtools.form.php\" onsubmit=\"return confirm('" . __('Are you sure you want to execute this operation', 'rgpdtools') . "?');\">";
       $html .= '<div class="spaced">';
       $html .= '<div class="card-body">';
       $html .= '<h5 class="card-title">' . __('Purge logs referring to the user', 'rgpdtools'). '</h5>';
       $html .= "<table class='tab_cadre_fixe'>";
       $html .= '<tbody>';
       $html .= self::getUserIdBlock($users_id);
       $html .= '<tr class="tab_bg_2">';
       $html .= '<td colspan="2">' . __('Retention Period', 'rgpdtools') . '</td>';
       $html_parts = Dropdown::showFromArray('userLogRetentionPeriod', $values, $config);
       $html .= '<td colspan="2">' . $html_parts . '</td>';
       $html .= '</tr>';
       $html .= "<tr class='tab_bg_2'>";
       $html .= "<th colspan='4' class='center'><input type='submit' name='purgeUserLogs' value=\"" . __('Purge') . "\" class='vsubmit'></th>";
       $html .= "</tr>";
       $html .= '</tbody>';
       $html .= "</table>";
       $html .= '</div>';
       $html .= Html::closeForm(false);
       $html .= "</div>";
       $html .= "</div>";
       $html .= "</div>";

       return $html;
   }

   private static function generateDeleteDocumentsForm($users_id, $itemsTypes) {
       $html = '';
       $rand = mt_rand();

       $config = [
           'value' => 6,
           'display' => false,
           'values' => range(1, 100),
           'class' => 'required',
           'noselect2' => false
       ];
       $values = [];
       for ($i = 0; $i < 100; $i++) {
           $values[$i] = $i . ' ' . __('month');
       }

       $idForm = "deleteDocuments_form$rand";
       $html .= "<div class='col-md-6'>";
       $html .= "<div class='center card card-sm mb-3'>";
       $html .= "<form method='post' name='$idForm' id='$idForm'
                  action=\"" . Plugin::getWebDir('rgpdtools') . "/front/rgpdtools.form.php\" onsubmit=\"return confirm('" . __('Are you sure you want to execute this operation', 'rgpdtools') . "?');\">";
       $html .= '<div class="spaced">';
       $html .= '<div class="card-body">';
       $html .= '<h5 class="card-title">' . __('Delete old uploaded documents', 'rgpdtools'). '</h5>';
       $html .= "<table class='tab_cadre_fixe'>";
       $html .= '<tbody>';
       $html .= self::getUserIdBlock($users_id, true);
       $html .= '</tbody>';
       $html .= "</table>";
       $html .= "<table class='tab_cadre_fixe' id='" . $idForm . "-checkable'>";
       $html .= '<tbody>';
       $html .= '<tr class="tab_bg_2">';
       $html .= '<th>';
       $html .= '<div class="form-group-checkbox">
                  <input title="' . __('Check all') . '" type="checkbox" class="new_checkbox" name="_checkall_' . $rand . '" id="checkall_' . $rand . '" onclick="if ( checkAsCheckboxes(\'checkall_' . $rand . '\', \'' . $idForm . '-checkable\')) {return true;}">
                  <label class="label-checkbox" for="checkall_' . $rand . '" title="' . __('Check all') . '">
                     <span class="check"></span>
                     <span class="box"></span>
                  </label>
               </div>
               <script type="text/javascript">
            //<![CDATA[
            $(function() {$(\'#' . $idForm . '-checkable input[type="checkbox"] \').shiftSelectable();});
            //]]>
            </script></th>' . "\n";
       $html .= '</th>';
       $html .= '<th>' . __('Choice of elements for which to delete uploaded documentss', 'rgpdtools') . '</th>';
       $html .= '<th colspan="2">' . __('For each item, retention period', 'rgpdtools') . '</th>';
       $html .= '</tr>';

       foreach ($itemsTypes as $itemType) {
           $html .= '<tr class="tab_bg_2">';
           $html .= '<td>';
           $html .= '<span class="form-group-checkbox">';
           $html .= '<input type="checkbox" class="new_checkbox" id="deleteItemTypes_' . $itemType . '" name="deleteItemTypes[]" value="' . $itemType . '" />';
           $html .= '<label class="label-checkbox" title="" for="deleteItemTypes_' . $itemType . '"> <span class="check"></span> <span class="box"></span>&nbsp;</label>';
           $html .= '</span>';
           $html .= '</td>';
           $html .= '<td>' . __($itemType) . '</td>';
           $html_parts = Dropdown::showFromArray('retentionPeriods[' . $itemType . ']', $values, $config);
           $html .= '<td colspan="2">' . $html_parts . '</td>';
           $html .= '</tr>';
       }
       $html .= "<tr class='tab_bg_2'>";
       $html .= "<th colspan='4' class='center'><input type='submit' name='deleteDocuments' value=\"" . __('Delete') . "\" class='vsubmit'></th>";
       $html .= "</tr>";
       $html .= '</tbody>';
       $html .= "</table>";
       $html .= '</div>';
       $html .= Html::closeForm(false);
       $html .= "</div>";
       $html .= "</div>";
       $html .= "</div>";

       return $html;
   }

    /**
     * Get all used items for user
     * @param ID of user
     * @return array
     */
   private static function getAllUsedItemsForUser($ID) {
       global $DB;

       $items = [];

      foreach (self::getUserAssociableItemTypes() as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView() && !in_array($itemtype, ['Ticket'])) {
             $itemtable = getTableForItemType($itemtype);

             $query = "SELECT *
                      FROM `$itemtable`
                      WHERE `users_id` = '$ID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `is_template` = '0' ";
            }
            if ($item->maybeDeleted()) {
                $query .= " AND `is_deleted` = '0' ";
            }
             $result = $DB->query($query);

             $type_name = $item->getTypeName();

            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetchAssoc($result)) {
                   $items[$itemtype][] = $data;
               }
            }
         }
      }

       // Consumables
       $consumables = $DB->request(
           [
                   'SELECT' => ['name', 'otherserial'],
                   'FROM' => ConsumableItem::getTable(),
                   'WHERE' => [
                       'id' => new QuerySubQuery(
                           [
                           'SELECT' => 'consumableitems_id',
                           'FROM' => Consumable::getTable(),
                           'WHERE' => [
                               'itemtype' => User::class,
                               'items_id' => $ID
                           ],
                               ]
                       )
                   ],
               ]
       );
      foreach ($consumables as $data) {
          $items['ConsumableItem'][] = $data;
      }

       // Tickets
       $tickets = $DB->request(
           [
                   'SELECT' => ['*'],
                   //'DISTINCT' => true,
                   'FROM' => Ticket::getTable(),
                   'LEFT JOIN' => [
                       Ticket_User::getTable() => [
                           'FKEY' => [
                               Ticket::getTable() => 'id',
                               Ticket_User::getTable() => 'tickets_id'
                           ]
                       ]
                   ],
                   'WHERE' => [
                       'OR' => [
                           'users_id_recipient' => $ID,
                           'users_id' => $ID
                       ],
                   ],
                   'ORDER' => 'date'
               ]
       );
      foreach ($tickets as $data) {
          $items['Ticket'][] = $data;
      }

       // getComputersIDs
       $computersIds = [];
      if (array_key_exists('Computer', $items) && count($items['Computer'])) {
         foreach ($items['Computer'] as $computer) {
             $computersIds[] = $computer['id'];
         }
      }
       // Software
       $softwares = self::getUserSoftwares($computersIds);
      foreach ($softwares as $data) {
          $items['Software'][] = $data;
      }

       //SoftwaresLicences getUserSoftwareLicences
       $softwareLicenses = self::getUserSoftwareLicences($computersIds);
      foreach ($softwareLicenses as $data) {
          $items['SoftwareLicense'][] = $data;
      }

       return $items;
   }

   private static function getUserAssociableItemTypes() {
       global $CFG_GLPI;

       $moreTypes = ['Ticket', 'ITILFollowup', 'TicketTask'];

       return array_merge($CFG_GLPI['linkuser_types'], $moreTypes);
   }

   private static function injectRowHeader($spreadsheet, $objectInfos, $itemType) {
       $col = 1;
       $row = 1;
       $sheet = $spreadsheet->getActiveSheet();
       $exportablefields = self::getExportablefields($itemType);
      foreach (array_keys($objectInfos) as $key) {
         if (!count($exportablefields) || in_array($key, $exportablefields)) {
            $sheet->setCellValueByColumnAndRow($col, $row, __($key));
            $col++;
         }
      }
       return $row++;
   }

   private static function injectRowValues($spreadsheet, $objectInfos, $row, $itemType) {
       $col = 1;
       $sheet = $spreadsheet->getActiveSheet();
       $exportablefields = self::getExportablefields($itemType);
      foreach ($objectInfos as $key => $info) {
         if (!count($exportablefields) || in_array($key, $exportablefields)) {
            $sheet->setCellValueByColumnAndRow($col, $row, $info);
            $col++;
         }
      }
       return $row++;
   }

   private static function getUserInfos($user) {
       $infos = [];

       $infos = [
           'id' => $user->getID(),
           'name' => $user->getField('name'),
           'realname' => $user->getField('realname'),
           'firstname' => $user->getField('firstname'),
           'phone' => $user->getField('phone'),
           'phone2' => $user->getField('phone2'),
           'mobile' => $user->getField('mobile'),
           'email' => $user->getDefaultEmail(),
           'comment' => $user->getField('comment'),
           'date_creation' => $user->getField('date_creation'),
           'date_mod' => $user->getField('date_mod'),
       ];

       return $infos;
   }

    /**
     * fonction permettant de limiter les champs remontés dans l'export en fonction du type d'élément
     * @param type $className
     * @return string
     */
   private static function getExportablefields($className) {
       $fields = [];
       // id fields is empty, all fields are export
      switch ($className) {
         case 'Computer':
            //$fields = ['id','name','serial','otherserial', 'contact', 'contact_num', 'comment','date_mod', 'date_creation'];
              break;
         case 'User':
             // $fields = ['id', 'name','phone','phone2','mobile','email'];
              break;
      }
       return $fields;
   }

   private static function unlinkUserAssociateElementsToDate($userID, $className, $retentionPeriod, $allUser = false) {
       global $DB;
      if (!class_exists($className)) {
          $errorMessage = sprintf(
              __('The class %1$s can\'t be instanciate because not finded on GLPI.', 'rgpdtools'),
              $className
          );
          throw new \Exception($errorMessage);
      }
       $date = new DateTime();
       $date->sub(new DateInterval('P' . $retentionPeriod . 'M'));

       $log = new Log();
       $object = new $className();
       //$object = new Computer(); // for test
       // recherche des éléments liés au user en bdd
       $querySelect = "SELECT t1.id FROM " . $object->getTable() . " t1 "
               . "INNER JOIN " . $log->getTable() . " lg ON t1.id = lg.items_id AND itemtype='" . $className . "' AND 	id_search_option=70 "
               . "WHERE new_value LIKE '% (" . $userID . ")' AND lg.date_mod <= '" . $date->format('Y-m-d') . "' ";
      if (!$allUser) {
          $querySelect .= "AND users_id=$userID ";
      }
       $querySelect .= "GROUP BY t1.id";

       $results = $DB->query($querySelect);
       $nbUnlinkedElmts = $DB->numrows($results);
      if ($nbUnlinkedElmts) {
          // construction du tableau des ids
          $objectsIds = [];
         while ($row = $DB->fetchAssoc($results)) {
             array_push($objectsIds, $row['id']);
         }
          $query = "UPDATE " . $object->getTable() . " SET users_id=NULL WHERE id IN (" . implode(',', $objectsIds) . ")";
          $DB->query($query);
      }

       return $nbUnlinkedElmts;
   }

   private static function deleteDocumentsToDate($userID, $className, $retentionPeriod, $allUser = false) {
       global $DB;
      if (!class_exists($className)) {
          $errorMessage = sprintf(
              __('The class %1$s can\'t be instanciate because not finded on GLPI.', 'rgpdtools'),
              $className
          );
          throw new \Exception($errorMessage);
      }
       $date = new DateTime();
       $date->sub(new DateInterval('P' . $retentionPeriod . 'M'));

       $document = new Document();
       $documentItem = new Document_Item();
       // recherche des éléments liés au user en bdd
       $querySelect = "SELECT d1.* FROM " . $document->getTable() . " d1 "
               . "INNER JOIN " . $documentItem->getTable() . " d2 ON d1.id = d2.documents_id "
               . "WHERE d2.itemtype='" . $className . "' AND d2.date <= '" . $date->format('Y-m-d') . "' ";
      if (!$allUser && $userID) {
         $querySelect .= "AND d2.users_id=$userID ";
      }

       $results = $DB->query($querySelect);
       $nbdeletedElmts = $DB->numrows($results);
      if ($nbdeletedElmts) {
          // construction du tableau des ids
         while ($row = $DB->fetchAssoc($results)) {
             // delete file on server
             $filepath = GLPI_DOC_DIR.'/'.$row['filepath'];
            if (file_exists($filepath)) {
               unlink($filepath);
            }
             // delete Document_Item into database
             $queryDeleteDocumentItem =  "DELETE FROM ".$documentItem->getTable()." WHERE documents_id=".$row['id'];
             $DB->query($queryDeleteDocumentItem);
             // delete Document into database
             $queryDeleteDocument =  "DELETE FROM ".$document->getTable()." WHERE id=".$row['id'];
             $DB->query($queryDeleteDocument);
         }

      }

       return $nbdeletedElmts;
   }


   private static function anonymizeUserLogActivity($userID, $retentionPeriod) {
       global $DB;

       $date = new DateTime();
       $date->sub(new DateInterval('P' . $retentionPeriod . 'M'));

       $log = new Log();
       // delete logs wich user is at origin
       $query = "DELETE FROM " . $log->getTable() . " WHERE user_name LIKE '% (" . $userID . ")' AND date_mod <= '" . $date->format('Y-m-d H:i:s') . "'";
       $DB->query($query);

       // anonymize logs wich are attch to the user
       $query = "DELETE FROM " . $log->getTable() . " WHERE itemtype='User' AND items_id=" . $userID . " AND date_mod <= '" . $date->format('Y-m-d H:i:s') . "'";
       $DB->query($query);

       // anonymize logs containing friendlyname of the user in old_value or new_value
       $user = new User();
       $user->getFromDB($userID);
       $friendlyName = $user->getFriendlyName();
       $query = "UPDATE " . $log->getTable() . " SET old_value='&nbsp; (0)' WHERE old_value LIKE '%" . $friendlyName . "%' AND date_mod <= '" . $date->format('Y-m-d H:i:s') . "'";
       $query = "UPDATE " . $log->getTable() . " SET new_value='&nbsp; (0)' WHERE new_value LIKE '%" . $friendlyName . "%' AND date_mod <= '" . $date->format('Y-m-d H:i:s') . "'";
       $query = "UPDATE " . $log->getTable() . " SET new_value='&nbsp; (0)' WHERE itemtype_link='User' AND new_value LIKE '% (" . $userID . ")' AND date_mod <= '" . $date->format('Y-m-d H:i:s') . "'";
       $query = "UPDATE " . $log->getTable() . " SET new_value='&nbsp; (0)' WHERE itemtype_link='User' AND new_value LIKE '% (" . $userID . ")' AND date_mod <= '" . $date->format('Y-m-d H:i:s') . "'";
   }

   private static function getUserSoftwares($computersIds) {
       global $DB;
       $softwares = [];
      if (count($computersIds)) {
          $query = "SELECT `glpi_softwares`.`name` AS `softname`, `glpi_items_softwareversions`.`id`, `glpi_states`.`name` AS `state`, `glpi_softwareversions`.`id` AS `verid`, `glpi_softwareversions`.`softwares_id`, `glpi_softwareversions`.`name` AS `version`, `glpi_softwares`.`is_valid` AS `softvalid`, `glpi_items_softwareversions`.`date_install` AS `dateinstall`
            FROM `glpi_items_softwareversions`
            LEFT JOIN `glpi_softwareversions` ON (`glpi_items_softwareversions`.`softwareversions_id` = `glpi_softwareversions`.`id`)
            LEFT JOIN `glpi_states` ON (`glpi_softwareversions`.`states_id` = `glpi_states`.`id`)
            LEFT JOIN `glpi_softwares` ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
            WHERE `glpi_items_softwareversions`.`items_id` IN (" . implode(',', $computersIds) . " ) AND `glpi_items_softwareversions`.`itemtype` = 'Computer' AND `glpi_items_softwareversions`.`is_deleted` = '0'

            ORDER BY `softname`, `version`";

          $result = $DB->query($query);
         while ($data = $DB->fetchAssoc($result)) {
            $softwares[] = $data;
         }
      }

       return $softwares;
   }

   private static function getUserSoftwareLicences($computersIds) {
       global $DB;
       $softwaresLicences = [];
      if (count($computersIds)) {
          $query = "SELECT tb.*
            FROM `glpi_items_softwareversions`
            LEFT JOIN `glpi_softwareversions` ON (`glpi_items_softwareversions`.`softwareversions_id` = `glpi_softwareversions`.`id`)
            LEFT JOIN `glpi_states` ON (`glpi_softwareversions`.`states_id` = `glpi_states`.`id`)
            LEFT JOIN `glpi_softwares` ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
            LEFT JOIN `glpi_softwarelicenses` tb ON (tb.`softwares_id` = `glpi_softwares`.`id`)
            WHERE `glpi_items_softwareversions`.`items_id` IN (" . implode(',', $computersIds) . " ) AND `glpi_items_softwareversions`.`itemtype` = 'Computer' AND `glpi_items_softwareversions`.`is_deleted` = '0'

            ORDER BY tb.name";

          $result = $DB->query($query);
         while ($data = $DB->fetchAssoc($result)) {
            $softwaresLicences[] = $data;
         }
      }

       return $softwaresLicences;
   }
}
