<?php
 // Copyright (C) 2006, 2010 Rod Roark <rod@sunsetsystems.com>
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.

 // This report lists prescriptions and their dispensations according
 // to various input selection criteria.
 //
 // Fixed drug name search to work in a broader sense - tony@mi-squared.com 2010
 // Added several reports as per EHR certification requirements for Patient Lists - OpenEMR Support LLC, 2010

	require_once("../globals.php");
	require_once("$srcdir/patient.inc");
	require_once("$srcdir/options.inc.php");
	require_once("../drugs/drugs.inc.php");

 	$type = $_POST["type"];
	$facility = isset($_POST['facility']) ? $_POST['facility'] : '';
	$sql_date_from = fixDate($_POST['date_from'], date('Y-01-01'));
	$sql_date_to = fixDate($_POST['date_to']  , date('Y-m-d'));
	$patient_id = trim($_POST["patient_id"]);
	$age_from = $_POST["age_from"];
	$age_to = $_POST["age_to"];
	$sql_gender = $_POST["gender"];
	$sql_ethnicity = $_POST["ethnicity"];
	$form_lot_number = trim($_POST['form_lot_number']);
	$form_drug_name = trim($_POST["form_drug_name"]);
?>
<html>
<head>
<?php html_header_show();?>
<title>
<?php xl('Clinical Reports','e'); ?>
</title>
<script type="text/javascript" src="../../library/overlib_mini.js"></script>
<script type="text/javascript" src="../../library/textformat.js"></script>
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/js/jquery.1.3.2.js"></script>
<script language="JavaScript">

 var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

 // The OnClick handler for receipt display.
 function show_receipt(payid) {
  // dlgopen('../patient_file/front_payment.php?receipt=1&payid=' + payid, '_blank', 550, 400);
  return false;
 }

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
}

.optional_area {
	<?php
	if($type != 'Prescription' || $type == '')
	{
	?>
	display: none;
	<?php
	}
	?>
}
</style>
<script language="javascript" type="text/javascript">
	function checkType()
	{
		if($('#type').val() == 'Prescription')
		{
			$('.optional_area').css("display", "inline");
		}
		else
		{
			$('.optional_area').css("display", "none");
		}
	}
</script>
</head>

<body class="body_top">

<!-- Required for the popup date selectors -->
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<span class='title'>
<?php xl('Report - Clinical','e'); ?>
</span>
<!-- Search can be done using age range, gender, and ethnicity filters.
Search options include diagnosis, procedure, prescription, medical history, and lab results.
-->
<div id="report_parameters_daterange"> <?php echo date("d F Y", strtotime($form_from_date)) ." &nbsp; to &nbsp; ". date("d F Y", strtotime($form_to_date)); ?> </div>
<form name='theform' id='theform' method='post' action='clinical_reports.php'>
	<div id="report_parameters">
		<input type='hidden' name='form_refresh' id='form_refresh' value=''/>
		<table>
			<tr>
				<td width='740px'><div style='float:left'>
						<table class='text'>
							<tr>
								<td class='label'><?php xl('Facility','e'); ?>
									: </td>
								<td>
								<select name='facility' id="facility">
									<option value='0'>All Facilities</option>
									<?php
									$ores = sqlStatement("SELECT id, name FROM facility  ORDER BY name");
									while ($orow = sqlFetchArray($ores))
									{
									  echo "    <option value='" . $orow['id'] . "'";
									  if ($orow['id'] == $facility) echo " selected";
									  echo ">" . $orow['name'] . "</option>\n";
									}
									?>
								  </select>
				
								</td>
								<td class='label'><?php xl('From','e'); ?>
									: </td>
								<td><input type='text' name='date_from' id="date_from" size='10' value='<?php echo $sql_date_from ?>'
				onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' title='yyyy-mm-dd'>
									<img src='../pic/show_calendar.gif' align='absbottom' width='24' height='22'
				id='img_from_date' border='0' alt='[?]' style='cursor:pointer'
				title='<?php xl('Click here to choose a date','e'); ?>'></td>
							</tr>
							<tr>
								<td class='label'><?php xl('Patient ID','e'); ?>:</td>
								<td><input name='patient_id' type='text' id="patient_id"
				title=<?php xl('Optional numeric patient ID','e','\'','\''); ?> value='<?php echo $patient_id ?>' size='10' maxlength='20' /></td>
								<td class='label'><?php xl('To','e'); ?>: </td>
								<td><input type='text' name='date_to' id="date_to" size='10' value='<?php echo $sql_date_to ?>'
				onKeyUp='datekeyup(this,mypcc)' onBlur='dateblur(this,mypcc)' title='yyyy-mm-dd'>
								<img src='../pic/show_calendar.gif' align='absbottom' width='24' height='22'
				id='img_to_date' border='0' alt='[?]' style='cursor:pointer'
				title='<?php xl('Click here to choose a date','e'); ?>'></td>
							</tr>
							<tr>
								<td class='label'><?php xl('Age Range','e'); ?>
								:</td>
								<td>From
									<input name='age_from' type='text' id="age_from" value="<?php echo $age_from; ?>" size='3' maxlength='3' />To
<input name='age_to' type='text' id="age_to" value="<?php echo $age_to; ?>" size='3' maxlength='3' /></td>
								<td class='label'><?php xl('Option','e'); ?>:</td>
								<td><label for="type"></label>
									<select name="type" id="type" onChange="checkType();">
										<option value="Diagnosis" <?php if($type == 'Diagnosis') { echo "selected"; } ?>>Diagnosis</option>
										<option value="Procedure" <?php if($type == 'Procedure') { echo "selected"; } ?>>Procedure</option>
										<option value="Prescription" <?php if($type == 'Prescription') { echo "selected"; } ?>>Prescription</option>
										<option value="Medical History" <?php if($type == 'Medical History') { echo "selected"; } ?>>Medical History</option>
										<option value="Lab Results" <?php if($type == 'Lab Results') { echo "selected"; } ?>>Lab Results</option>
								</select></td>
							</tr>
							<tr>
								<td class='label'><?php xl('Gender','e'); ?>
								:</td>
								<td><select name='gender' id="gender">
									<option value=''>Unassigned</option>
									<?php
								$ores = sqlStatement("SELECT option_id, title FROM list_options " .
								  "WHERE list_id = 'sex' ORDER BY seq");
								while ($orow = sqlFetchArray($ores)) {
								  echo "    <option value='" . $orow['option_id'] . "'";
								  if ($orow['option_id'] == $gender) echo " selected";
								  echo ">" . $orow['title'] . "</option>\n";
								}
								?>
								</select></td>
								<td class='label'><span class="optional_area"><?php xl('Drug','e'); ?>:</span>&nbsp;</td>
								<td><span class="optional_area"><input type='text' name='form_drug_name' size='10' maxlength='250' value='<?php echo $form_drug_name ?>'
				title=<?php xl('Optional drug name, use % as a wildcard','e','\'','\''); ?> /></span>&nbsp;</td>
							</tr>
							<tr>
								<td class='label'><?php xl('Race/Ethnicity','e'); ?>:</td>
								<td><select name='ethnicity' id="ethnicity">
									<option value=''>Unassigned</option>
									<?php
								$ores = sqlStatement("SELECT option_id, title FROM list_options " .
								  "WHERE list_id = 'ethrace' ORDER BY seq");
								while ($orow = sqlFetchArray($ores)) {
								  echo "    <option value='" . $orow['option_id'] . "'";
								  if ($orow['option_id'] == $ethnicity) echo " selected";
								  echo ">" . $orow['title'] . "</option>\n";
								}
								?>
								</select></td>
								<td class='label'><span class="optional_area">
									<?php xl('Lot','e'); ?>
								:</span>&nbsp;</td>
								<td><span class="optional_area">
									<input type='text' name='form_lot_number' size='10' maxlength='20' value='<?php echo $form_lot_number ?>'
				title=<?php xl('Optional lot number, use % as a wildcard','e','\'','\''); ?> />
								</span></td>
							</tr>
						</table>
				</div></td>
				<td height="100%" align='left' valign='middle'><table style='border-left:1px solid; width:100%; height:100%' >
						<tr>
							<td><div style='margin-left:15px'> <a href='#' class='css_button' onclick='$("#form_refresh").attr("value","true"); $("#theform").submit();'> <span>
									<?php xl('Submit','e'); ?>
									</span> </a>
									<?php if ($_POST['form_refresh']) { ?>
									<a href='#' class='css_button' onclick='window.print()'> <span>
									<?php xl('Print','e'); ?>
									</span> </a>
									<?php } ?>
								</div></td>
						</tr>
				</table></td>
			</tr>
		</table>
	</div>
	<!-- end of parameters -->

<?php

// SQL scripts for the various searches
if ($_POST['form_refresh']) 
{
	if($type == 'Prescription')
	{
		$facility = isset($_POST['facility']) ? $_POST['facility'] : '';
		$sql_date_from = fixDate($_POST['date_from'], date('Y-01-01'));
		$sql_date_to = fixDate($_POST['date_to']  , date('Y-m-d'));
		$patient_id = $_POST["patient_id"];
		$age_from = $_POST["age_from"];
		$age_to = $_POST["age_to"];
		$sql_gender = $_POST["gender"];
		$sql_ethnicity = $_POST["ethnicity"];
		$form_lot_number = trim($_POST['form_lot_number']);
		$form_drug_name = trim($_POST["form_drug_name"]);

		$sqlstmt = "
			SELECT 
			r.id, r.patient_id, r.date_modified AS prescriptions_date_modified, r.dosage, r.route, r.interval, r.refills, r.drug,
			d.name, d.ndc_number, d.form, d.size, d.unit, d.reactions,
			s.sale_id, s.sale_date, s.quantity,
			i.manufacturer, i.lot_number, i.expiration,
			p.pubpid, p.fname, p.lname, p.mname, 
			concat(p.fname, ' ', p.lname) AS patient_name, p.id AS patient_id, DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),p.dob)), '%Y')+0 AS patient_age, p.sex AS patient_sex, p.ethnoracial AS patient_ethnic,
			u.facility_id, concat(u.fname, ' ', u.lname)  AS users_provider
			FROM prescriptions AS r
			LEFT OUTER JOIN drugs AS d ON d.drug_id = r.drug_id
			LEFT OUTER JOIN drug_sales AS s ON s.prescription_id = r.id
			LEFT OUTER JOIN drug_inventory AS i ON i.inventory_id = s.inventory_id
			LEFT OUTER JOIN patient_data AS p ON p.pid = r.patient_id
			LEFT OUTER JOIN users AS u ON u.id = r.provider_id ";

		$where_str = 
			"
			WHERE r.date_modified >= '2010-01-01'
			AND r.date_modified <= '2010-05-31' ";

		if(strlen($sql_gender) > 0)
		{
			$where_str .= "AND p.sex = '$sql_gender' ";
		}

		if(strlen($sql_ethnicity) > 0)
		{
			$where_str .= "AND p.ethnoracial = '$sql_ethnicity' ";
		}

		if(strlen($age_from) > 0)
		{
			$where_str .= "AND DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),p.dob)), '%Y')+0 >= '$age_from' ";
		}

		if(strlen($age_to) > 0)
		{
			$where_str .= "AND DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),p.dob)), '%Y')+0 <= '$age_to' ";
		}

		if(strlen($patient_id) > 0)
		{
			$where_str .= "AND p.id = '$patient_id' ";
		}
		
		if(strlen($form_drug_name) > 0)
		{
			$where_str .= "AND (
			d.name LIKE '$form_drug_name'
			OR r.drug LIKE '$form_drug_name'
			) ";
		}

		if(strlen($form_lot_number) > 0)
		{
			$where_str .= "AND i.lot_number LIKE '$form_lot_number' ";
		}

		$sqlstmt .= $where_str . "ORDER BY p.lname, p.fname, p.pubpid, r.id, s.sale_id";
	}
	else
	{
		$sqlstmt = "select
		concat(pd.fname, ' ', pd.lname) AS patient_name,
		pd.id AS patient_id,
		DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),pd.dob)), '%Y')+0 AS patient_age,
		pd.sex AS patient_sex,
		pd.ethnoracial AS patient_ethnic,
		concat(u.fname, ' ', u.lname)  AS users_provider, ";

		if ( $type == 'Diagnosis' )

		{
			$sqlstmt = $sqlstmt."li.date AS lists_date,
		   li.diagnosis AS lists_diagnosis,
			li.title AS lists_title ";
		}

		if ( $type == 'Procedure')
		{
			//pt.standard_code AS procedure_type_standard_code replaced CPT
			$sqlstmt = $sqlstmt."po.date_ordered AS procedure_order_date_ordered,
		    pt.standard_code AS procedure_type_standard_code,
			pt.name   as procedure_name,
			po.order_priority AS procedure_order_order_priority,
			po.order_status AS procedure_order_order_status,
			po.patient_instructions AS procedure_order_patient_instructions,
			po.activity AS procedure_order_activity,
			po.control_id AS procedure_order_control_id ";
		}
		
		if ( $type == 'Medical History')
		{
			$sqlstmt = $sqlstmt."hd.date AS history_data_date,
		   hd.tobacco AS history_data_tobacco,
			hd.alcohol AS history_data_alcohol,
			hd.recreational_drugs AS history_data_recreational_drugs   ";
		}
			
		if ( $type == 'Lab Results')
		{
			$sqlstmt = $sqlstmt."pr.date AS procedure_result_date,
			   pr.facility AS procedure_result_facility,
				pr.units AS procedure_result_units,
				pr.result AS procedure_result_result,
				pr.range AS procedure_result_range,
				pr.abnormal AS procedure_result_abnormal,
				pr.comments AS procedure_result_comments,
				pr.document_id AS procedure_result_document_id ";
		}
		
		// from
		$sqlstmt = $sqlstmt."from	patient_data		pd,
			  users             u,
			  facility          f,      ";
		
		if ( $type == 'Diagnosis')
		{	$sqlstmt = $sqlstmt."	lists	li "; }

		if ( $type == 'Lab Results')
		{	$sqlstmt = $sqlstmt."	procedure_result	pr, "; }
		
		if ( $type == 'Medical History')
		{   $sqlstmt = $sqlstmt."   history_data   hd "; }
		
		if ( $type == 'Procedure' || $type == 'Lab Results' )
		{
		   $sqlstmt = $sqlstmt."   procedure_order	po,
			  procedure_report  pp,
			  procedure_type    pt ";
		}
		
		// added condition
		if ( !($type == 'Procedure' || $type == 'Lab Results') )
		{
			// where
			$sqlstmt = $sqlstmt."where u.id  = pd.providerid   
			and   u.facility_id  = f.id   ";
		}
		
		if($type == 'Diagnosis')
		{
			$sqlstmt = $sqlstmt." and li.pid  = pd.id ";   
		}
		
		if ( $type == 'Procedure' || $type == 'Lab Results' )
		{
			$sqlstmt = $sqlstmt."where u.id  = po.provider_id
			and   u.facility_id  = f.id   ";
   		   	$sqlstmt = $sqlstmt."and   pp.procedure_order_id   = po.procedure_order_id
			and   pt.procedure_type_id    = po.procedure_type_id
			and   po.patient_id = pd.pid  ";
		}
		
		
		if ( $type == 'Lab Results' )
		{
		   $sqlstmt = $sqlstmt."and pr.procedure_report_id  = pp.procedure_report_id
		and   pr.procedure_type_id    = po.procedure_type_id  ";
		}
		
		if ( $type == 'Medical History')
		{   $sqlstmt = $sqlstmt."and hd.pid   =  pd.pid "; }
		
		if($facility != '0')
		{
			$sqlstmt = $sqlstmt."   and f.id = '$facility' ";
		}

		if ( $type == 'Diagnosis')
		{   
 			$dt_field = 'li.date';
		}
		if ( $type == 'Medical History')
		{   
			$dt_field = 'hd.date';
		}
		if ( $type == 'Lab Results')
		{   
 			$dt_field = 'pr.date';
		}
		if ( $type == 'Procedure')
		{   
			$dt_field = 'po.date_ordered';
		}
		
		$sqlstmt = $sqlstmt."   and $dt_field >=  '$sql_date_from' AND $dt_field <=  '$sql_date_to'";
		
		if ( is_null($patient_id) != 0)
		{	$sqlstmt = $sqlstmt."   and pd.id = ".$patient_id;	}
		
		if ( strlen($age_from) != 0)
		{	$sqlstmt = $sqlstmt."   and DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),pd.dob)), '%Y')+0 >= ".$age_from;	}
		if ( strlen($age_to) != 0)
		{	$sqlstmt = $sqlstmt."   and DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),pd.dob)), '%Y')+0 <= ".$age_to;	}
		
		if ( strlen($sql_gender) != 0)
		{  $sqlstmt = $sqlstmt."   and pd.sex = \"".$sql_gender."\"";  }
		if ( strlen($sql_ethnicity) != 0)
		{  $sqlstmt = $sqlstmt."   and pd.ethnoracial = \"".$sql_ethnicity."\"";   }
	}
	
	$result = mysql_query($sqlstmt);

	if(mysql_num_rows($result) > 0)
	{
?>
		<div id="report_results">
			<table>
				<thead>
					<th><?php xl('Patient','e'); ?></th>
					<th> <?php xl('ID','e'); ?></th>
					<th> <?php xl('Age','e'); ?></th>
					<th> <?php xl('Gender','e'); ?></th>
					<th> <?php xl('Race','e'); ?></th>
					<th> <?php xl('Provider','e'); ?></th>
					
					<?php
					if($type == 'Prescription')
					{
					?>
						<th> <?php xl('Date','e'); ?> </th>
						<th> <?php xl('RX','e'); ?> </th>
						<th> <?php xl('Drug Name','e'); ?> </th>
						<th> <?php xl('NDC','e'); ?> </th>
						<th> <?php xl('Units','e'); ?> </th>
						<th> <?php xl('Refills','e'); ?> </th>
						<th> <?php xl('Instructed','e'); ?> </th>
						<th> <?php xl('Reactions','e'); ?> </th>
						<th> <?php xl('Dispensed','e'); ?> </th>
						<th> <?php xl('Qty','e'); ?> </th>
						<th> <?php xl('Manufacturer','e'); ?> </th>
						<th> <?php xl('Lot','e'); ?> </th>
					<?php
					}
					?>
					
					<?php
					if($type == 'Diagnosis')
					{
					?>
					<!-- Diagnosis -->
					<th> <?php xl('Date','e'); ?></th>
					<th> <?php xl('DX','e'); ?></th>
					<th> <?php xl('Diagnosis Name','e'); ?></th>
					<?php
					}
					?>
					
					<?php
					if($type == 'Procedure')
					{
						
					?>
					<!-- Procedure -->
					<th> <?php xl('Date','e'); ?></th>
					<th> <?php xl('CPT','e'); ?></th>
					<th> <?php xl('Procedure','e'); ?></th>
					<th> <?php xl('Encounter','e'); ?></th>
					<th> <?php xl('Priority','e'); ?></th>
					<th> <?php xl('Status','e'); ?></th>
					<th> <?php xl('Patient Instructions','e'); ?></th>
					<th> <?php xl('Activity','e'); ?></th>
					<th> <?php xl('Control ID','e'); ?></th>
					<?php
					}
					?>
					
					<?php
					if($type == 'Medical History')
					{
					?>
					<!-- Medical History -->
					<th> <?php xl('Date','e'); ?></th>
					<th> <?php xl('Smoking','e'); ?></th>
					<th> <?php xl('Alcohol','e'); ?></th>
					<th> <?php xl('Rec. Drugs','e'); ?></th>
					<?php
					}
					?>
					
					<?php
					if($type == 'Lab Results')
					{
					?>
					<!-- Lab Results -->
					<th> <?php xl('Date','e'); ?></th>
					<th> <?php xl('Facility','e'); ?></th>
					<th> <?php xl('Units','e'); ?></th>
					<th> <?php xl('Result','e'); ?></th>
					<th> <?php xl('Range','e'); ?></th>
					<th> <?php xl('Abnormal','e'); ?></th>
					<th> <?php xl('Comments','e'); ?></th>
					<th> <?php xl('Document ID','e'); ?></th>
					<?php
					}
					?>
				</thead>
				<tbody>
				
				<?php
				if($type == 'Prescription')
				{
					$last_patient_id = 0;
  					$last_prescription_id = 0;
  					while ($row = sqlFetchArray($result)) 
					{
   						$prescription_id = $row['id'];
   						$drug_name       = empty($row['name']) ? $row['drug'] : $row['name'];
   						$ndc_number      = $row['ndc_number'];
   						$drug_units      = $row['size'] . ' ' .
	               		generate_display_field(array('data_type'=>'1','list_id'=>'drug_units'), $row['unit']);
   						$refills         = $row['refills'];
   						$reactions       = $row['reactions'];
   						$instructed      = $row['dosage'] . ' ' .
	               		
						generate_display_field(array('data_type'=>'1','list_id'=>'drug_form'), $row['form']) . ' ' .
                       	generate_display_field(array('data_type'=>'1','list_id'=>'drug_interval'), $row['interval']);
  						
						//if ($row['patient_id'] == $last_patient_id) {
   						if (strcmp($row['pubpid'], $last_patient_id) == 0) 
						{
    						$patient_name = '&nbsp;';
    						$patient_id   = '&nbsp;';
   							
							if ($row['id'] == $last_prescription_id) 
							{
    							$prescription_id = '&nbsp;';
     							$drug_name       = '&nbsp;';
     							$ndc_number      = '&nbsp;';
     							$drug_units      = '&nbsp;';
     							$refills         = '&nbsp;';
     							$reactions       = '&nbsp;';
     							$instructed      = '&nbsp;';
   							}
   						}
						?>
							<tr>
								<td> <?=$row['patient_name']?>&nbsp;</td>
								<td> <?=$row['patient_id']?>&nbsp;</td>
								<td> <?=$row['patient_age']?>&nbsp;</td>
								<td> <?=$row['patient_sex']?>&nbsp;</td>
								<td> <?=$row['patient_ethnic']?>&nbsp;</td>
								<td> <?=$row['users_provider']?>&nbsp;</td>
								<td> <?=$row['prescriptions_date_modified']?>&nbsp;</td>
								<td><?php echo $prescription_id ?></td>
								<td><?php echo $drug_name ?></td>
								<td><?php echo $ndc_number ?></td>
								<td><?php echo $drug_units ?></td>
								<td><?php echo $refills ?></td>
								<td><?php echo $instructed ?></td>
								<td><?php echo $reactions ?></td>
								<td><a href='../drugs/dispense_drug.php?sale_id=<?php echo $row['sale_id'] ?>'
								style='color:#0000ff' target='_blank'>
								<?php echo $row['sale_date'] ?>
								</a>
								</td>
								<td><?php echo $row['quantity'] ?></td>
								<td><?php echo $row['manufacturer'] ?></td>
								<td><?php echo $row['lot_number'] ?></td>
							</tr>
						<?php
						$last_prescription_id = $row['id'];
					   	$last_patient_id = $row['pubpid'];
					}
				}
				else
				{
					while($row = mysql_fetch_array($result))
					{
					?>
					<tr>
						<td> <?=$row['patient_name']?>&nbsp;</td>
						<td> <?=$row['patient_id']?>&nbsp;</td>
						<td> <?=$row['patient_age']?>&nbsp;</td>
						<td> <?=$row['patient_sex']?>&nbsp;</td>
						<td> <?=$row['patient_ethnic']?>&nbsp;</td>
						<td> <?=$row['users_provider']?>&nbsp;</td>
						
						<?php
						if($type == 'Diagnosis')
						{
						?>
						<!-- Diagnosis -->
						<td> <?=$row['lists_date']?>&nbsp;</td>
						<td> <?=$row['lists_diagnosis']?>&nbsp;</td>
						<td> <?=$row['lists_title']?>&nbsp;</td>
						<?php
						}
						?>
						
						<?php
						if($type == 'Procedure')
						{
							$procedure_type_standard_code_arr = explode(':', $row['procedure_type_standard_code']);
							$procedure_type_standard_code = $procedure_type_standard_code_arr[1];
						?>
						<!-- Procedure -->
						<td> <?=$row['procedure_order_date_ordered']?>&nbsp;</td>
						<td> <?=$procedure_type_standard_code?>&nbsp;</td>
						<td> <?=$row['procedure_name']?>&nbsp;</td>
						<td> <?=$row['procedure_order_encounter']?>&nbsp;</td>
						<td> <?=$row['procedure_order_order_priority']?>&nbsp;</td>
						<td> <?=$row['procedure_order_order_status']?>&nbsp;</td>
						<td> <?=$row['procedure_order_patient_instructions']?>&nbsp;</td>
						<td> <?=$row['procedure_order_activity']?>&nbsp;</td>
						<td> <?=$row['procedure_order_control_id']?>&nbsp;</td>
						<?php
						}
						?>
						
						<?php
						if($type == 'Medical History')
						{
						?>
						<!-- Medical History -->
						<td> <?=$row['history_data_date']?>&nbsp;</td>
						<td> <?=$row['history_data_tobacco']?>&nbsp;</td>
						<td> <?=$row['history_data_alcohol']?>&nbsp;</td>
						<td> <?=$row['history_data_recreational_drugs']?>&nbsp;</td>
						<?php
						}
						?>
						
						<?php
						if($type == 'Lab Results')
						{
						?>
						<!-- Lab Results -->
						<td> <?=$row['procedure_result_date']?>&nbsp;</td>
						<td> <?=$row['procedure_result_facility']?>&nbsp;</td>
						<td> <?=$row['procedure_result_units']?>&nbsp;</td>
						<td> <?=$row['procedure_result_result']?>&nbsp;</td>
						<td> <?=$row['procedure_result_range']?>&nbsp;</td>
						<td> <?=$row['procedure_result_abnormal']?>&nbsp;</td>
						<td> <?=$row['procedure_result_comments']?>&nbsp;</td>
						<td> <?=$row['procedure_result_document_id']?>&nbsp;</td>
						<?php
						}
						?>
					</tr>
				<?php
					}
				}
				?>
				</tbody>
			</table>
		</div>
		<!-- end of results -->
	<?php
	}
	?>
<?php 
}
else
{
	?><div class='text'> <?php echo xl('Please input search criteria above, and click Submit to view results.', 'e' ); ?> </div><?php
}
?>
</form>
</body>

<!-- stuff for the popup calendar -->
<style type="text/css">
@import url(../../library/dynarch_calendar.css);
</style>
<script type="text/javascript" src="../../library/dynarch_calendar.js"></script>
<?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
<script type="text/javascript" src="../../library/dynarch_calendar_setup.js"></script>
<script language="Javascript">
 Calendar.setup({inputField:"date_from", ifFormat:"%Y-%m-%d", button:"img_from_date"});
 Calendar.setup({inputField:"date_to", ifFormat:"%Y-%m-%d", button:"img_to_date"});
</script>
</html>
