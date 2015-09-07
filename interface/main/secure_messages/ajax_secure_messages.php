<?php
/**
 *
 * Ajax functions for Secure Messaging.
 * Copyright (C) 2015 Ensoftek, Inc
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package OpenEMR
 * @author  Ensoftek
 * @link    http://www.open-emr.org
 */

$sanitize_all_escapes=true;
$fake_register_globals=false;

require_once("../../globals.php");
require_once("$srcdir/formatting.inc.php");
require_once("secure_messages.inc");

//var_dump($_REQUEST);
$mode = formData('mode','R');
switch( $mode ) {
	case 'deleteMessage' :
		$messageIds = $_REQUEST['messageIds'];
		$ret = deleteMessage($messageIds);
		echo $ret;
		break;
	
	case 'changeStatus' :
		$messageIds = $_REQUEST['messageIds'];
		$inboxMessages = $_REQUEST['inboxMsgs'];
		$sentMessages = $_REQUEST['sentMsgs'];
		$status = $_REQUEST['status'];
		
		if ( $status == -1)
			$ret = deleteMessage($inboxMessages,$sentMessages);
		else
			$ret = updateMessage($inboxMessages,$status);
		echo $ret;
		break;
	
	case 'displayMessage' :
		$pageRequest = $_REQUEST['pageRequest'];
		$userID = $_REQUEST['userID'];
		$userType = $_REQUEST['userType'];
		$columnNames = $_REQUEST['columnNames'];
		$sortOrder = ( $_REQUEST['sortByOrder'] ) ? $_REQUEST['sortByOrder'] : "DESC";
		$sortByColumn = ( $_REQUEST['sortByColumn'] ) ? $_REQUEST['sortByColumn'] : "message_time";
		$keyWord = $_REQUEST['keyWord'];
		$inFolder = explode(",",$_REQUEST['inFolder']);
		
		// For search move it to function.
		if ( $keyWord ) {
			$content = searchMessages($userID,$userType,$pageRequest,$columnNames,$sortByColumn,$sortOrder,$keyWord,$inFolder);
			/*$content = "<table width='95%' class='oemr_list' cellspacing='0' cellpadding='0'>";
			$content .= displayTableHeader($columnNames,$sortByColumn,$sortOrder);
			foreach ( $inFolder as $pageRequest ) {
				
				//$messagesResultSet = getSecureMessages($userID,$userType,$pageRequest,$sortByColumn,$sortOrder,$keyWord,$inFolder);
				$folderContent = displayMessages(0,$userID,$userType,$pageRequest,$columnNames,$sortByColumn,$sortOrder,$keyWord,$inFolder);
				if ( $folderContent && sizeof($inFolder) > 1) {
					$pageRequest = str_replace("_"," ",$pageRequest);
					$pageRequest = ucfirst($pageRequest);
					$content .= "<tr class='text'><td colspan=5>" . $pageRequest . "</td></tr>";
				}
				$content .= $folderContent;
				while ( $row = sqlFetchArray($messagesResultSet)) {
					$content .= printMessageRow($row,$userID,$userType);
				}
			}*/
		} else {
			$content = displayMessages(1,$userID,$userType,$pageRequest,$columnNames,$sortByColumn,$sortOrder,$keyWord,$inFolder);
		}
		echo $content;
		break;
	
	case 'getAuthRep' :
		$pids = $_REQUEST['pids'];
		$authReps = getAuthorizedReps($pids);
		$authReps = json_encode($authReps);
		echo $authReps;
		break;
}
?>
