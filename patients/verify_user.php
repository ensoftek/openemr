<?php

/**
 *
 * Verify User Page. 
 * Should be included all the files which are used in patient portal and also in EHR.
 * The main purpose of this file is to make $ignoreAuth as true
 * Should be called included before globals.php
 * 
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

//continue session
session_start();

// check if accessed from patient portal and session is set.
// pid and auth rep are not validated here. Assuming it is already validated
if ( ( isset($_SESSION['pid']) || isset($_SESSION['authRep']) ) && isset($_SESSION['patient_portal_onsite']) ) {
	$pid = ( $_SESSION['pid'] ) ? $_SESSION['pid'] : $_SESSION['authRepPid'];
	$_SESSION['patient_portal'] = true;
	$ignoreAuth=true;
} else {
	session_destroy(); // destroy the session if not from patient portal
}

?>
