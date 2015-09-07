<?php
/**
 *
 * View Message page.
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

$messageID = $_REQUEST['message_id'];
$preview = $_REQUEST['preview'];

// Get message details.
$messageDetails = getMessageDetails($messageID);
$fromNameArray = getMessageUserName($messageDetails[0]['from_id'],$messageDetails[0]['from_type']);
$fromName = $fromNameArray[0];
$mSubject = $messageDetails[0]['subject'];
$mBody = $messageDetails[0]['body'];
foreach ( $messageDetails as $messageDetail ) {
	$names[$messageDetail['to_type']] = getMessageUserName($messageDetail['toAddresses'],$messageDetail['to_type']);
}

if ( $preview ) {
	echo $mBody;
	exit();
}
?>

<html>
<head>

<?php html_header_show();?>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/common.js"></script>
<link rel="stylesheet" href="<?php echo $GLOBALS['webroot'] ?>/library/css/jquery-ui-1.8.21.custom.css" type="text/css" />
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui-1.8.6.custom.min.js"></script>

<script type="text/javascript">

	$(document).ready(function(){
	});
	
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
</style>

</head>

<body>
<form method='post' id="thisForm" name="thisForm" action="">

<table>
	<tr>
		<td><span class="title"><?php echo xl("Message"); ?></span></td>
		<td><a href='new_message.php?userID=<?php echo $_REQUEST['userID']; ?>&userType=<?php echo $_REQUEST['userType']; ?>&message_id=<?php echo $_REQUEST['message_id']; ?>' class='css_button' ><span><?php xl('Reply','e'); ?> </span></a></td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
	<tr>
		<td class="text"><span><?php echo xl("From"); ?></span></td>
		<td class="text_bold"><b><?php echo $fromName; ?></b></td>
	</tr>
	
	<tr>
		<td class='text'><?php echo xl("To"); ?></td>
		<td align="left" id="toAddresses" name="toAddresses">
		<?php
			foreach ( $names as $name ) {
				foreach ( $name as $displayName )
					echo "<div class=toAddress>" . $displayName . "</div>&nbsp;";
			}
		?>
		</td>
	</tr>
	
	<tr>
		<td class="text"><span><?php echo xl("Subject"); ?></span></td>
		<td>
			<input type="text" id="mSubject" name="mSubject" size="52" maxlength="120" readonly value="<?php echo xl($mSubject);?>"/>
		</td>
	</tr>
	
	<tr>
		<td class="text"><span><?php echo xl("Message"); ?></span></td>
		<td>
			<textarea name='mBody' id='mBody' cols='40' rows='12' style='width:390px;' readonly><?php echo $mBody; ?></textarea>
		</td>
	</tr>
</table>
	
</form>
</body>
</html>