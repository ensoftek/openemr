<?php

require_once($GLOBALS['fileroot'] . "/library/classes/Controller.class.php");
require_once($GLOBALS['fileroot'] . "/library/classes/Laboratory.class.php");
require_once($GLOBALS['fileroot'] . "/library/classes/WSWrapper.class.php");

class C_Laboratory extends Controller {

	var $template_mod;
	var $ilaboratories;

	function C_Laboratory($template_mod = "general") {
		parent::Controller();
		$this->ilaboratories = array();
		$this->template_mod = $template_mod;
		$this->assign("FORM_ACTION", $GLOBALS['webroot']."/controller.php?" . $_SERVER['QUERY_STRING']);
		$this->assign("CURRENT_ACTION", $GLOBALS['webroot']."/controller.php?" . "practice_settings&laboratory&");
		$this->assign("STYLE", $GLOBALS['style']);
		$this->assign("WEB_ROOT", $GLOBALS['webroot'] );		
	}

	function default_action() {
		return $this->list_action();
	}

	function edit_action($id = "",$patient_id="",$p_obj = null) {
		if ($p_obj != null && get_class($p_obj) == "laboratory") {
			$this->ilaboratories[0] = $p_obj;
		}
		elseif (get_class($this->ilaboratories[0]) != "laboratory" ) {
			$this->ilaboratories[0] = new Laboratory($id);
		}

		$this->assign("laboratory", $this->ilaboratories[0]);
		return $this->fetch($GLOBALS['template_dir'] . "laboratories/" . $this->template_mod . "_edit.html");
	}

	function list_action($sort = "") {

		if (!empty($sort)) {
			$this->assign("ilaboratories", Laboratory::laboratories_factory("",$sort));
		}
		else {
			$this->assign("ilaboratories", Laboratory::laboratories_factory());
		}

		return $this->fetch($GLOBALS['template_dir'] . "laboratories/" . $this->template_mod . "_list.html");
	}


	function edit_action_process() {
		if ($_POST['process'] != "true")
			return;
		if (is_numeric($_POST['id'])) {
			$this->ilaboratories[0] = new Laboratory($_POST['id']);
		}
		else {
			$this->ilaboratories[0] = new Laboratory();
		}

  		parent::populate_object($this->ilaboratories[0]);

		$this->ilaboratories[0]->persist();
		$this->ilaboratories[0]->populate();

		$_POST['process'] = "";
	}

	function _sync_ws($ic) {
/*
		$db = $GLOBALS['adodb']['db'];

		$customer_info = array();

		$sql = "SELECT foreign_id,foreign_table FROM integration_mapping where local_table = 'insurance_companies' and local_id = '" . $ic->get_id() . "'";
		$result = $db->Execute($sql);
		if ($result && !$result->EOF) {
			$customer_info['foreign_update'] = true;
			$customer_info['foreign_id'] = $result->fields['foreign_id'];
			$customer_info['foreign_table'] = $result->fields['foreign_table'];
		}

		///xml rpc code to connect to accounting package and add user to it
		$customer_info['firstname'] = "";
		$customer_info['lastname'] = $ic->get_name();
		$a = $ic->get_address();
		$customer_info['address'] = $a->get_line1() . " " . $a->get_line2();
		$customer_info['suburb'] = $a->get_city();
		$customer_info['postcode'] = $a->get_zip();

		//ezybiz wants state as a code rather than abbreviation
		$customer_info['geo_zone_id'] = "";
		$sql = "SELECT zone_id from geo_zone_reference where zone_code = '" . strtoupper($a->get_state()) . "'";
		$db = $GLOBALS['adodb']['db'];
		$result = $db->Execute($sql);
		if ($result && !$result->EOF) {
			$customer_info['geo_zone_id'] = $result->fields['zone_id'];
		}

		//ezybiz wants country as a code rather than abbreviation
		$customer_info['country'] = "";

		//assume USA for insurance companies
		$country_code = 223;
		$sql = "SELECT countries_id from geo_country_reference where countries_iso_code_2 = '" . strtoupper($country_code) . "'";
		$db = $GLOBALS['adodb']['db'];
		$result = $db->Execute($sql);
		if ($result && !$result->EOF) {
			$customer_info['geo_country_id'] = $result->fields['countries_id'];
		}

		$customer_info['phone1'] = $ic->get_phone();
		$customer_info['phone1comment'] = "Phone Number";
		$customer_info['phone2'] = "";
		$customer_info['phone2comment'] = "";
		$customer_info['email'] = "";
		$customer_info['is_payer'] = true;
		$function['ezybiz.add_customer'] = array(new xmlrpcval($customer_info,"struct"));
		$ws = new WSWrapper($function);

		// if the remote patient was added make an entry in the local mapping table to that updates can be made correctly
		if (is_numeric($ws->value)) {
			$sql = "REPLACE INTO integration_mapping set id = '" . $db->GenID("sequences") . "', foreign_id ='" . $ws->value . "', foreign_table ='customer', local_id = '" . $ic->get_id() . "', local_table = 'insurance_companies' ";
			$db->Execute($sql) or die ("error: " . $db->ErrorMsg());
		}
*/
              echo "<br>Stub<br>";
	}

}

?>
