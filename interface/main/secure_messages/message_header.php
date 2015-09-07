<?php

/**
 *
 * Header for Secure Messaging.
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

?>

<script type="text/javascript">
function checkBoxes() {
	var totalMessages = 0;
	var messageIDs = "";
	
	$("#thisform input:checkbox:checked").each(function() {
		if ( $(this).attr("id") != "selectAll" ) {
			messageIDs += $(this).val() + ",";
			totalMessages++;
		}
	});
	
	if (totalMessages == 0) {
		alert("<?php echo htmlspecialchars( xl('Please select atleast one message'), ENT_QUOTES) ?>");
		return false;
	}
	messageIDs = messageIDs.replace(/,$/,'');
	return messageIDs;
}

function checkAllMessages() {

	var checked =  ( $("#selectAll").attr("checked") ) ? true : false;
	$("#thisform input:checkbox").each(function() {
		$(this).attr("checked", checked);
	});
}

function changeStatus(detailMessageID) {
	var messageIDs;
	var markValue;
	var inboxMessages = "";
	var sentMessages = "";
	
	if ( detailMessageID ) {
		$("#messageIDs").val(detailMessageID);
		inboxMessages = detailMessageID.toString();
		markValue = "1";
	} else { 
		messageIDs = checkBoxes();
		if ( messageIDs === false ) {
			$("#selMark").val('');
			return false;
		}
		markValue = $("#selMark").val();
		$("#messageIDs").val(messageIDs);
	}
	
	if ( messageIDs ) {
		// Differentiate into inbox and sent items array
		var mIDs = messageIDs.split(",");
		$.each(mIDs,function(i,s){  
			var c = s.charAt(0);
			if ( c == 'i' )
				inboxMessages += s.substring(1) + ",";
			else
				sentMessages += s.substring(1) + ",";
		});
		inboxMessages = inboxMessages.replace(/,$/,'');
		sentMessages = sentMessages.replace(/,$/,'');
	}

	$.ajax({
		url: "ajax_secure_messages.php?mode=changeStatus&inboxMsgs="+inboxMessages+"&sentMsgs="+sentMessages+"&status="+markValue,
		context: document.body,
		success: function(data) {
			if ( data == "SUCCESS" ) {
				//displayMessages();
				changeMessageStatus(inboxMessages,sentMessages,markValue);
				$("#selMark").val('');
				$("#selectAll").attr("checked",false);
			} else {
				alert("<?php echo htmlspecialchars(xl('Unable to change the status')); ?>");
				return false;
			}
		}
	});
}

function changeMessageStatus(inboxMessages,sentMessages,markValue) {
	var iMessageArray,sMessageArray;
	
	if ( inboxMessages.length != 0 ) {
		iMessageArray = inboxMessages.split(",");
		for( var i = 0 ; i < iMessageArray.length ; i++)
			markMessage(iMessageArray[i],markValue);
	}
	
	if ( sentMessages.length != 0 ) {
		sMessageArray = sentMessages.split(",");
		if ( markValue == -1 ) {
			for( var i = 0 ; i < sMessageArray.length ; i++)
				markMessage(sMessageArray[i],markValue);
		} else {
			for( var i = 0 ; i < sMessageArray.length ; i++)
				$("input[name=" + sMessageArray[i] + "]").attr("checked",false);
		}
	}
}

function markMessage(messageId,markValue) {
	if ( $("#" + messageId).length ) {
		switch(markValue) {
			case "1" :
				$("#" + messageId).removeClass("unReadMessages");
				$("input[name=" + messageId + "]").attr("checked",false);
				break;
			case "0" :
				$("#" + messageId).addClass("unReadMessages");
				$("input[name=" + messageId + "]").attr("checked",false);
				break;
			case "-1" :
				$('input:checkbox[id=' + messageId + ']').parent().parent().remove();
				break;
		}
	}
}

function deleteMessage() {
	var messageIDs = checkBoxes();
	if ( messageIDs === false ) 
		return false;
		
	$("#messageIDs").val(messageIDs);
	$.ajax({
		url: "ajax_secure_messages.php?mode=deleteMessage&messageIds="+$('#messageIDs').val(),
		context: document.body,
		success: function(data) {
			if ( data == "SUCCESS" ) {
				displayMessages();
			} else {
				alert("<?php echo htmlspecialchars(xl('Not able to delete messages')); ?>");
				return false;
			}
		}
	});
}

function displayMessages() {
	var keyWord = $("#searchbox").val();
	var inFolder = $("#selSearchFolder").val();
	$.ajax({
		url: "ajax_secure_messages.php?mode=displayMessage&keyWord="+keyWord+"&inFolder="+inFolder+"&sortByColumn="+$("#sort_column").val()+"&sortByOrder="+$("#sort_order").val()+"&columnNames="+$("#columnNames").val()+"&pageRequest="+$('#page_request').val()+"&userID="+$("#userID").val()+"&userType="+$("#userType").val(),
		context: document.body,
		success: function(data) {
			//alert(data);
			$("#messageDiv").html(data);
		}
	});	
}

function newMessage(messageID) {
	var fbox = document.getElementById("newMessage");
	fbox.href = "new_message.php?userID="+$("#userID").val()+"&userType="+$("#userType").val()+"&message_id="+messageID,'_blank';
	$('#newMessage').trigger('click');
}

function viewMessage(messageID,detailID) {
	
	// Mark the message as read if in Inbox
	if ( detailID )
		changeStatus(detailID);
	
	var fbox = document.getElementById("newMessage");
	fbox.href = "view_message.php?userID="+$("#userID").val()+"&userType="+$("#userType").val()+"&message_id="+messageID,'_blank';
	$('#newMessage').trigger('click');
}

function sortByColumn(columnName,sortOrder) {
	var newSortOrder;
	
	if ( columnName === $("#sort_column").val())
		newSortOrder = ( sortOrder == 'DESC') ? 'ASC' : 'DESC';
	else
		newSortOrder = "DESC";
	
	$("#sort_column").val(columnName);	
	$("#sort_order").val(newSortOrder);

	//displayMessages();
	$("#thisform").submit();
}

function searchMessage() {
	var keyWord = $("#searchbox").val();
	if ( $.trim(keyWord) == "" ) {
		alert("<?php echo 'Please enter a keyword to search'; ?>");
		return false;
	}
	
	var inFolder = $("#selSearchFolder").val();
	$("#thisform").submit();

	/*$.ajax({
		url: "ajax_secure_messages.php?mode=displayMessage&keyWord="+keyWord+"&inFolder="+inFolder+"&columnNames="+$("#columnNames").val()+"&pageRequest="+"&userID="+$("#userID").val()+"&userType="+$("#userType").val(),
		context: document.body,
		success: function(data) {
			//alert(data);
			$("#messageDiv").html(data);
		}
	});*/
}

</script>

<br>
<div style="display:none">
  <a id="newMessage" class="iframe message_iframe"></a>
</div>

<table border="0" style="border:1px solid;background-color: lightgray;width:95%">
	<tr>
		<td width="100px"><a href='#' class='iframe css_button'  onclick='newMessage();'><span><?php xl('New Message','e'); ?></span></a></td>
		
		<td width="150px"><span class="text"><?php echo xl("Mark As"); ?></span>
			<select id="selMark" title='<?php echo xl("Select Mark As"); ?>' name='selMark' onchange='changeStatus();'>
				<option></option>
				<?php if ( $pageRequest == "inbox") { ?>
					<option value="1"><?php echo xl("Read"); ?></option>
					<option value="0"><?php echo xl("Unread"); ?></option>
				<?php } ?>
				<option value='-1'><?php echo xl("Delete"); ?></option>
			</select>
		</td>
		<td width="325px">&nbsp;<span class="text"><?php echo xl("Subject Search"); ?></span>
		<input title="In Subject" type="textbox" id="searchbox" name="searchbox" maxlength="20" size="10" value="<?php echo $keyWord;?>"/>
		<span class="text"><?php echo xl("in"); ?></span>
			<select id="selSearchFolder" name='selSearchFolder'>
				<option value="inbox,sent_items" <?php echo ( $_POST['selSearchFolder'] == 'inbox,sent_items' )? "selected" : ""; ?>><?php echo xl("All");?></option>
				<option value="inbox" <?php echo ( $_POST['selSearchFolder'] == 'inbox' )? "selected" : ""; ?>><?php echo xl("Inbox"); ?></option>
				<option value="sent_items" <?php echo ( $_POST['selSearchFolder'] == 'sent_items' )? "selected" : ""; ?>><?php echo xl("Sent Items"); ?></option>
			</select>
		</td>
		<td><a href='#' class='css_button'  onclick='searchMessage()'> <span><?php xl('Search','e'); ?> </span></a></td>
	</tr>
</table>

<br>