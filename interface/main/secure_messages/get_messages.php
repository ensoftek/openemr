<?php
/**
 *
 * Secure Message page in patient portal
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
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//
if($_GET['application'] == 'p_portal') {
	$ignoreAuth=true;
}
require_once("../../globals.php");

$site = $_SESSION['site_id'];
if($_GET['application'] == 'p_portal') {
	$_SESSION['patient_portal'] = true;
}

$userID = $_GET['userID'];
$userType = $_GET['userType'];
?>
<html>

<head>
<title><?php echo htmlspecialchars( xl('Patient Portal Summary'), ENT_NOQUOTES); ?></title>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">


<style>
</style>

</head>

 <frameset cols="15%,*" id="secMessage_frame"  class='bgcolor2' style='border:2px red solid;'>
    <frame src="<?php echo $GLOBALS['web_root']; ?>/patients/message_links.php" name="FormLeft" scrolling="auto" >
    <frame src="<?php echo $GLOBALS['web_root']; ?>/interface/main/secure_messages/messages.php?page_request=inbox&userID=<?php echo $userID; ?>&userType=<?php echo $userType;?>"  name="FormRight" scrolling="auto" >   
</frameset>

</html>
