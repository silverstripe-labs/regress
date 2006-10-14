<?php

class TestSession extends DataObject {
	static $db = array(
	
	);
	static $has_one = array(
		"TestPlan" => "TestPlan",
	);
	static $has_many = array(
		"Results" => "StepResult",
	);
	
	function Passes() {
		return DataObject::get("StepResult", "TestSessionID = $this->ID AND Outcome = 'pass'");
	}

	function Failures() {
		return DataObject::get("StepResult", "TestSessionID = $this->ID AND Outcome = 'fail'");
	}
	
	function NumPasses() {
		return DB::query("SELECT COUNT(*) FROM StepResult WHERE TestSessionID = $this->ID AND Outcome = 'pass'")->value();
	}
	function NumFailures() {
		return DB::query("SELECT COUNT(*) FROM StepResult WHERE TestSessionID = $this->ID AND Outcome = 'fail'")->value();
	}
}

?>