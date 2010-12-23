<?php
//  ------------------------------------------------------------------------ //
//                     Garden State Health Systems                           //
//                    Copyright (c) 2010 gshsys.com                          //
//                      <http://www.gshsys.com/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
 
require_once(dirname(__FILE__) . "/../interface/globals.php");
require_once(dirname(__FILE__) . "/../library/sql-ccr.inc");
require_once(dirname(__FILE__) . "/../library/sql.inc");
require_once(dirname(__FILE__) . "/uuid.php");
?>

<?php

function createCCR($action){

	//require_once("uuid.php");
	
	$authorID = getUuid();
	
	echo '<!--';

	$result = getHeaderData();
	//while ($row = sqlFetchArray($result)) {

	   $ccr = new DOMDocument('1.0','UTF-8');
	   $e_styleSheet = $ccr->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="ccr.xsl"');
	   $ccr->appendChild($e_styleSheet);

	   $e_ccr = $ccr->createElementNS('urn:astm-org:CCR', 'ContinuityOfCareRecord');
	   $ccr->appendChild($e_ccr);

	   /////////////// Header

	   $e_ccrDocObjID = $ccr->createElement('CCRDocumentObjectID', getUuid());
	   $e_ccr->appendChild($e_ccrDocObjID);

	   $e_Language = $ccr->createElement('Language', 'English');
	   $e_ccr->appendChild($e_Language);

	   $e_Version = $ccr->createElement('Version', 'V1.0');
	   $e_ccr->appendChild($e_Version);

	   $e_dateTime = $ccr->createElement('DateTime');
	   $e_ccr->appendChild($e_dateTime);

           $e_ExactDateTime = $ccr->createElement('ExactDateTime', date('Y-m-d\TH:i:s\Z'));
	   $e_dateTime->appendChild($e_ExactDateTime);

	   $e_patient = $ccr->createElement('Patient');
	   $e_ccr->appendChild($e_patient);

	   $e_ActorID = $ccr->createElement('ActorID', $row['patient_id']);
	   $e_patient->appendChild($e_ActorID);
	   
	   //Header From:
	   $e_From = $ccr->createElement('From');
	   $e_ccr->appendChild($e_From);

	   $e_ActorLink = $ccr->createElement('ActorLink');
	   $e_From->appendChild($e_ActorLink);

	   $e_ActorID = $ccr->createElement('ActorID', $authorID );
	   $e_ActorLink->appendChild($e_ActorID);

	   $e_ActorRole = $ccr->createElement('ActorRole');
	   $e_ActorLink->appendChild($e_ActorRole);

	   $e_Text = $ccr->createElement('Text', 'author');
	   $e_ActorRole->appendChild($e_Text);
	   
	   //Header To:	
	   $e_To = $ccr->createElement('To');
	   $e_ccr->appendChild($e_To);

           $e_ActorLink = $ccr->createElement('ActorLink');
           $e_To->appendChild($e_ActorLink);

           $e_ActorID = $ccr->createElement('ActorID', $row['patient_id']);
           $e_ActorLink->appendChild($e_ActorID);

           $e_ActorRole = $ccr->createElement('ActorRole');
           $e_ActorLink->appendChild($e_ActorRole);

           $e_Text = $ccr->createElement('Text', 'patient');
           $e_ActorRole->appendChild($e_Text);

	   //Header Purpose:
	   $e_Purpose = $ccr->createElement('Purpose');
	   $e_ccr->appendChild($e_Purpose);

	   $e_Description = $ccr->createElement('Description');
	   $e_Purpose->appendChild($e_Description);

	   $e_Text = $ccr->createElement('Text', 'Summary of patient information');
	   $e_Description->appendChild($e_Text);

	   $e_Body = $ccr->createElement('Body');
	   $e_ccr->appendChild($e_Body);
	   
	   /////////////// Problems

	   $e_Problems = $ccr->createElement('Problems');
	   require_once("createCCRProblem.php");
	   $e_Body->appendChild($e_Problems);

	   /////////////// Alerts

	   $e_Alerts = $ccr->createElement('Alerts');
	   require_once("createCCRAlerts.php");
	   $e_Body->appendChild($e_Alerts);

	   ////////////////// Medication

	   $e_Medications = $ccr->createElement('Medications');
	   require_once("createCCRMedication.php");
	   $e_Body->appendChild($e_Medications);

	   ///////////////// Immunization

	   $e_Immunizations = $ccr->createElement('Immunizations');
	   require_once("createCCRImmunization.php");
	   $e_Body->appendChild($e_Immunizations);


	   /////////////////// Results

	   $e_Results = $ccr->createElement('Results');
	   require_once("createCCRResult.php");
	   $e_Body->appendChild($e_Results);


	   /////////////////// Procedures

	   $e_Procedures = $ccr->createElement('Procedures');
	   require_once("createCCRProcedure.php");
	   $e_Body->appendChild($e_Procedures);

	   //////////////////// Footer

	   // $e_VitalSigns = $ccr->createElement('VitalSigns');
	   // $e_Body->appendChild($e_VitalSigns);

	   /////////////// Actors

	   $e_Actors = $ccr->createElement('Actors');
	   require_once("createCCRActor.php");
	   $e_ccr->appendChild($e_Actors);


	   // save created CCR in file
	   
	   echo " \n action=".$action;
	   
	   
	   if ($action=="generate"){
	   	gnrtCCR($ccr);
	   }
	   
	   if($action == "viewccd"){
	   	viewCCD($ccr);
	   }
	//}

}

	
	function gnrtCCR($ccr){
		global $css_header;
		echo "\n css_header=$css_header";
		$ccr->preserveWhiteSpace = false;
		$ccr->formatOutput = true;
		$ccr->save('generatedXml/ccrDebug.xml');
		
		$xmlDom = new DOMDocument();
		$xmlDom->loadXML($ccr->saveXML());
		
		$ss = new DOMDocument();
		$ss->load('ccr.xsl');
		
		$proc = new XSLTProcessor();
		
		$proc->importStylesheet($ss);
		$s_html = $proc->transformToXML($xmlDom);
		
		echo '-->';
		echo $s_html;
		
	}
	
	function viewCCD($ccr){
		
		$ccr->preserveWhiteSpace = false;
		$ccr->formatOutput = true;
		
		$ccr->save('generatedXml/ccrForCCD.xml');
		
		$xmlDom = new DOMDocument();
		$xmlDom->loadXML($ccr->saveXML());
		
		$ccr_ccd = new DOMDocument();
		$ccr_ccd->load('ccd/ccr_ccd.xsl');

		$xslt = new XSLTProcessor();
		$xslt->importStylesheet($ccr_ccd);
		
		$ccd = new DOMDocument();
		$ccd->preserveWhiteSpace = false;
		$ccd->formatOutput = true;
		
		$ccd->loadXML($xslt->transformToXML($xmlDom));
		
		$ccd->save('generatedXml/ccdDebug.xml');
		

		$ss = new DOMDocument();
		$ss->load("ccd/CCD.xsl");
				
		$xslt->importStyleSheet($ss);

		$html = $xslt->transformToXML($ccd);

		echo '-->';
		echo $html;
		
	
	}

	
	function sourceType($ccr, $uuid){
		
		$e_Source = $ccr->createElement('Source');
		
		$e_Actor = $ccr->createElement('Actor');
		$e_Source->appendChild($e_Actor);
		
		$e_ActorID = $ccr->createElement('ActorID',$uuid);
		$e_Actor->appendChild($e_ActorID);
		
		return $e_Source;
	}

	
createCCR($_POST['ccrAction']);

?>
