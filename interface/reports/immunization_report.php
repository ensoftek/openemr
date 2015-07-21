<?php
// Copyright (C) 2011 Ensoftek Inc.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This report lists  patient immunizations for a given date range.
// 07-2015: Ensoftek: Extended for MU2 170.314(f)(2).


require_once("../globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/formatting.inc.php");
include_once("$srcdir/options.inc.php"); 


if(isset($_POST['form_from_date'])) {
  $from_date = $_POST['form_from_date'] !== "" ? 
    fixDate($_POST['form_from_date'], date('Y-m-d')) :
    0;
}
if(isset($_POST['form_to_date'])) {
  $to_date =$_POST['form_to_date'] !== "" ? 
    fixDate($_POST['form_to_date'], date('Y-m-d')) :
    0;
}

//
$form_code = isset($_POST['form_code']) ? $_POST['form_code'] : Array();
//
if (empty ($form_code) ) {
  $query_codes = '';
} else {
  $query_codes = 'c.id in (';
      foreach( $form_code as $code ){ $query_codes .= $code . ","; }
      $query_codes = substr($query_codes ,0,-1);
      $query_codes .= ') and ';
}


function tr($a) {
  return (str_replace(' ','^',$a));
}

function format_cvx_code($cvx_code) {
    
	if ( $cvx_code < 10 ) {
		return "0$cvx_code"; 
	}
		
	return $cvx_code;
}

function format_phone($phone) {

	$phone = preg_replace("/[^0-9]/", "", $phone);
	switch (strlen($phone))
	{
		case 7:
			return tr(preg_replace("/([0-9]{3})([0-9]{4})/", "000 $1$2", $phone));
		case 10:
			return tr(preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "$1 $2$3", $phone));
		default:
			return tr("000 0000000");
	}
}

function format_ethnicity($ethnicity) {

	switch ($ethnicity)
	{
		 case "hisp_or_latin":
		 case "eth_hispanic_latino":
		   return ("H^Hispanic or Latino^HL70189");
		 case "not_hisp_or_latin":
		 case "eth_not_hispanic_lation":
		   return ("N^not Hispanic or Latino^HL70189");
		 default: // Unknown
		   return ("U^Unknown^HL70189");
	}
 }
 

function format_name($patientname) {

  $names = explode(" ", $patientname);
  
  $name = $names[1] . "^" . $names[0] . "^^^^^L";
  
  return $name;
}

function check_date($indate)
{
	$res = strtotime($indate);
	if( !$res )
	{
		return null;
	}
	return $indate;
}

function generate_protection_indicator($allow_imm_info_share, $patient_record_update_date) {
  $s = array('', '');
  
    // Flip it. If "Allow Immunization Info Sharing" == "YES", then it means OK to share information
	//          and "Protection Indicator" = "NO" and vice-versa
	if ( isset($allow_imm_info_share) && !empty($allow_imm_info_share) )
	{
		if ($allow_imm_info_share == "YES")
		{
			$s = array("N", $patient_record_update_date);
		}
		else if ($allow_imm_info_share == "NO")
		{
			$s = array("Y", $patient_record_update_date);
		}
	}
	
	return $s;
}

function generate_home_phone($home_phone, $email) {
  $s = "^";

	// Example: ^PRN^PH^^^503^6431226^~^NET^^nvally@fastmail.com
	if ( isset($home_phone) && !empty($home_phone) )
	{
		$s .= "PRN^PH^^^" . format_phone($home_phone);
	}
	
	if ( isset($email) && !empty($email) )
	{
		$s .= "~^NET^^$email";
	}
	
    return $s;
}

function generate_work_phone($work_phone) {
  $s = '';

	// Example: ^WPN^^^^000^0000000
	if ( isset($work_phone) && !empty($work_phone) )
	{
		$s .= "WPN^^^^" . format_phone($work_phone);
	}
		
    return $s;
}



function generate_patient_identifier_list($pid, $ss) {
  $s = '';

	// Example: MR-76732^^^NIST MPI^MR~184-36-9200^^^MAA^SS
	$s = "$pid" . "^^^MPI&2.16.840.1.113883.19.3.2.1&ISO^MR";
	
	if ( isset($ss) && !empty($ss) )
	{
		$s .= "~$ss^^^MAA^SS";
	}
	
    return $s;
}


function generate_reminder_recall_notices($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';

  	if ( !isset($currvalue) || empty($currvalue) )
	{
		return $s;
	}

  
	$lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	  	
	// Example: 02^Reminder/Recall - any method^HL70215
	$s = $currvalue . "^" . $title = $lrow['title'] . "^" . "HL70215";
	
    return $s;
}

function generate_immunization_registry_status($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';

   	if ( !isset($currvalue) || empty($currvalue) )
	{
		return $s;
	}

  
	$lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	  	
	// Example: A
	$s = $lrow['notes'];
	
    return $s;
}


function generate_mothers_name($mothersname) {

   	if ( isset($mothersname) && !empty($mothersname) )
	{
		return format_name($mothersname);
	}

	return null;
}

function generate_next_of_kin($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';

    $lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	  
	if ( !isset($lrow) || ($lrow['title'] == 'N/A') )
	{
		return null;
	}

	return $lrow;
}


function generate_manufacturer($manufacturer) {

    // Expects the format in "<Manufacturer MVX Code>:<Manufacturer Name>"
	// Example CSL:Behring
    $detail = explode(":", $manufacturer);
	
	// Hard-code the "UNK"(unknown) if not formatted.
	if ( !isset($detail[0]) || ($detail[0] == $manufacturer) )
	{
		$detail[0] = "UNK";
		$detail[1] = $manufacturer;
	}
  
    $s = htmlspecialchars(xl_list_label($detail[0]),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($detail[1]),ENT_NOQUOTES) . "^" . "MVX";

	return $s;
}


function generate_vaccine_type_cvx($vaccine_type_cvx_code) {

    $lrow = sqlQuery("SELECT code_text FROM codes " .
      "WHERE code = ? AND code_type = 100", array($vaccine_type_cvx_code) );
	 
    $s = htmlspecialchars(xl_list_label($vaccine_type_cvx_code),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['code_text']),ENT_NOQUOTES) . "^" . "CVX";

   return $s;
}


function generate_presumed_immunized_snomed($snomed_code) {

    $lrow = sqlQuery("SELECT ct_id FROM code_types WHERE ct_key = 'SNOMED-CT'");	  
	if ( !isset($lrow) )
	{
		return null;
	}
	$code_type = $lrow['ct_id'];

    $lrow = sqlQuery("SELECT code_text FROM codes " .
      "WHERE code = ? AND code_type = ?", array($snomed_code, $code_type) );
	 
    $s = htmlspecialchars(xl_list_label($snomed_code),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['code_text']),ENT_NOQUOTES) . "^" . "SCT";

   return $s;
}


function generate_refusal_reason($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';


    $lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	 
	if ( !isset($lrow) || ($lrow['title'] == 'N/A') )
	{
		return null;
	}

	// 00^Parental Refusal^NIP002
    $s = htmlspecialchars(xl_list_label($lrow['notes']),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['title']),ENT_NOQUOTES) . "^" . "NIP002";

   return $s;
}


function generate_vaccine_administration_notes($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = null;


    $lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	 
	if ( !isset($lrow) || ($lrow['title'] == 'N/A') )
	{
		return null;
	}

	// 00^New immunization record^NIP001
    $s = htmlspecialchars(xl_list_label($lrow['notes']),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['title']),ENT_NOQUOTES) . "^" . "NIP001";

   return array($lrow['notes'], $s);
}


function generate_race($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';

    if ( !isset($currvalue) || empty($currvalue) )
	{
		return null;
	}

    $lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	 
	if ( !isset($lrow) || ($lrow['title'] == 'N/A') )
	{
		return null;
	}

	// Example: 2076-8^native_hawai_or_pac_island^HL70005
    $s = htmlspecialchars(xl_list_label($lrow['notes']),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['title']),ENT_NOQUOTES) . "^" . "HL70005";

   return $s;
}


function generate_vfc_eligibility($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';

    if ( !isset($currvalue) || empty($currvalue) )
	{
		return null;
	}
  
    $lrow = sqlQuery("SELECT title FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	 
	if ( !isset($lrow) || !isset($lrow['title'])  )
	{
		return null;
	}

	// Example: V05^VFC eligible - Federally Qualified Health Center Patient (under-insured)^HL70064
    $s = htmlspecialchars(xl_list_label($currvalue),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['title']),ENT_NOQUOTES) . "^" . "HL70064";

   return $s;
}


function generate_administration_site($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';


    $lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	 
	if ( !isset($lrow) || ($lrow['title'] == 'N/A') )
	{
		return null;
	}

	// Example: LD^Left Arm^HL70163
    $s = htmlspecialchars(xl_list_label($lrow['notes']),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['title']),ENT_NOQUOTES) . "^" . "HL70163";

   return $s;
}


function generate_route($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';


    $lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );
	 
	if ( !isset($lrow) || ($lrow['title'] == 'N/A') )
	{
		return null;
	}

	// Example:  C28161^Intramuscular^NCIT
    $s = htmlspecialchars(xl_list_label($lrow['notes']),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['title']),ENT_NOQUOTES) . "^" . "NCIT";

   return $s;
}


function generate_display_drug_units($frow, $currvalue) {
  $list_id    = $frow['list_id'];
  $s = '';


    $lrow = sqlQuery("SELECT title, notes FROM list_options " .
      "WHERE list_id = ? AND option_id = ?", array($list_id,$currvalue) );

    $s = htmlspecialchars(xl_list_label($lrow['title']),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['notes']),ENT_NOQUOTES) . "^" . "UCUM";

  return $s;
}

  
function get_provider($id, $openemr_name) {
  $s = '';


    $lrow = sqlQuery("SELECT lname, fname, mname FROM users " .
      "WHERE id = ?", array($id) );

	if ( isset($lrow) )
	{
		$s = $id . "^" . htmlspecialchars(xl_list_label($lrow['lname']),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['fname']),ENT_NOQUOTES) . "^" . htmlspecialchars(xl_list_label($lrow['mname']),ENT_NOQUOTES)."^^^^^".strtoupper($openemr_name);
	}
	
  return $s;
  
}  

  $query = 
  "select " .
  "i.patient_id as patientid, " .
  "p.language, ".
  "i.cvx_code , ".
  "i.vaccine_type_cvx_code , ".
  "i.presumed_immunity_snomed_code , ".
  "i.administered_by_id , ".
  "i.immunization_dose as dose, ";
  if ($_POST['form_get_hl7']==='true') {
    $query .= 
      "DATE_FORMAT(p.DOB,'%Y%m%d') as DOB, ".
      "concat(p.street, '^^', p.city, '^', p.state, '^', p.postal_code, '^', p.country_code) as address, ".
      "p.country_code, ".
      "p.phone_home, ".
      "p.phone_biz, ".
      "p.email, ".
      "p.status, ".
      "p.sex, ".
      "p.ethnoracial, ".
      "p.race, ". 
      "p.ethnicity, ".   
      "p.ss, ".   
      "p.mothersname, ".   
      "p.guardiansname, ".   
      "p.immu_reg_status as immunization_registry_status, ".   
      "DATE_FORMAT(p.immu_effective_date,'%Y%m%d') as immunization_registry_status_effective_date, ".   
      "p.recal_remind_note as recall_reminder_notices, ".   
      "DATE_FORMAT(p.recal_effective_date,'%Y%m%d') as recall_reminder_notices_effective_date, ".  
	  "p.allow_imm_info_share as allow_imm_info_share, ".
	  "DATE_FORMAT(p.date,'%Y%m%d') as patient_record_update_date, ".   
      "p.vfc  as vfc_eligibility, ".	  	  
      "c.code_text, ".
      "c.code, ".
      "c.code_type, ".
      "DATE_FORMAT(i.vis_date,'%Y%m%d') as immunizationdate, ".
      "DATE_FORMAT(i.administered_date,'%Y%m%d') as administered_date, ".
	  "DATE_FORMAT(i.education_date,'%Y%m%d') as educationdate, ".
      "i.lot_number as lot_number, ".
	  "i.amount_administered  as amount_administered, ".
	  "i.amount_administered_unit  as amount_administered_unit, ".
	  "i.route  as route, ".
	  "i.administration_site  as administration_site, ".	  
	  "i.refusal_reason  as refusal_reason, ".
      "i.next_of_kin as next_of_kin, ".	  
      "i.manufacturer as manufacturer, ". 
	  "i.vaccine_administration_notes as vaccine_administration_notes, ".
	  "DATE_FORMAT(i.expiration_date,'%Y%m%d') as expiration_date, ".
      "i.note as note, ". 	  
      "p.fname, p.lname, concat(p.lname, '^', p.fname, '^', p.mname) as patientname, ";
  } else {
    $query .= "p.fname, p.lname, ".
	  "DATE_FORMAT(i.administered_date,'%Y-%m-%d') as administered_date, ".
      "i.vis_date as immunizationdate, "  ;
  }
  $query .=
  "concat(u.fname, ' ', u.lname) as uname,i.id as immunizationid, c.code_text_short as immunizationtitle ".
  "from immunizations i, patient_data p, users u, codes c ".
  "left join code_types ct on c.code_type = ct.ct_id ".     
  "where ".
  "ct.ct_key='CVX' and ";
  
  if($from_date!=0) {
    $query .= "i.vis_date >= '$from_date' " ;
  }
  if($from_date!=0 and $to_date!=0) {
    $query .= " and " ;
  }
  if($to_date!=0) {
    $query .= "i.vis_date <= '$to_date' ";
  }
  if($from_date!=0 or $to_date!=0) {
    $query .= " and " ;
  }
  
                
    $query .= "i.patient_id=p.pid and i.administered_by_id = u.id and ".
	$query_codes .
	"i.cvx_code = c.code ";
	$query .= " ORDER BY i.patient_id, i.note ";  // MU2 test case IZ_7_Complete_Record needs the orders to be in a predefined sequence. Force it through "Notes"
	  $res = sqlStatement($query);
	  $immunizations_list = array();
	  $mainimmunizationsarr = array();
	while ($row = sqlFetchArray($res)) {
	$dispProvider = "";
	if($row['administered_by_id'] != "")
		$dispProvider = $row['uname'];
		
        $immunizations_list['Patient ID'] = htmlspecialchars($row['patientid']);
        $immunizations_list['First Name'] = htmlspecialchars($row['fname']);
        $immunizations_list['Last Name'] = htmlspecialchars($row['lname']);
        $immunizations_list['Vaccine Type Code'] = htmlspecialchars($row['vaccine_type_cvx_code']);
        $immunizations_list['Immunization Code'] = htmlspecialchars($row['cvx_code']);
        $immunizations_list['Immunization Title'] = htmlspecialchars($row['immunizationtitle']);
        $immunizations_list['Dose'] = htmlspecialchars($row['dose']);
        $immunizations_list['Immunization Date'] = htmlspecialchars(oeFormatShortDate($row['immunizationdate']));
        $immunizations_list['Provider'] = htmlspecialchars($dispProvider);
        $immunizations_list['Administered Date'] = htmlspecialchars(oeFormatShortDate($row['administered_date']));
        
		$mainimmunizationsarr[] = $immunizations_list;

	}
  
$D="\r";
$nowdate = date('Ymdhs');
$now = date('YmdGi');
$now1 = date('Y-m-d G:i');
$filename = "imm_reg_". $now . ".hl7";
$imm_count = 0;
$prev_obx_count = 0;
$prev_cvx_code = '';
$prev_pid = 0;

// GENERATE HL7 FILE
if ($_POST['form_get_hl7']==='true') {
	$content = ''; 

  $res = sqlStatement($query);

  while ($r = sqlFetchArray($res)) {
  
	// If multiple(and different) patient immunization results, generate multiple MSH segments
	if ( $prev_pid != $r['patientid'] ) // If transitioning to a different patient, reset the immunization count.
	{
		$imm_count = 0;
	}
	
	// Generate MSH and PID segments only for the first immunization(in case of multiple immunizations).
	if ( ($imm_count <= 0) && ($prev_pid != $r['patientid']) )
	{
			$content .= "MSH|^~\&|".strtoupper($openemr_name)."|X68||".strtoupper($openemr_name)."|$nowdate||".
			  "VXU^V04^VXU_V04|".strtoupper($openemr_name)."-110316102457117|P|2.5.1|||AL|ER" .
			  "$D" ;
			  
			if ($r['sex']==='Male') $r['sex'] = 'M';
			if ($r['sex']==='Female') $r['sex'] = 'F';
			if ($r['status']==='married') $r['status'] = 'M';
			if ($r['status']==='single') $r['status'] = 'S';
			if ($r['status']==='divorced') $r['status'] = 'D';
			if ($r['status']==='widowed') $r['status'] = 'W';
			if ($r['status']==='separated') $r['status'] = 'A';
			if ($r['status']==='domestic partner') $r['status'] = 'P';
			$content .= "PID|" . // [[ 3.72 ]]
				"1|" . // 1. Set id
				"|" . // 2. (B)Patient id
				generate_patient_identifier_list($r['patientid'], $r['ss']) . "|". // 3. (R) Patient indentifier list. TODO: Hard-coded the OID from NIST test. 
				"|" . // 4. (B) Alternate PID
				$r['patientname']."^^^^L|" . // 5.R. Name
				generate_mothers_name($r['mothersname']) . "|" . // 6. Mather Maiden Name
				$r['DOB']."|" . // 7. Date, time of birth
				$r['sex']."|" . // 8. Sex
				"|" . // 9.B Patient Alias
				generate_race(array('data_type'=>'1','list_id'=>'race'), $r['race']) . "|" . // 10. Race
				$r['address'] . "^L" . "|" . // 11. Address. Default to address type  Mailing Address(M)
				"|" . // 12. county code
				generate_home_phone($r['phone_home'], $r['email']) . "|" . // 13. Phone Home. Default to Primary Home Number(PRN)
				generate_work_phone($r['phone_biz']) . "|" . // 14. Phone Work.
				"|" . // 15. Primary language
				$r['status']."|" . // 16. Marital status
				"|" . // 17. Religion
				"|" . // 18. patient Account Number
				"|" . // 19.B SSN Number
				"|" . // 20.B Driver license number
				"|" . // 21. Mathers Identifier
				format_ethnicity($r['ethnicity']) . "|" . // 22. Ethnic Group
				"|" . // 23. Birth Plase
				"|" . // 24. Multiple birth indicator
				"|" . // 25. Birth order
				"|" . // 26. Citizenship
				"|" . // 27. Veteran military status
				"|" . // 28.B Nationality
				"|" . // 29. Patient Death Date and Time
				"|" . // 30. Patient Death Indicator
				"|" . // 31. Identity Unknown Indicator
				"|" . // 32. Identity Reliability Code
				"|" . // 33. Last Update Date/Time
				"|" . // 34. Last Update Facility
				"|" . // 35. Species Code
				"|" . // 36. Breed Code
				"|" . // 37. Breed Code
				"|" . // 38. Production Class Code
				""  . // 39. Tribal Citizenship
				"$D" ;
	} // if ( $imm_count <= 0 )
				
	// PD1	
	//    If "Demographics-->Choices-->Immunization Registry Status" OR "Demographics-->Choices-->Recall Reminder Notices"
	//    OR "Demographics-->Choices-->Allow Immunization Info Sharing" is set, only then generate the PD1 segment.
	if ( ( isset($r['immunization_registry_status']) && !empty($r['immunization_registry_status']) ) ||
  	     ( isset($r['recall_reminder_notices']) && !empty($r['recall_reminder_notices']) ) || 
		 ( isset($r['allow_imm_info_share']) && !empty($r['allow_imm_info_share']) ) )
	{
		$protection_indicator = generate_protection_indicator($r['allow_imm_info_share'], $r['patient_record_update_date']);
	
	    $content .= "PD1" .
		"|||||||||||" .
	    generate_reminder_recall_notices(array('data_type'=>'1','list_id'=>'recall_reminder_notices'), $r['recall_reminder_notices']) . "|" .
		$protection_indicator[0] . "|" .
		$protection_indicator[1] . "|" .
		"||" .
	    generate_immunization_registry_status(array('data_type'=>'1','list_id'=>'immunization_registry_status'), $r['immunization_registry_status']) .
		"|" . check_date($r['immunization_registry_status_effective_date']) .		
		"|" . check_date($r['recall_reminder_notices_effective_date']) .
        "$D" ;
	}
		
	// NK1	
	// Check if immunization is refused to see if we need NK1 segment
	$refused = false;
    $refusal_reason = generate_refusal_reason(array('data_type'=>'1','list_id'=>'refusal_reason'), $r['refusal_reason']);
	if ($refusal_reason != null )
	{
		$refused = true;
	}
	
	if ( !$refused )
	{
    	$nk = generate_next_of_kin(array('data_type'=>'1','list_id'=>'contact_relationship'), $r['next_of_kin']);
		if ( $nk != null )
		{
			$nk_name = '';
			$nk_code = '';
			
			if ( $nk['title'] == 'Mother')
			{
				$nk_name = format_name($r['mothersname']);
				$nk_code = "MTH";
			}
			else
			{
				$nk_name = format_name($r['guardiansname']); // Default to Guardian
				$nk_code = "GRD";
			}
						
			if ( isset($nk_name) && !empty($nk_name) )
			{
				$content .= "NK1" . 
				"|" . 
				"1" .
				"|" .
				$nk_name . 
				"|" .
				$nk_code . "^" . $nk['title'] . "^HL70063" . 
				"|" .
				$r['address'] . "^L" . "|" . // Address. Default to patient address
				 "|" .
				 generate_home_phone($r['phone_home'], $r['email']) .
				"$D" ;
			}
		}
	}
	
	// Check if its a case of "no vaccine administered(CVX: 998)"
	$no_vaccine_administered = false;
	if ( format_cvx_code($r['code']) == '998' )
	{
		$no_vaccine_administered = true;
	}
	
	// Check if this a "complete record", i.e. where we can have multiple immunizations with the case CVX code.
	// In such case, skip writing ORCX, RXR & RXA. Go straight to the OBX
	if ( $prev_cvx_code == format_cvx_code($r['code']) )
	{
	    $imm_rec_switch_over = true;
		goto complete_record;
	}
		
    $content .= "ORC" . // ORC mandatory for RXA
        "|" . 
        "RE" .
		"|" .
		"|";
		if ( !$refused && !$no_vaccine_administered )
		{
			$content .= $filename . "^NDA" . "|";
			$content .= "||||||" .
			get_provider($_SESSION['authUserID'], $openemr_name) .
			"|" .
			"|" .
			get_provider($r["administered_by_id"], $openemr_name) . "^L";
		}
		else
		{
			$content .= "9999^CDC";
		}		
    $content .= "$D" ;
		
	$vaccine_admin_notes = generate_vaccine_administration_notes(array('data_type'=>'1','list_id'=>'vaccine_administration_notes'), $r['vaccine_administration_notes']);	
	$historical = false;
	if ( isset($vaccine_admin_notes) )
	{
		if ( ($vaccine_admin_notes[0] != 'N/A') && ($vaccine_admin_notes[0] != '00') )
		{
			$historical = true;
		}
	}
	
	
    $content .= "RXA|" . 
        "0|" . // 1. Give Sub-ID Counter
        "1|" . // 2. Administrattion Sub-ID Counter
    	$r['administered_date']."|" . // 3. Date/Time Start of Administration
    	$r['administered_date']."|" . // 4. Date/Time End of Administration
        format_cvx_code($r['code']). "^" . $r['immunizationtitle'] . "^" . "CVX" ."|"; // 5. Administration Code(CVX)
		if ( !$refused && !$historical && !$no_vaccine_administered )
		{
				$content .= $r["amount_administered"] . "|" . // 6. Administered Amount. TODO: Immunization amt currently not captured in database, default to 999(not recorded)
				generate_display_drug_units(array('data_type'=>'1','list_id'=>'drug_units'), $r['amount_administered_unit']) . "|" . // 7. Administered Units
				"|" . // 8. Administered Dosage Form
				$vaccine_admin_notes[1] . "|" . // 9. Administration Notes
				get_provider($r["administered_by_id"], $openemr_name) . "|" . // 10. Administering Provider 
				"^^^X68" . "|" . // 11. Administered-at Location
				"|" . // 12. Administered Per (Time Unit)
				"|" . // 13. Administered Strength
				"|" . // 14. Administered Strength Units
				$r['lot_number']."|" . // 15. Substance Lot Number
				$r['expiration_date'] . "|" . // 16. Substance Expiration Date
				generate_manufacturer($r['manufacturer']) . "|" . // 17. Substance Manufacturer Name
				"|" . // 18. Substance/Treatment Refusal Reason
				"|" . // 19.Indication
				"CP|" . // 20.Completion Status // Hard-code to complete(CP) for now
				"A"; // 21.Action Code - RXA
		}
		else
		{
		    // If refused immunization, send 999 for amount administered and the reason for refusal.
			$content .= "999|||" .
			$vaccine_admin_notes[1] . "|"; // 9. Administration Notes
			if ( $refused )
			{
				$content .= "||||||||" . 
				$refusal_reason .
				"|" .
				"|" .
				"RE";
			}
			else if ( $no_vaccine_administered )
			{
				$content .= "||||||||||NA";
			}
		}
        $content .= "$D" ;

    // If refused, bail out here as no further segments are needed.		
    if ( $refused || $historical )
    {
		goto end;
	}
	
	// If no vaccine administered, generate the corresponding OBX code and bail out.
	if ( $no_vaccine_administered )
	{
			$content .= "OBX" . 
			"|" . 
			"1" .
			"|" .
			"CE" .
			"|" .
			"59784-9^Disease with presumed immunity^LN" .
			"|" .
			"1" .
			"|" .
			generate_presumed_immunized_snomed($r['presumed_immunity_snomed_code']) .
			"||||||F" .
			"$D" ;	

		goto end;
	}
	
    $content .= "RXR" . // RXR
        "|" . 
        generate_route(array('data_type'=>'1','list_id'=>'drug_route'), $r['route']) . // Route
		"|" .
		generate_administration_site(array('data_type'=>'1','list_id'=>'proc_body_site'), $r['administration_site']) . // Administration site
        "$D" ;
		
	// OBX
	// Check if VFC Eligibility information is given.
    $obx_count = 0;
    $vfc_eligibility = generate_vfc_eligibility(array('data_type'=>'1','list_id'=>'vfc_eligibility'), $r['vfc_eligibility']);
	if ( $vfc_eligibility != null )
	{
	    $obx_count++;
		$content .= "OBX" .
			"|" . 
			$obx_count .
			"|" .
			"CE" .
			"|" .
			"64994-7^Vaccine funding program eligibility category^LN" .
			"|" .
			"1" .
			"|" .
			$vfc_eligibility .
			"||||||F|||" .
			$r['administered_date'] .
			"|||VXC40^Eligibility captured at the immunization level^CDCPHINVS" .
			"$D" ;		
	}
	
complete_record:	

	$obx4 = 2; // It is 2 in normal(non-multi) cases.
    if ( $imm_rec_switch_over == true )
	{
	    $obx_count = $prev_obx_count;
		$obx4 = $prev_obx_count; // Must be a unique number for every following 3-set OBX of a given ORC
	}

	// Vaccine type.
	$obx_count++;
		$content .= "OBX" . 
			"|" . 
			$obx_count .
			"|" .
			"CE" .
			"|" .
			"30956-7^vaccine type^LN" .
			"|" .
			$obx4 .
			"|" .
			generate_vaccine_type_cvx($r['vaccine_type_cvx_code']) .
			"||||||F" .
			"$D" ;	
    // Date of VIS Statement			
	$obx_count++;
		$content .= "OBX" . 
			"|" . 
			$obx_count .
			"|" .
			"TS" .
			"|" .
			"29768-9^Date vaccine information statement published^LN" .
			"|" .
			$obx4 .
			"|" .
			$r['immunizationdate'] .
			"||||||F" .
			"$D" ;		
	// Date Immunization Information Statements Given
	$obx_count++;
		$content .= "OBX" . 
			"|" . 
			$obx_count .
			"|" .
			"TS" .
			"|" .
			"29769-7^Date vaccine information statement presented^LN" .
			"|" .
			$obx4 .
			"|" .
			$r['educationdate'] .
			"||||||F" .
			"$D" ;		
			
 
end: 

    $imm_count++;
	$prev_pid = $r['patientid'];
	$prev_cvx_code = format_cvx_code($r['code']);
	$prev_obx_count = $obx_count;
	$imm_rec_switch_over = false;

}

  // send the header here
  header('Content-type: text/plain');
  header('Content-Disposition: attachment; filename=' . $filename );

  // put the content in the file
  echo($content);
  exit;
}
?>

<html>
<head>
<?php html_header_show();?>
<title><?php xl('Immunization Registry','e'); ?></title>
<style type="text/css">@import url(../../library/dynarch_calendar.css);</style>
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/textformat.js"></script>
<script type="text/javascript" src="../../library/dynarch_calendar.js"></script>
<?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
<script type="text/javascript" src="../../library/dynarch_calendar_setup.js"></script>
<script type="text/javascript" src="../../library/js/jquery.1.3.2.js"></script>
<script language="JavaScript">
<?php require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>
</script>

<link rel='stylesheet' href='<?php echo $css_header ?>' type='text/css'>
<style type="text/css">
/* specifically include & exclude from printing */
@media print {
    #report_parameters {
        visibility: hidden;
        display: none;
    }
    #report_parameters_daterange {
        visibility: visible;
        display: inline;
		margin-bottom: 10px;
    }
    #report_results table {
       margin-top: 0px;
    }
}
/* specifically exclude some from the screen */
@media screen {
	#report_parameters_daterange {
		visibility: hidden;
		display: none;
	}
	#report_results {
		width: 100%;
	}
}
</style>
</head>

<body class="body_top">

<span class='title'><?php xl('Report','e'); ?> - <?php xl('Immunization Registry','e'); ?></span>

<div id="report_parameters_daterange">
<?php echo date("d F Y", strtotime($form_from_date)) ." &nbsp; to &nbsp; ". date("d F Y", strtotime($form_to_date)); ?>
</div>

<form name='theform' id='theform' method='post' action='immunization_report.php'
onsubmit='return top.restoreSession()'>
<div id="report_parameters">
<input type='hidden' name='form_refresh' id='form_refresh' value=''/>
<input type='hidden' name='form_get_hl7' id='form_get_hl7' value=''/>
<table>
 <tr>
  <td width='410px'>
    <div style='float:left'>
      <table class='text'>
        <tr>
          <td class='label'>
            <?php xl('Codes','e'); ?>:
          </td>
          <td>
<?php
 // Build a drop-down list of codes.
 //
 $query1 = "select id, concat('CVX:',code) as name from codes ".
   " left join code_types ct on codes.code_type = ct.ct_id ".
   " where ct.ct_key='CVX' ORDER BY name";
 $cres = sqlStatement($query1);
 echo "   <select multiple='multiple' size='3' name='form_code[]'>\n";
 //echo "    <option value=''>-- " . xl('All Codes') . " --\n";
 while ($crow = sqlFetchArray($cres)) {
  $codeid = $crow['id'];
  echo "    <option value='$codeid'";
  if (in_array($codeid, $form_code)) echo " selected";
  echo ">" . $crow['name'] . "\n";
 }
 echo "   </select>\n";
?>
          </td>
          <td class='label'>
            <?php xl('From','e'); ?>:
          </td>
          <td>
            <input type='text' name='form_from_date' id="form_from_date"
            size='10' value='<?php echo $form_from_date ?>'
            onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' 
            title='yyyy-mm-dd'>
            <img src='../pic/show_calendar.gif' align='absbottom' 
            width='24' height='22' id='img_from_date' border='0' 
            alt='[?]' style='cursor:pointer'
            title='<?php xl('Click here to choose a date','e'); ?>'>
          </td>
          <td class='label'>
            <?php xl('To','e'); ?>:
          </td>
          <td>
            <input type='text' name='form_to_date' id="form_to_date" 
            size='10' value='<?php echo $form_to_date ?>'
            onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' 
            title='yyyy-mm-dd'>
            <img src='../pic/show_calendar.gif' align='absbottom' 
            width='24' height='22' id='img_to_date' border='0' 
            alt='[?]' style='cursor:pointer'
            title='<?php xl('Click here to choose a date','e'); ?>'>
          </td>
        </tr>
      </table>
    </div>
  </td>
  <td align='left' valign='middle' height="100%">
    <table style='border-left:1px solid; width:100%; height:100%' >
      <tr>
        <td>
          <div style='margin-left:15px'>
            <a href='#' class='css_button' 
            onclick='
            $("#form_refresh").attr("value","true"); 
            $("#form_get_hl7").attr("value","false"); 
            $("#theform").submit();
            '>
            <span>
              <?php xl('Refresh','e'); ?>
            </spain>
            </a>
            <?php if ($_POST['form_refresh']) { ?>
              <a href='#' class='css_button' onclick='window.print()'>
                <span>
                  <?php xl('Print','e'); ?>
                </span>
              </a>
              <a href='#' class='css_button' onclick=
              "if(confirm('<?php xl('This step will generate a file which you have to save for future use. The file cannot be generated again. Do you want to proceed?','e'); ?>')) {
                     $('#form_get_hl7').attr('value','true'); 
                     $('#theform').submit();
              }">
                <span>
                  <?php xl('Get HL7','e'); ?>
                </span>
              </a>
            <?php } ?>
          </div>
        </td>
      </tr>
    </table>
  </td>
 </tr>
</table>
</div> <!-- end of parameters -->


<?php
 if ($_POST['form_refresh']) {
?>
<div id="report_results">
<table>
 <thead align="left">
  <th> <?php xl('Patient ID','e'); ?> </th>
  <th> <?php xl('Patient Name','e'); ?> </th>
  <th> <?php xl('Immunization Code','e'); ?> </th>
  <th> <?php xl('Immunization Title','e'); ?> </th>
  <th> <?php xl('Immunization Date','e'); ?> </th>
 </thead>
 <tbody>
<?php
  $total = 0;
  //echo "<p> DEBUG query: $query </p>\n"; // debugging
  $res = sqlStatement($query);


  while ($row = sqlFetchArray($res)) {
?>
 <tr>
  <td>
  <?php echo htmlspecialchars($row['patientid']) ?>
  </td>
  <td>
   <?php echo htmlspecialchars($row['patientname']) ?>
  </td>
  <td>
   <?php echo htmlspecialchars($row['cvx_code']) ?>
  </td>
  <td>
   <?php echo htmlspecialchars($row['immunizationtitle']) ?>
  </td>
  <td>
   <?php echo htmlspecialchars($row['immunizationdate']) ?>
  </td>
 </tr>
<?php
   ++$total;
  }
?>
 <tr class="report_totals">
  <td colspan='9'>
   <?php xl('Total Number of Immunizations','e'); ?>
   :
   <?php echo $total ?>
  </td>
 </tr>

</tbody>
</table>
</div> <!-- end of results -->
<?php } else { ?>
<div class='text'>
  <?php echo xl('Click Refresh to view all results, or please input search criteria above to view specific results.', 'e' ); ?>
</div>
<?php } ?>
</form>

<script language='JavaScript'>
 Calendar.setup({inputField:"form_from_date", ifFormat:"%Y-%m-%d", button:"img_from_date"});
 Calendar.setup({inputField:"form_to_date", ifFormat:"%Y-%m-%d", button:"img_to_date"});
</script>

</body>
</html>
