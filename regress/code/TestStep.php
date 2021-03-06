<?php
/**
 * @package regress
 * @subpackage code
 */

/**
 * Scenario
 */
class TestStep extends DataObject {
	static $db = array(
		"Step" => "MarkdownText",
		'Sort' => 'Int', 
		"UpdatedViaFrontend" => "Boolean" ,
		"UpdatedTimestamp"   => "Varchar(50)" ,
		"UpdatedByMember" 	 => "Varchar(100)"
	);
	
	static $has_one = array(
		"Parent"    => "TestSection",
		"UpdatedBy" => "Member"
	);
	
	/**
	 * Human-readable singular name
	 * @var string
	 */
	public static $singular_name = 'Scenario';

	/**
	 * Human-readable plural name
	 * @var string
	 */
	public static $plural_name = 'Scenarios';
	
	/**
	 * Returns the test session object of a given ID. The ID is passed in as a
	 * HTTP parameter.
	 * 
	 * @return TestSessionObj|Null Instance of the session object.
	 */
	function SessionStepResult() {
		$obj = null;

		$OtherID = Controller::curr()->urlParams['OtherID'];
		if($OtherID) {
			if(is_numeric($OtherID)) {
				$dataobjectset = DataObject::get("StepResult", "TestStepID = $this->ID AND TestSessionID = $OtherID");
				if ($dataobjectset != null) {
					$obj = $dataobjectset->First();
				}
			}
		}
		return $obj;
	}
	
	function IsOutcomePass() {
		$obj = $this->SessionStepResult();		
		if ($obj) return $obj->Outcome == 'pass';
	}

	function IsOutcomeSkip() {
		$obj = $this->SessionStepResult();		
		if ($obj) return $obj->Outcome == 'skip';
	}

	function IsOutcomeFail() {
		$obj = $this->SessionStepResult();		
		if ($obj) return $obj->Outcome == 'fail';
	}


	function IsSeverity1() {
		$obj = $this->SessionStepResult();		
		if ($obj) return $obj->IsSeverity1();
	}

	function IsSeverity2() {
		$obj = $this->SessionStepResult();		
		if ($obj) return $obj->IsSeverity2();
	}

	function IsSeverity3() {
		$obj = $this->SessionStepResult();		
		if ($obj) return $obj->IsSeverity3();
	}

	function IsSeverity4() {
		$obj = $this->SessionStepResult();		
		if ($obj) return $obj->IsSeverity4();
	}	
	
	/**
	 * Return the text stored in Step formatted as HTML, using Markdown for the
	 * formatting.
	 *
	 * @return string
	 */
	function StepMarkdown() {
		return MarkdownText::render($this->Step);
	}
	
	function KnownIssues() {
		if(is_numeric($this->ID)) {
			return DataObject::get("StepResult", "TestStepID = $this->ID AND Outcome = 'fail' AND Note <> '' AND ResolutionDate IS NULL");
		}
	}
	
	function PassNotes() {
		if(is_numeric($this->ID)) {
			return DataObject::get("StepResult", "TestStepID = $this->ID AND Outcome = 'pass' AND Note <> '' AND ResolutionDate IS NULL");
		}
	}
	
	function SkipNotes() {
		if(is_numeric($this->ID)) {
			return DataObject::get("StepResult", "TestStepID = $this->ID AND Outcome = 'skip' AND Note <> '' AND ResolutionDate IS NULL");
		}
	}
	
	function StepNote() {
		return ( $this->KnownIssues() || $this->PassNotes() || $this->SkipNotes() );
	}
	
	function canDelete() {
		return Permission::check("CMS_ACCESS_CMSMain");
	}
	
	function ParentClassName() {
		return $this->Parent()->ClassName;
	}

	function ParentFeatureID() {
		return $this->Parent()->FeatureID;
	}

}

class TestStep_Controller extends Controller {
	
	static $allowed_actions = array(
		'load',
		'save',
		'delete',
		'add'
	);
	
	/**
	 * This method returns the raw-scenario description which will
	 * be used to populate the textarea field for front-end editing.
	 *
	 * NOTE: this method is used for front-end editing. When the user
	 * does not have edit-permissions, this text should not come up and returns
	 * a 401 error.
	 *
	 * @param HTTPRequest $request
	 * @return string
	 */
	function load($request) {
		
		if (Member::currentUser() == null) {
			$this->getResponse()->setStatusCode(401);
			return TestPlan::$permission_denied_text;
		}
		
		$vars = $request->getVars();
		$stepid_raw = $vars['id'];
		
		$tmp = explode ("_",$stepid_raw);
		if ($tmp[0] == 'scenarioContent') {
			$id       = (int)$tmp[1];
			
			$testStep = DataObject::get_by_id("TestStep", $id);
			$testSection = $testStep->Parent();
			if ($testSection) {
				$testPlan = $testSection->getTestPlan();
				
				if ($testPlan) {
					if (!$testPlan->canEdit()) {
						$this->getResponse()->setStatusCode(401);
						return TestPlan::$permission_denied_text;
					}		
				} else {
					return "Internal error. Please contact system administrator.";
				}
			} else {
				return "Internal error. Please contact system administrator.";
			}
			return $testStep->getField('Step');
		}
	}

	/**
	 * This method saves the scenario description which has been entered
	 * bin the textarea field during the test-run.
	 *
	 * NOTE: this method is used for front-end editing. When the user
	 * does not have edit-permissions, this text should not come up and returns
	 * a 401 error.
	 *
	 * @param HTTPRequest $request
	 * @return string
	 */
	function save($request) {
		
		if (Member::currentUser() == null) {
			$this->getResponse()->setStatusCode(401);
			return TestPlan::$permission_denied_text;
		}
		
		$vars       = $request->postVars();
		$stepid_raw = $vars['id'];
		$value      = $vars['value'];
		
		$tmp = explode ("_",$stepid_raw);
		
		if ($tmp[0] == 'scenarioContent') {
			$id = (int)$tmp[1];
			$testStep = DataObject::get_by_id("TestStep", $id);
			if($testStep) {
				$TestSection = DataObject::get_by_id("TestSection",$testStep->ParentID);
			}
			else return false;
		
			if($TestSection) {
				if (!$TestSection->getTestPlan()->canEdit(Member::currentUser())) {
					$this->getResponse()->setStatusCode(401);
					return TestPlan::$permission_denied_text;
				}
			}		
			
			$testStep->setField('Step',$value );			
			$testStep->setField('UpdatedViaFrontend',true);
			$testStep->setField('UpdatedByMember',Member::currentUser()->getName());
			$testStep->setField('UpdatedTimestamp',SS_Datetime::now()->Nice());

			$testStep->write();
			return $testStep->StepMarkdown();
		}
		
		$this->getResponse()->setStatusCode(500);
		return "Invalid parameters.";
	}
	
	/**
	 * Deletes an step (called from an ajax request)
	 *
	 * @return Boolean
	 */
	function delete(){
		$retValue = false;
		
		if(!isset($this->urlParams['ID']) || $this->urlParams['ID'] == '') {
			return $retValue;
		}

		if (Member::currentUser() == null) {
			$this->getResponse()->setStatusCode(401);
			return TestPlan::$permission_denied_text;
		}
				
		$Step = DataObject::get_by_id("TestStep",(int)$this->urlParams['ID']);
		$TestSection = null;

		if($Step) {
			$TestSection = DataObject::get_by_id("TestSection",$Step->ParentID);
		}
		else return false;
		
		if($TestSection) {
			if ($TestSection->canEdit(Member::currentUser())) {
				$Step->delete();
				$Step->destroy();
				$retValue = true;
			} else {
				$this->getResponse()->setStatusCode(401);
				return TestPlan::$permission_denied_text;
			}
		}
		return $retValue;
	}
	
	/** 
	 * Add a test step after the seleted test step. 
	 * Test step identification is prodivded by the URL parameters (ID).
	 */ 
	function add() {
		if(!isset($this->urlParams['ID']) || $this->urlParams['ID'] == '') return false;
		
		$Feature = DataObject::get_by_id('TestSection',(int)$this->urlParams['ID']);
		$featureID = $Feature->ID;
		$Step = new TestStep();
		$Step->Sort = $this->urlParams['OtherID'];
		$Step->UpdatedViaFrontend = 1;
		$Step->UpdatedByID = Member::currentUserID();
		$Step->ParentID = $featureID;
		$Step->Step = Convert::raw2sql($this->urlParams['ExtraID']);
		$Step->write();
		
		return json_encode(array('content' => $Step->renderWith('TestStep'), 'ID' => $Step->ID));

		//return true;
	}
	

}

?>