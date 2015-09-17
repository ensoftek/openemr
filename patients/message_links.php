<?php

/**
 *
 * Left Navigation Links on Patient Portal
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

require_once("verify_session.php");
if ( isset( $_SESSION['authRep'])) {
	$userID = $_SESSION['authRep'];
	$userType = 3;
} else {
	$userID = $_SESSION['pid'];
	$userType = 2;
}
?>

<html>
<head>
<?php html_header_show();?>
<title><?php xl('Secure Messages','e'); ?></title>
<style>
.arrow-right {
	width: 0; 
	height: 0; 
	border-top: 5px solid transparent;
	border-bottom: 5px solid transparent;
	border-left: 5px solid black;
}
</style>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
</head>

<body class='bgcolor2'>
<br/>
<table>
	<tr>
		<td><div class="arrow-right"></div></td>
		<td><a target='FormRight' href="<?php echo $GLOBALS['web_root'];?>/interface/main/secure_messages/messages.php?page_request=inbox&userID=<?php echo $userID; ?>&userType=<?php echo $userType;?>" title='Inbox'><span class="text"><?php echo xl("Inbox"); ?></span></a></td>
	</tr>
	
	<tr>
		<td><div class="arrow-right"></div></td>
		<td><a target='FormRight' href="<?php echo $GLOBALS['web_root'];?>/interface/main/secure_messages/messages.php?page_request=sent_items&userID=<?php echo $userID; ?>&userType=<?php echo $userType;?>" title='Sent Items'><span class="text"><?php echo xl("Sent Items"); ?></span></a></td>
	</tr>
</table>s

</body>
</html>