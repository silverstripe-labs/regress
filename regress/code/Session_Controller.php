<?php
/**
 * @package regress
 * @subpackage code
 */

/**
 * Dedicated Session Controller to handle the test-sessions requests.
 *
 * This controller is dealing with test-session requests, such as submitting
 * and saving  draft versions of test-sessions.
 */
class Session_Controller extends Controller {

	static $allowed_actions = array(
		'saveperformance',
		'reportdetail',
		'uploadattachment'
	);
	
	function init() {
		parent::init();
		
		if (!Member::currentUser()) {
			return Security::permissionFailure();
		}
				
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR."/jquery-form/jquery.form.js");
		Requirements::javascript("regress/javascript/Session.js");
	}
	
	function reportdetail() {

		if (!Member::currentUser()) {
			return Security::permissionFailure();
		}
		
		$session = $this->TestSessionObj();
		if (!$session) {
			return 'Test report not found.';
		}
		
		if (!$session->getMainTestPlan()->canView()) {
			return Security::permissionFailure();
		}		
		return $this->render();
	}
		
	/**
	 * Returns the test session object of a given ID. The ID is passed in as a
	 * HTTP parameter.
	 * 
	 * @return TestSessionObj|null Instance of the session object.
	 */
	function TestSessionObj() {
		
		if((int)$this->urlParams['OtherID']) {
			$obj = DataObject::get_by_id("TestSessionObj", (int)$this->urlParams['OtherID']);
			
			return $obj;
		}
		return null;
	}
	
	/**
	 * Returns an existing or creates a new test-session object
	 *
	 * If an id is provided, otherwise initiate a new TestSessionObj dataobject.
	 * 
	 * @return TestSessionObj 
	 */
	function prepareTestSessionObj($testSessionData) {
		$session = null;
		if (isset($testSessionData['ID']) && $testSessionData['ID'] != '') {
			$session = DataObject::get_by_id('TestSessionObj',(int)$testSessionData['ID']);
		} else {
			$session = new TestSessionObj();
		}
		return $session;
	}
	
	/**
	 * Returns the step result object for a given test-step of a given session.
	 *
	 * If an id is provided, otherwise initiate a new StepResult dataobject.
	 *
	 * @return StepResult
	 */ 
	function getStepResult($TestSessionObject, $testStepID) {
		$obj = null;
		if (isset($TestSessionObject->ID)) {
			$filter = "TestSessionID = ".$TestSessionObject->ID." AND TestStepID = ".$testStepID;
			$obj = DataObject::get_one('StepResult',$filter);
			
			if ($obj == null) {
				$obj = new StepResult();
			}
		} else {
			$obj = new StepResult();
		}
		return $obj;
	}

	/**
	 * Returns all notes which have been attached to a given test-plan.
	 * Two cases do exist: 
	 * (1) If a session-object for the test plan exist, return all notes stored
	 *     in the session object.
	 * (2) It will return all StepResult instances which have been marked as
	 *     'fail' or any notes of any 'pass' and 'skip' steps.
	 *
	 * @return DataObjectSet
	 */
	function Notes() {
		$planID = (int)$this->urlParams['ID'];

		// If we're viewing one session, then show that session's notes
		if($obj = $this->TestSessionObj()) return $obj->Notes();
		
		// Otherwise, view all unresolved notes
		else return DataObject::get("StepResult", "TestPlanID = $planID AND (Outcome = 'fail' OR (Outcome IN ('pass','skip') AND Note != '' AND Note IS NOT NULL)) 
			AND ResolutionDate IS NULL");
	}
	
	/**
	 * Creates an empty array structure for the save-session action.
	 *
	 * @return array
	 */
	private function createSessionDataArray() {
		// get test session object data
		$testSessionData = array();

		$testSessionData["ID"]          = NULL;
		$testSessionData["Tester"]      = NULL;
		$testSessionData["OverallNote"] = NULL;
		$testSessionData["BaseURL"]     = NULL;
		$testSessionData["Browser"]     = NULL;
		$testSessionData["CodeRevision"]= NULL;
		$testSessionData["TestPlanVersion"]= NULL;
		$testSessionData["NumTestSteps"]  = NULL;
		return $testSessionData;
	}
	
	/**
	 * Form action 'saveperformance'. The website user is able to save the 
	 * results of the test into a session object and closes the current test
	 * run.
	 * After saving the test-result into the database, redirect the user
	 * to the report of the test.
	 *
	 * @return String html page
	 */
	function saveperformance($request) {

		if (!Member::currentUser()) {
			return Security::permissionFailure();
		}

		$responseArray = array(
			'TestSessionObjID' => '',
			'Message'          => ''
		);
		
		// if there's no outcomes was set the redirect to the same page
		if (!isset($_REQUEST['Outcome'])) {
			if (Director::is_ajax()) {
				$responseArray['Message'] = "Test Session not saved (It is empty)";
				return json_encode($responseArray);
			}
			Director::redirectBack();
			return;
		}

		// get test session object data
		$testSessionData = $this->createSessionDataArray();
		
		if (isset($_REQUEST['TestSessionObjID'])) { 
			$testSessionData["ID"] = $_REQUEST['TestSessionObjID'];
		}

		if (isset($_REQUEST['Tester'])) { 
			$testSessionData["Tester"] = $_REQUEST['Tester'];
		}
		
		if (isset($_REQUEST['OverallNote'])) { 
			$testSessionData["OverallNote"] = $_REQUEST['OverallNote'];
		}

		if (isset($_REQUEST['BaseURL'])) { 
			$testSessionData["BaseURL"] = $_REQUEST['BaseURL'];
		}

		if (isset($_REQUEST['Browser'])) { 
			$testSessionData["Browser"] = $_REQUEST['Browser'];
		}

		if (isset($_REQUEST['CodeRevision'])) { 
			$testSessionData["CodeRevision"] = $_REQUEST['CodeRevision'];
		}
		
		if (isset($_REQUEST['TestPlanVersion'])) { 
			$testSessionData["TestPlanVersion"] = $_REQUEST['TestPlanVersion'];
		}
		
		if (isset($_REQUEST['NumTestSteps'])) { 
			$testSessionData["NumTestSteps"] = (int) $_REQUEST['NumTestSteps'];
		}
				
		// default: tests are always performed via test-plans.
		if ($_REQUEST['SessionType'] == 'TestSection') {
			$testSessionData["TestSectionID"] = (int)$_REQUEST['ParentID'];
			$testSessionData["TestPlanID"] = null;
		} else {
			$testSessionData["TestSectionID"] = null;
			$testSessionData["TestPlanID"] = (int)$_REQUEST['ParentID'];
		}
		
		$session = $this->prepareTestSessionObj($testSessionData);
		
		if (!$session->isEditable()) {
			if (Director::is_ajax()) {
				$responseArray['Message'] = "This session is not editable.";
				return json_encode($responseArray);
			}
			Director::redirectBack();			
		}

		// update test-session object
		$session->Tester        = $testSessionData["Tester"];
		$session->OverallNote   = $testSessionData["OverallNote"];
		$session->TestSectionID = $testSessionData["TestSectionID"];
		$session->TestPlanID    = $testSessionData["TestPlanID"];
		$session->BaseURL       = $testSessionData["BaseURL"];
		$session->Browser       = $testSessionData["Browser"];
		$session->CodeRevision  = $testSessionData["CodeRevision"];
		$session->TestPlanVersion = $testSessionData["TestPlanVersion"];
		$session->NumberOfTestSteps = $testSessionData["NumTestSteps"];
		
		if (isset($_REQUEST['action_doSaveSession']) && ($_REQUEST['action_doSaveSession'] == 'Execute')) {
			$session->Status = 'draft';
		} else {
			$session->Status = 'submitted';
		}
		
		// update dataobject in the database
		$session->write();
		
		foreach($_REQUEST['Outcome'] as $testStepID => $outcome) {
			
			$result = $this->getStepResult($session,$testStepID);

			$result->TestStepID    = (int)$testStepID;
			$result->TestPlanID    = (int)$testSessionData["TestPlanID"];
			$result->TestSectionID = (int)$testSessionData["TestSectionID"];
			$result->TestSessionID = (int)$session->ID;

			$result->Severity = '';

			if ($outcome == 'fail') {
				if (isset($_REQUEST['Severity'][$testStepID])) {
					$result->Severity = Convert::raw2sql($_REQUEST['Severity'][$testStepID]);
				}
			}
			
			$result->Outcome = $outcome;
			
			//if ($outcome=='pass') $result->ResolutionDate = date('Y-m-d h:i:s');
			$result->Note = $_REQUEST['Note'][$testStepID];
			$result->write();
		}
			
		if (Director::is_ajax()) {
			$responseArray['TestSessionObjID'] = $session->ID;
			$responseArray['Message']          = "Draft-session saved (".$session->LastEdited.")";
			return json_encode($responseArray);
		}
		
		Director::redirect("session/reportdetail/" . (int)$_REQUEST['ParentID'] . "/" . $session->ID);
	}
	
	/**
	 * This should be only by ajax only. It create a test session (draft) there isn't one yet. 
	 * The client script that called this function must set test session object id as it should in self::saveperformance()
	 * 
	 * This assume file is being uploaded one at a time
	 */
	function uploadattachment() {
		
		// get session object if the id is set in $_REQUEST['TestSessionObjID']
		$testSessionData = array();
		if (isset($_REQUEST['TestSessionObjID']) && is_numeric($_REQUEST['TestSessionObjID'])) { 
			$testSessionData["ID"] = $_REQUEST['TestSessionObjID'];
		}
		$session = $this->prepareTestSessionObj($testSessionData);
		
		// populate session. here we don't need to worry about any other data besides session object id and file being uploaded properly
		$session->Status = 'draft';
		$session->write();
		
		$fileid = '';
		$filename = '';
		$url = '';
		foreach($_FILES['Attachment']['name'] as $testStepID => $value) {
			if($value) {
				$file = new File();
				
				$upload = new Upload();
				$upload->setFile($file);
				
				$validator = new Upload_Validator(); 
				$validator->setAllowedExtensions( array('jpg', 'gif', 'png', 'pdf') );
				$upload->setValidator($validator); 
				
				
				// regenerate file array
				$tmpFile = array();
				$tmpFile['tmp_name'] = $_FILES['Attachment']['tmp_name'][$testStepID];
				$tmpFile['name'] = $_FILES['Attachment']['name'][$testStepID];
				$tmpFile['type'] = $_FILES['Attachment']['type'][$testStepID];
				$tmpFile['size'] = $_FILES['Attachment']['size'][$testStepID];
				
				$upload->load($tmpFile);
				if(!$upload->isError()) {
					$result = $this->getStepResult($session, $testStepID);
					$result->TestStepID = $testStepID;
					$result->TestSessionID = $session->ID;
					
					$result->write(); 
					
					$file->StepResultID = $result->ID;
					$file->write();
					
					$fileid = $file->ID;
					$filename = $file->Name;
					$url = $file->getURL();
				}
				else {
					return $this->prepareResponse('failure', '', 'Please check your file extension. Only JPG, GIF, PNG and PDF files are allowed.');
				}
			}
		}
		
		return $this->prepareResponse('success', $session->ID, '', $fileid, 	$filename, $url);
	}
	
	private function prepareResponse($status, $sessionid = '', $message = '', $fileid = '', $filename = '', $url = '') {
		$resultStringFormat = '<ul id="response"><li class="status">%s</li> <li class="sessionid">%s</li> <li class="message">%s</li> <li class="fileid">%s</li> <li class="filename">%s</li><li class="url">%s</li></ul>';
		
		return sprintf($resultStringFormat, $status, $sessionid, $message, $fileid, $filename, $url);
	}

}