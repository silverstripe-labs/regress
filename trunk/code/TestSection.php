<?php

class TestSection extends Page {
	static $allowed_children = array("TestSection");
	static $default_child = "TestSection";
	static $can_be_root = false;
		
	static $has_many = array(
		"Steps" => "TestStep",
	);
	
	function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab("Root.Content.Main", new TableField("Steps", "TestStep", 
			array("Step" => "Test Steps"), array("Step" => 'TextareaField($fieldName, $fieldTitle, 2)'), "ParentID", $this->ID));
		
		return $fields;
	}
}

class TestSection_Controller extends Page_Controller {
	
}
?>