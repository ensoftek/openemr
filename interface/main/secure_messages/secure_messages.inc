<?php

/**
 *
 * Functions for Secure Messaging.
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

global $totalMessagesPerPage;
$totalMessagesPerPage = 100;
		
function getSecureMessages($userID,$userType,$page_request = "ALL",$orderBy="message_time",$sortOrder="desc",$search="%",$start=0) {
	global $totalMessagesPerPage;
	if ( $page_request == "inbox" ) {
		$subQuery = " smd.to_id = $userID AND smd.to_type = $userType AND smd.deleted = 0 ";
		$joinColumn = "sm.from_id";
		$whereColumn = "sm.from_type";
		$groupBy = "";
		$groupByColumn = "";
		$selectColumns = ",smd.is_read,smd.message_details_id AS id";
	} else if ( $page_request == "sent_items") {
		$subQuery = " sm.from_id = $userID AND sm.from_type = $userType AND sm.archive = 0 ";
		$joinColumn = "smd.to_id";
		$whereColumn = "smd.to_type";
		$selectColumns = "";
		$groupBy = " GROUP BY message_id ";
		$groupByColumn = " ,GROUP_CONCAT(name SEPARATOR ', ') AS name";
	} else {
		$subQuery = " ( smd.to_id = $userID AND smd.to_type = $userType ) OR ( sm.from_id = $userID AND sm.from_type = $userType )";
	}

	$searchQuery = ( $search && $search != "%" ) ? " AND sm.subject LIKE '%$search%'" : "";
	$startCount = $start;
	$limitQuery = " LIMIT " . $startCount . " , " . $totalMessagesPerPage;
	$commonColumns = "sm.subject,sm.body,sm.message_time,sm.message_id" . $selectColumns;
	
	$userQueryString = "SELECT $commonColumns,CONCAT(u.fname,' ',u.lname) AS name FROM secure_messages sm INNER JOIN secure_message_details smd ON sm.message_id = smd.message_id
		INNER JOIN users u ON " . $joinColumn . " = u.id WHERE $whereColumn = 1 AND " . $subQuery . $searchQuery; 
	
	$patientQueryString = "SELECT $commonColumns,CONCAT(pd.fname,' ',pd.lname) AS name FROM secure_messages sm INNER JOIN secure_message_details smd ON sm.message_id = smd.message_id
		INNER JOIN patient_data pd ON " . $joinColumn . " = pd.pid WHERE $whereColumn = 2 AND " . $subQuery . $searchQuery;
	
	$authorizedRepQueryString = "SELECT $commonColumns,pcpd.contact_name AS name FROM secure_messages sm INNER JOIN secure_message_details smd ON sm.message_id = smd.message_id
		INNER JOIN patient_contact_portal_data pcpd ON " . $joinColumn . " = pcpd.id WHERE $whereColumn = 3 AND " . $subQuery . $searchQuery;

	$finalQuery = "SELECT SQL_CALC_FOUND_ROWS * $groupByColumn FROM ( (" . $userQueryString . ") UNION ( " . $patientQueryString . ") UNION ( " . $authorizedRepQueryString . ") )
		AS finalTable $groupBy ORDER BY $orderBy $sortOrder $limitQuery";
	
	//echo $finalQuery . "<br>";
	$resultSet = sqlStatement($finalQuery);
	$query = "select FOUND_ROWS() AS rowCount";
	$rs = sqlQuery($query);
	return array($resultSet,$rs['rowCount']);
}

function printMessageRow($row,$userID,$userType) {
	$qTipURL = "view_message.php?userID=" . $userID . "&userType=" . $userType . "&preview=1&message_id=" . $row['message_id'];
	$unReadClass = ( isset($row['is_read']) && $row['is_read'] == 0 ) ? "unReadMessages" : "";
	$row['id'] = ( $row['id'] ) ? $row['id'] : 0;
	$content = "<tr id=" . $row['id'] . " class='messageRow $unReadClass' onclick=viewMessage(" . $row['message_id'] . "," . $row['id']. ")>";
	$content .= "<td class=deleteColumn align=center onclick='event.cancelBubble=true'>";
	if ( isset($row['is_read']) )
		$content .= "<input type=checkbox value=i" . $row['id'] . " id=" . $row['id'] . " name=" . $row['id'];
	else
		$content .= "<input type=checkbox value=s" . $row['message_id'] . " id=" . $row['message_id'] . " name=" . $row['message_id'];
	
	$message_time = $row['message_time'];
	$content .= " </td>";
	
	// Format the name
	$nameContent = ( strlen($row['name']) > 45 ) ? substr($row['name'],0,45) . "..." : $row['name'];
	$content .= "<td class=text>" . $nameContent . "</td>";
	// Format subject
	$subjectContent = ( strlen($row['subject']) > 50 ) ? substr($row['subject'],0,50) . "..." : $row['subject'];
	$content .= "<td class='subjectColumn text' title='" . $qTipURL . "'>" . $subjectContent . "</td>";
	
	$content .= "<td class=text>" . oeFormatShortDate(substr($message_time,0,10)) . " " . oeFormatTime(substr($message_time,11)) . "</td>";
	$content .= "</tr>";
	return $content;
}

function deleteMessage($inboxMessages,$sentMessages) {
	$modifiedTime = date("Y-m-d H:i:s");
	
	if ( $inboxMessages ) {
		$queryString = "UPDATE secure_message_details SET deleted = 1, modified_time = '$modifiedTime' WHERE message_details_id IN ( " . $inboxMessages . ")";
		$rs = sqlStatement($queryString);
	}
	if ( $rs === FALSE)
		return "FAILURE";
	
	if ( $sentMessages ) {
		$queryString = "UPDATE secure_messages SET archive = 1 WHERE message_id IN ( " . $sentMessages . ")";
		$rs = sqlStatement($queryString);
	}
	
	if ( $rs === FALSE)
		return "FAILURE";
	else 
		return "SUCCESS";
}

function updateMessage($messageIDs,$status) {
	$modifiedTime = date("Y-m-d H:i:s");
	$queryString = "UPDATE secure_message_details SET is_read = $status, modified_time = ? WHERE message_details_id IN ( " . $messageIDs . ")";
	$rs = sqlStatement($queryString,array($modifiedTime));
	if ( $rs === FALSE)
		return "FAILURE";
	else 
		return "SUCCESS";
}

function displayPagination($start,$rowCount,$pageRequest,$userType,$userID,$sortByColumn,$sortByOrder,$keyWord,$inFolder) {
	global $totalMessagesPerPage;
	if ( $rowCount <= $totalMessagesPerPage )
		return;
	
	$params = "&page_request=" . $pageRequest . "&userType=" . $userType . "&userID=" . $userID . "&sort_column=" .$sortByColumn . "&sort_order=" . $sortByOrder . "&searchbox=" . $keyWord . "&inFolder=" . $inFolder;
	
	echo '<div id="paginationDiv" name="paginationDiv" align="right" style="width:95%">';
	simplePagination($start,$totalMessagesPerPage,$rowCount,"messages.php",$params);
	echo '</div>';
}

function displayMessages($header,$userID,$userType,$pageRequest,$columnNames,$sortByColumn,$sortOrder,$keyWord,$inFolder) {
	global $totalMessagesPerPage;
	if ( $keyWord && $inFolder && is_array($inFolder))
		$inFolder = implode(",",$inFolder);
		
	// display Messages.
	if ( $keyWord == "" )
		list($messagesResultSet,$rowCount) = getSecureMessages($userID,$userType,$pageRequest,$sortByColumn,$sortOrder);
	else
		list($messagesResultSet,$rowCount) = getSecureMessages($userID,$userType,$pageRequest,$sortByColumn,$sortOrder,$keyWord);
	
	if ( $header ) {
		$content = "<table width='95%' class='messageTable' cellspacing='0' cellpadding='0'>";
		$content .= displayTableHeader($columnNames,$sortByColumn,$sortOrder);
	}
	
	echo $content;
	if ( trim($keyWord) == "")
		displayPagination(0, $rowCount, $pageRequest, $userType, $userID, $sortByColumn, $sortOrder, $keyWord,$inFolder);
	$content = "";
	while ( $row = sqlFetchArray($messagesResultSet)) {
		$content .= printMessageRow($row,$userID,$userType);
	}
	return $content;
}

function displayTableHeader($columnNames,$sortByColumn,$sortOrder) {
	$columnNameArray = explode(",",$columnNames);
	$content .= "<tr class='text'>";
	$content .= "<th width='2%'><input type=checkbox onclick='javascript:checkAllMessages();' id='selectAll' name='selectAll'/></th>";
	$content .= "<th width='20%' align='left'><a title='" . $columnNameArray[0] . "' href=javascript:sortByColumn('name','" . $sortOrder . "')>" . xl($columnNameArray[0]) ."</a></th>";
	$content .= "<th width='35%' align='left'><a title='" . $columnNameArray[1] . "' href=javascript:sortByColumn('subject','" . $sortOrder . "')>" . xl($columnNameArray[1]) . "</a></th>";
	$content .= "<th width='10%' align='left'><a title='" . $columnNameArray[2] . "' href=javascript:sortByColumn('message_time','" . $sortOrder . "')>" . xl($columnNameArray[2]) . "</a></th>";
	$content .= "</tr>";
	return $content;
}

function getAuthorizedReps($pids,$patPortal=true) {
	$queryString = "SELECT id,contact_name FROM patient_contact_portal_data WHERE pid IN (" . $pids . ") AND portal_id IS NOT NULL";
	$rs = sqlStatement($queryString);
	while($row = sqlFetchArray($rs) ) {
		$authReps[$row['id']] = $row['contact_name'];
	}

	return $authReps;
}

function insertMessage($userID=null,$userType=null,$subject,$messageBody,$toAddress) {
	if ( $userID == null || $userType == null ) {
		return false;
	}
	
	$messageTime = date("Y-m-d H:i:s");
	$messageHash = sha1($messageBody.$subject); // Create a hash
	$queryString = "INSERT INTO secure_messages SET
		from_id = ?,
		from_type = ?,
		subject = ?,
		body = ?,
		message_time = ?,
		message_hash = ?";
	$queryBindArray = array($userID,$userType,$subject,$messageBody,$messageTime,$messageHash);
	//var_dump($queryString,$queryBindArray);
	$messageID = sqlInsert($queryString,$queryBindArray);
	

	// Insert into secure_message_details
	foreach ( $toAddress as $toType => $toIDs ) {
		for ( $i = 0 ; $i < sizeof($toIDs) ; $i++ ) { 
			$queryString = "INSERT INTO secure_message_details SET 
				message_id = ?,
				to_id = ?,
				to_type = ?";
			$queryBindArray = array($messageID,$toIDs[$i],$toType);
			//var_dump($queryString,$queryBindArray);
			sqlInsert($queryString,$queryBindArray);
		}
	}
}

function getMessageDetails($messageID = NULL) {
	if ( $messageID == NULL)
		return NULL;
	
	$queryString = "SELECT *,GROUP_CONCAT(to_id SEPARATOR ',') AS toAddresses,COUNT(*) AS addressCount FROM secure_messages sm
		INNER JOIN secure_message_details smd ON sm.message_id = smd.message_id 
		WHERE sm.message_id = ? GROUP BY to_type";
	$messagesResultSet = sqlStatement($queryString,array($messageID));
	while( $row = sqlFetchArray($messagesResultSet) ) {
		$messageDetails[] = $row;
	}
	//var_dump($queryString,$messageDetails);
	return $messageDetails;
}

function getMessageUserName($userID,$userType) {
	if ( !$userID || !$userType ) 
		return "";
	
	$queryString = "";
	switch($userType) {
		case 1 : 
			$queryString = "SELECT CONCAT(fname,' ',lname) AS name FROM users WHERE id IN ($userID)";
			break;
		
		case 2 :
			$queryString = "SELECT CONCAT(fname,' ',lname) AS name FROM patient_data WHERE pid IN ($userID)";
			break;
		
		case 3 :
			$queryString = "SELECT contact_name AS name FROM patient_contact_portal_data WHERE id IN ($userID)";
			break;
	}
	
	if ( $queryString == "" )
		return ""; // Invalid usertype
	
	$resultSet = sqlStatement($queryString);
	while ( $row = sqlFetchArray($resultSet)) {
		$names[] = $row['name'];
	}
	return $names;
} 

function searchMessages($userID,$userType,$pageRequest,$columnNames,$sortByColumn,$sortOrder,$keyWord,$inFolder) {
	$content = "<table width='95%' class='messageTable' cellspacing='0' cellpadding='0'>";
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
	}
	return $content;
}
?>
