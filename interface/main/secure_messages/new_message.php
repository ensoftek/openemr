<?php

/**
 *
 * New Message Screen.
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

//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;

if ( $_SESSION['patient_portal'] ) {
	$ignoreAuth=true;
}

require_once('../../globals.php');
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/patient.inc");
require_once("secure_messages.inc");

//ini_set("display_errors","on");
//var_dump($_REQUEST);
$userID = $_REQUEST['userID'];
$userType = $_REQUEST['userType'];

if ( isset($_POST['mode']) ) {
	$messageBody = $_POST['messageBody'];
	$subject = $_POST['mSubject'];
	$userID = $_POST['userID'];
	$userType = $_POST['userType'];
	$toPids = implode(",",$_POST['form_pids']);
	$toAuthReps = implode(",",$_POST['form_authRep']);
	$toAddress[1] = $_POST['form_user'];
	$toAddress[2] = $_POST['form_pid'];
	$toAddress[3] = $_POST['form_authRep'];
	
	if ( $_POST['mode'] == "insert" ) {
		insertMessage($userID,$userType,$subject,$messageBody,$toAddress);	
		echo "<html><body><script language='JavaScript'>\n";
		echo " var myboss = opener ? opener : parent;\n";
		echo " myboss.location.reload();\n";
		echo " if ( parent.$ ) {\n";
		echo "  if ( parent.$.fancybox ) {\n";
		echo "    parent.$.fancybox.close();\n";
		echo "  } else {\n";
		echo "    parent.$.fn.fancybox.close();\n";
		echo "  }\n";
		echo "}\n";
		echo "</script></body></html>\n";
		exit();
	}
}

// Get the User Name
$fromNameArray = getMessageUserName($userID,$userType);	
$fromName = $fromNameArray[0];

// get all users
$userInfo = getProviderInfo();
foreach ( $userInfo as $user ) {
	$ures[$user['id']] = $user['fname'] . " " . $user['lname'];
}

if ( $userType == 1 ) {
	$pidResults = getPatientIds("pid,fname,lname","fname asc");
	foreach ( $pidResults as $row ) {
		$pres[$row['pid']] = $row['fname'] . " " . $row['lname'];
	}
} else if ( $userType == 2) {
	$pres[$userID] = getPatientName($userID);
} else {
	// Get the patient for this userID
	$query = "SELECT pid FROM patient_contact_portal_data WHERE id = '$userID'";
	$rs = sqlQuery($query);
	$pres[$rs['pid']] = getPatientName($rs['pid']);
}

// If logged in patient portal, then populate the authrep dropdown based on the patient ( as there is only one patient)
if ( $userType == 2 || $userType == 3 ) {
	// Get the authRep for patients.
	$patients = implode(",",array_keys($pres));
	$authReps = getAuthorizedReps($patients);	
}

$messageId = ( $_REQUEST['message_id'] ) ? $_REQUEST['message_id'] : "";
if ( $messageId ) {
	$messageDetails = getMessageDetails($messageId);
	$mSubject = $messageDetails[0]['subject'];
	if ( substr_compare($mSubject, "Re:", 0,2) != 0 ) {
		$mSubject = "Re:" . $mSubject;
	}
	$mBody = $messageDetails[0]['body'];
	$recipientsCount = 0;
	foreach ( $messageDetails as $messageDetail ) {
		$recipients[$messageDetail['to_type']] = explode(",",$messageDetail['toAddresses']);
		$recipientsList[$messageDetail['to_type']] = $messageDetail['toAddresses'];
		$recipientsCount += $messageDetail['addressCount'];
	}
	
	// Add the original from address to recipient List. (on reply)
	$recipientsList[$messageDetails[0]['from_type']] = ( $recipientsList[$messageDetails[0]['from_type']] == null ) ? $messageDetails[0]['from_id'] : $recipientsList[$messageDetails[0]['from_type']] . "," . $messageDetails[0]['from_id'] ;
	$recipients[$messageDetails[0]['from_type']][] = $messageDetails[0]['from_id'];
	
	//var_dump($messageDetails,$recipientsList,$recipients[2]);
	if ( $recipientsList[3] ) {
		// Get the authReps in the message if any
		$queryString = "SELECT id,contact_name FROM patient_contact_portal_data WHERE id IN (" . $recipientsList[3] . ")";
		$rs = sqlStatement($queryString);
		while( $row = sqlFetchArray($rs) ) {
			$authRepsSelected[$row['id']] = $row['contact_name'];
		}
	}

	if ( $recipientsList[2] ) {
		// Get the authRep for all patients selected.
		$queryString = "SELECT id,contact_name FROM patient_contact_portal_data WHERE pid IN (" . $recipientsList[2] . ")";
		$rs = sqlStatement($queryString);
		while( $row = sqlFetchArray($rs) ) {
			$authReps[$row['id']] = $row['contact_name'];
		}
	}

	// Remove the current message "FROM ID" from the receipient list unless the sender is himself.
	if ( $userType != $messageDetails[0]['from_type'] || $userID != $messageDetails[0]['from_id'] ) {
		$found = array_search($userID,$recipients[$userType]);
		if ( $found !== false)
			unset($recipients[$userType][$found]);
	}
}

?>

<html>
<head>

<?php html_header_show();?>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>
<link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/jquery-ui-1.8.21.custom.css" type="text/css" />
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui-1.8.6.custom.min.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.multiselect/jquery.multiselect.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.multiselect/jquery.multiselect.filter.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.multiselect/jquery-ui.css" />
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.multiselect/jquery.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.multiselect/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.multiselect/jquery.multiselect.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.multiselect/jquery.multiselect.filter.js"></script>

<script type="text/javascript">
	$(document).ready(function(){
		$("#form_pid").multiselect().multiselectfilter();
		$("#form_pid").multiselect({  
			noneSelectedText: "Select Patients",	
			click: function(event, ui) {
				populateAuthRep(ui.checked,ui.value,ui.text);
				if ( ui.checked )
					addAddress(2,ui.value,ui.text); // 2 -- indicates pid
				else
					deleteToAddress("2i" + ui.value);
			},
			checkAll: function(event,ui) {
				var allPids = $("#form_pid").val();
				if ( allPids ) {
					for( var i = 0; i < allPids.length; i++) {
						var pidName = $("#form_pid").find("option[value='" + allPids[i] + "']").text();
						addAddress(2,allPids[i],pidName);
						populateAuthRep(true,allPids[i],pidName);
					}
				}
			},
			uncheckAll: function(event,ui) {
				var allPids = $('#form_pid option').map(function() {
					return $(this).val();
				});
				deleteAllToAddress(2,allPids);
			}
		});

		$("#form_authRep").multiselect().multiselectfilter();
		$("#form_authRep").multiselect({ 
			noneSelectedText: "Select Authorized Representative",
			click: function(event, ui) {
				if ( ui.checked )
					addAddress(3,ui.value,ui.text); // 3 -- indicates authRep
				else
					deleteToAddress("3i" + ui.value);
			},
			checkAll: function(event,ui) {
				var allAuthReps = $("#form_authRep").val();
				if ( allAuthReps ) {
					for( var i = 0; i < allAuthReps.length; i++) {
						var authRepName = $("#form_authRep").find("option[value='" + allAuthReps[i] + "']").text();
						addAddress(3,allAuthReps[i],authRepName);
					}
				}
			},
			uncheckAll: function(event,ui) {
				var allAuthReps = $('#form_authRep option').map(function() {
					return $(this).val();
				});
				deleteAllToAddress(3,allAuthReps);
			}
		});

		$("#form_user").multiselect().multiselectfilter();
		$("#form_user").multiselect({ 
			noneSelectedText: "Select Provider",
			click: function(event, ui) {
				if ( ui.checked )
					addAddress(1,ui.value,ui.text); // 1 -- indicates user
				else
					deleteToAddress("1i" + ui.value);
			},
			checkAll: function(event,ui) {
				var allProviders = $("#form_user").val();
				if ( allProviders ) {
					for( var i = 0; i < allProviders.length; i++) {
						var providerName = $("#form_user").find("option[value='" + allProviders[i] + "']").text();
						addAddress(1,allProviders[i],providerName);
					}
				}
			},
			uncheckAll: function(event,ui) {
				var allProviders = $('#form_user option').map(function() {
					return $(this).val();
				});
				deleteAllToAddress(1,allProviders);
			}	
		});

		$('#mSubject').keyup(function() {
			var textLength = $(this).val().length;
			$("#spanSubject").html("Remaining characters : " +(120 - textLength));
		});
		
		// For all selected in authRep and patient dropdown add it to the to list.
		$("#form_pid option:selected").each(function() {
			addAddress(2,$(this).val(),$(this).text());
		});
		$("#form_authRep option:selected").each(function() {
			addAddress(3,$(this).val(),$(this).text());
		});
		$("#form_user option:selected").each(function() {
			addAddress(1,$(this).val(),$(this).text());
		});
	});
	
	function deleteAllToAddress(group,ids) {
		for ( var i = 0 ; i < ids.length ;i++)
			deleteToAddress(group + "i" + ids[i]);
	}

	function populateAuthRep(isChecked,checkValue,checkText) {
		$.ajax({
			url: "ajax_secure_messages.php?mode=getAuthRep&pids="+checkValue,
			context: document.body,
			success: function(data) {
				if ( data ) {
					var values = JSON.parse(data);
					for ( var authID in values ) {
						var optionValue = authID;
						var optionText = values[authID];
						if ( isChecked ) {
							if ( $("#form_authRep").find("option[value=" + optionValue + "]").length <= 0 )
								$("#form_authRep").append($('<option></option>').val(optionValue).html(optionText));
						}
					}
					$("#form_authRep").multiselect("refresh");
				}
			}
		});
	}
	 
	function onMouseOverToAddress(toAddressID) {
		//alert(toAddressID);
		var toID = toAddressID.substr(2);
		getDeleteLinkContent(toID);		
	}
	
	function onMouseOutToAddress(toAddressID) {
		var toID = toAddressID.substr(2);
		$("#div" + toID).hide();		
	}
	
	function addAddress(userType,userIDs,userNames) {
		//alert(userIDs);
		//alert(userType);
		var userIDArray = userIDs.split(",");
		var userNameArray = userNames.split(":");
		for( var i = 0 ; i < userIDArray.length ; i++) {
			var elementID = userType + "i" + userIDArray[i];
			var userName = userNameArray[i];
			//alert(elementID);
			if ( $("#to" + elementID).length ) {
				// To address already exists
			} else {
				var content = "<div id='to" + elementID + "' name='to" + elementID + "'><span>" + userName + "</span><div onclick=deleteToAddress('" + elementID + "'); class=notification-bubble style='display:inline' id=div" + elementID + " name=div" + elementID + ">x</div></div>&nbsp;";
				$("#toAddresses").append(content);
				$("#to" + elementID).addClass('toAddress');
			}
		}
	}
	
	function getDeleteLinkContent(toID) {
		//alert(toID);
		var content = "x";
		$("#div" + toID).html(content);
		$("#div" + toID).show();
	}
	
	function deleteToAddress(toID) {
		//alert(toID); 
		var userType = toID.split("i");
		if ( userType[0] == 3 ) { // Uncheck the authorized rep if that is deleted
			$("#form_authRep").find("option[value='" + userType[1] + "']").attr("selected",false);
			$("#form_authRep").multiselect("refresh");
		} else if ( userType[0] == 2 ) { // Deleting patient
			$("#form_pid").find("option[value='" + userType[1] + "']").attr("selected",false);
			$("#form_pid").multiselect("refresh");			
		} else if ( userType[0] == 1 ) { // Deleting user
			$("#form_user").find("option[value='" + userType[1] + "']").attr("selected",false);
			$("#form_user").multiselect("refresh");			
		}
		$("#to" + toID).remove();
		$('#toAddresses').html($('#toAddresses').html().replace("&nbsp;&nbsp;","&nbsp;"));
	}
	
	function closeMe(refresh) {
		if ( refresh ) {
			var myboss = opener ? opener : parent;
			myboss.location.reload();
		}
		if ( parent.$ ) {
			if ( parent.$.fancybox ) {
				parent.$.fancybox.close();
			} else {
				parent.$.fn.fancybox.close();
			}
		}
	}
	
	function sendMessage() {
		if ( !validate() )
			return false;
		
		var allIDs = "";
		$("div[id^='to']").each(function() {
			allIDs += this.id + ':';
		});
		allIDs = allIDs.replace(/:$/,'');
		if ( allIDs == "" ) {
			alert("<?php echo 'Please select atleast one To address'; ?>");
			return false;
		}
		$("#mode").val('insert');
		$("#thisForm").submit();
	}
	
	function validate() {
		var message = $("#mBody").val();
		$("#messageBody").val(message);
		if ( message == "" ) {
			alert("<?php echo 'Please enter message'; ?>");
			$("#mBody").focus();
			return false;
		} else if ( $("#mSubject").val() == "" ) {
			alert("<?php echo 'Please enter subject'; ?>");
			$("#mSubject").focus();
			return false;
		}
		return true;
	}

</script>

<style type="text/css">
	.toAddress {
		font-family: sans-serif;
		padding : 4px 4px 4px 4px;
		margin : 0 0 3 0;
		font-size: 10pt;
		font-weight: bold;
		border-radius: 4px;
		text-align:center;
		background-color: #CCC;
		display: inline-block;
	}
	
	.notification-bubble {
		position: relative;
		top : -10px;
		right : 0px;
	}
</style>

</head>

<body>
<form method='post' id="thisForm" name="thisForm" action="new_message.php">

<table>
	<tr>
		<td class="text"><span><?php echo xlt("From"); ?></span></td>
		<td class="text_bold"><b><?php echo $fromName; ?></b></td>
	</tr>
	
	<tr>
		<td class="text"><span><?php echo xlt("Providers"); ?></span></td>
		<td class="text">
			<div id="patients_div">
				<select name='form_user[]' style='width:390px' id='form_user' multiple='multiple' title='Click to Select a <?php echo xl("Provider");?>' >
					<?php  foreach ($ures as $key => $urow) { 
						$selected = in_array($key,$recipients[1]) ? "selected" : ""; ?>
						<option value="<?php echo $key; ?>" <?php echo $selected ?>><?php echo $urow . "(" . $key . ")"; ?></option>
					<?php } ?>
				</select>
			</div>
		</td>
	</tr>	
	
	<tr>
		<td class="text"><span><?php echo xlt("Patients"); ?></span></td>
		<td class="text">
			<div id="patients_div">
				<select name='form_pid[]' style='width:390px' id='form_pid' multiple='multiple' title='Click to Select a <?php echo xl("Patient");?>' >
					<?php
						foreach ($pres as $key => $prow) { 
							$selected = in_array($key,$recipients[2]) ? "selected" : ""; ?>
						<option value="<?php echo $key; ?>" <?php echo $selected ?>><?php echo $prow . "(" . $key . ")"; ?></option>
					<?php } ?>
				</select>
			</div>
		</td>
	</tr>
	
	<tr>
		<td class="text"><span><?php echo xlt("Authorized Representatives"); ?></span></td>
		<td class="text">
			<div id="authRep_div">
				<select name='form_authRep[]' style='width:390px' id='form_authRep' multiple='multiple' title='Click to Select a authorized representative' >
					<?php if ( $authReps ) { 
						foreach ( $authReps as $id => $name) {
							$selected = ( $authRepsSelected[$id] ) ? "selected" : ""; ?>
							<option value="<?php echo $id; ?>" <?php echo $selected; ?> ><?php echo $name . "(" . $id . ")"; ?></option>
					<?php }	} ?>
				</select>
			</div>
		</td>
	</tr>
	
	<tr>
		<td class='text'><?php echo xlt("To"); ?></td>
		<td align="left" id="toAddresses" name="toAddresses">
		</td>
	</tr>
	
	<tr>
		<td class="text"><span><?php echo xlt("Subject"); ?></span></td>
		<td>
			<input type="text" id="mSubject" name="mSubject" title='<?php echo xlt("Enter Subject"); ?>' size="52" maxlength="120" value="<?php echo attr($mSubject);?>"/>
			<span class="text" id="spanSubject"></span>
		</td>
	</tr>
	
	<tr>
		<td class="text"><span><?php echo xlt("Message"); ?></span></td>
		<td>
			<textarea name='mBody' id='mBody' cols='40' rows='12' style='width:390px;'><?php echo attr($mBody);?></textarea>
		</td>
	</tr>
	
	<tr><td><br></td></tr>
	
	<tr>
		<td align="center" colspan="2">
			<table>
				<tr><td>
					<a href='#' class='css_button' onclick='sendMessage();'> <span><?php xl('Send','e'); ?> </span></a>
					<a href='#' class='css_button' onclick='closeMe();'> <span><?php xl('Cancel','e'); ?> </span></a>
				</td></tr>
			</table>
		</td>
	</tr>
	
</table>

<input type='hidden' id='mode' name='mode' value=''/>
<input type="hidden" id="messageID" name="messageID" value="<?php echo $messageId; ?>"/>
<input type="hidden" id="messageBody" name="messageBody" value=""/>
<input type="hidden" id="userType" name="userType" value='<?php echo $userType; ?>'/>
<input type="hidden" id="userID" name="userID" value='<?php echo $userID; ?>'/>

</form>
</body>
</html>