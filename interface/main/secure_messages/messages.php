<?php

/**
 *
 * Inbox/Sent Items for Secure Messaging.
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

require_once('../../globals.php');
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/options.inc.php");
require_once("secure_messages.inc");

$pageRequest = ( $_REQUEST['page_request'] ) ? $_REQUEST['page_request'] : $_POST['page_request'];
if ( !empty($_POST) ) {
	$sortByOrder = ( $_POST['sort_order'] ) ? $_POST['sort_order'] : "DESC";
	$sortByColumn = ( $_POST['sort_column'] ) ? $_POST['sort_column'] : "message_time";
	$keyWord = ( $_POST['searchbox'] ) ? $_POST['searchbox'] : NULL;
	$inFolder = ( $keyWord ) ? $_POST['selSearchFolder'] : "";
	$start = ( $_POST['start'] ) ? $_POST['start'] : 0;
} else if ( isset($_REQUEST['start']) ) {
	$sortByColumn = $_REQUEST['sort_column'];
	$sortByOrder = $_REQUEST['sort_order'];
	$keyWord = $_REQUEST['searchbox'];
	$inFolder = ( $keyWord ) ? $_REQUEST['inFolder'] : "";
	$start = $_REQUEST['start'];
} else {
	$sortByColumn = "message_time";
	$sortByOrder = "DESC";
	$start = 0;
}

if ( $pageRequest == "inbox") {
	$columnNames = array("From","Subject","Send/Received","Content");
} else {
	$columnNames = array("To","Subject","Send/Received","Content");
}
$columnNameString = implode(",",$columnNames);

if ( $_SESSION['patient_portal'] ) {
	$userID = $_GET['userID'];
	$userType = $_GET['userType'];
} else {
	$userID = $_SESSION['authUserID'];
	$userType = 1;
}

?>

<html>
<head>

<?php html_header_show();?>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
<link rel="stylesheet" href='<?php echo $GLOBALS['webroot'] ?>/library/js/qtip/jquery.qtip.min.css' type='text/css'>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.css" media="screen" />
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/common.js"></script>
<link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/jquery-ui-1.8.21.custom.css" type="text/css" />
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/qtip/jquery.qtip.js"></script>

<script type="text/javascript">
	$(document).ready(function(){
		$(".message_iframe").fancybox( {
			'overlayOpacity' : 0.0,
			'showCloseButton' : true,
			'height' : 500,
			'width' : 800,
			'centerOnScroll' : false,
			'hideOnOverlayClick' : false
		});
		
		$(".subjectColumn").each(function() {
			$(this).qtip({
				content : '<iframe class="qtip-box" src="' + $(this).attr('title') + '" />',
				hide : {
					delay : 20,
					fixed : true
				},
				position : {
					at : 'bottom left',
					viewport : $(window)
				},
				style: 'qtip-style'
			});
		});
		
		$(".subjectColumn").live({
			mouseover : function() {
				var detailMessageID = $(this).parent().attr('id'); //$(this).attr('id');
				var unread = 0;
				if ( detailMessageID == 0 )
					return;
				if ( $("#" + detailMessageID).hasClass("unReadMessages") ) {
					unread = 1;
					var functionName = "changeStatus";
					var urlLink = "ajax_secure_messages.php";
					$.ajax({
						url: urlLink + "?mode="+functionName+"&inboxMsgs="+detailMessageID+"&status="+unread,
						context: document.body,
						success: function(data) {
							if (data == "SUCCESS")
								$("#" + detailMessageID).removeClass("unReadMessages");
						}
					});
				}
			}
		});		
	});
	
</script>

<style type="text/css">
	.messageRow:hover{
		cursor: pointer;
	}
	
	.deleteColumn:hover {
		cursor:initial;
	}
	.qtip-box{
		width : 100%;
		height : 90%;
	}

	.qtip-style {
		width: 50%;
		max-width: 50%;
		height: 35%;
		max-height: 35%;
	}
	.qtip {
		max-width : 100%;
	}

	.qtip-default {
		background-color: #c5dbec;
		text-align: center;
		vertical-align: middle;
	}
	.unReadMessages {
		background-color: bisque;
	}
	
	.messageTable {
		border-collapse: collapse;
	}
	
	.messageTable th, .messageTable td{
		border: 1px solid gray;
		padding: 2px;
	}
	
	.messageTable th {
		background: lightgray;
	}
	
</style>

</head>

<body class="body_top">
	<span class='title'><?php echo xlt("Secure Messages");?></span>
<form id='thisform' name='thisform' method="post">
	<?php include_once("message_header.php");?>
	<div id="messageDiv" name="messageDiv">
	<?php
		if ( $inFolder ) {
			if ( strchr($inFolder,",") != FALSE ) {
				$inFolders = explode(",",$inFolder);
			} else
				$inFolders[] = $inFolder;
			$content = searchMessages($userID,$userType,$pageRequest,$columnNameString,$sortByColumn,$sortByOrder,$keyWord,$inFolders);
			echo $content;
		} else {
			list($messagesResultSet,$rowCount) = getSecureMessages($userID,$userType,$pageRequest,$sortByColumn,$sortByOrder,$keyWord,$start);
			displayPagination($start, $rowCount, $pageRequest, $userType, $userID, $sortByColumn, $sortByOrder, $keyWord);
		?>
		<table class="messageTable" width='95%' cellspacing='0' cellpadding='0'>
			<tr class='text'>
				<th width="2%">
					<input type=checkbox value="" onclick="javascript:checkAllMessages();" aria-label='Select All' id="selectAll" name="selectAll"/>
				</th>
				<th width='20%' align='left'><a title="<?php echo $columnNames[0]; ?>" href="javascript:sortByColumn('name','<?php echo $sortByOrder;?>');"> <?php echo xl($columnNames[0]); ?></a></th>
				<th width='35%' align='left'><a title="<?php echo $columnNames[1]; ?>" href="javascript:sortByColumn('subject','<?php echo $sortByOrder;?>');"><?php echo xl($columnNames[1]); ?></a></th>
				<th width='10%' align='left'><a title="<?php echo $columnNames[2];?>" href="javascript:sortByColumn('message_time','<?php echo $sortByOrder;?>');"><?php echo xl($columnNames[2]); ?></a></th>
			</tr>

	<?php
	// Display messages.
	while ( $row = sqlFetchArray($messagesResultSet)) {
		$content = printMessageRow($row,$userID,$userType);
		echo $content;
	}
	?>
	</table>
	<?php }?>
	</div>
	

<input type='hidden' id='page_request' name='page_request' value='<?php echo $pageRequest;?>'/>
<input type="hidden" id="pageNumber" name="pageNumber" value="<?php echo $pageNumber;?>"/>
<input type='hidden' id='userType' name='userType' value="<?php echo $userType; ?>"/>
<input type='hidden' id='userID' name='userID' value="<?php echo $userID; ?>"/>
<input type="hidden" id="columnNames" name="columnNames" value="<?php echo $columnNameString; ?>"/>
<input type='hidden' id='messageIDs' name='messageIDs'/>
<input type='hidden' name='sort_column' id='sort_column' value='<?php echo $sortByColumn?>'/>
<input type='hidden' name='sort_order' id='sort_order' value='<?php echo $sortByOrder;?>'/>

</form>
</body>
</html>