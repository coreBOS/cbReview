<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';
require_once 'include/utils/utils.php';
require_once 'modules/com_vtiger_workflow/include.inc';
require_once 'include/events/include.inc';
require_once 'modules/com_vtiger_workflow/VTWorkflowManager.inc';
require_once 'modules/com_vtiger_workflow/VTTaskManager.inc';

class cbReview extends CRMEntity {
	public $table_name = 'vtiger_cbreview';
	public $table_index= 'cbreviewid';
	public $column_fields = array();

	/** Indicator if this is a custom module or standard module */
	public $IsCustomModule = true;
	public $HasDirectImageField = false;
	public $moduleIcon = array('library' => 'standard', 'containerClass' => 'slds-icon_container slds-icon-standard-approval', 'class' => 'slds-icon', 'icon'=>'approval');

	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = array('vtiger_cbreviewcf', 'cbreviewid');
	// related_tables variable should define the association (relation) between dependent tables
	// FORMAT: related_tablename => array(related_tablename_column[, base_tablename, base_tablename_column[, related_module]] )
	// Here base_tablename_column should establish relation with related_tablename_column
	// NOTE: If base_tablename and base_tablename_column are not specified, it will default to modules (table_name, related_tablename_column)
	// Uncomment the line below to support custom field columns on related lists
	// public $related_tables = array('vtiger_MODULE_NAME_LOWERCASEcf' => array('MODULE_NAME_LOWERCASEid', 'vtiger_MODULE_NAME_LOWERCASE', 'MODULE_NAME_LOWERCASEid', 'MODULE_NAME_LOWERCASE'));

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	public $tab_name = array('vtiger_crmentity', 'vtiger_cbreview', 'vtiger_cbreviewcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	public $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_cbreview'   => 'cbreviewid',
		'vtiger_cbreviewcf' => 'cbreviewid',
	);

	/**
	 * Mandatory for Listing (Related listview)
	 */
	public $list_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Review No'=> array('cbreview' => 'reviewitno'),
		'Why'=> array('cbreview' => 'whyreview'),
		'Who'=> array('cbreview' => 'whoreview'),
		'When'=> array('cbreview' => 'whenreview'),
		'What'=> array('cbreview' => 'whatreview'),
		'Result Type'=> array('cbreview' => 'reviewresulttype'),
		'Assigned To' => array('crmentity' => 'smownerid')
	);
	public $list_fields_name = array(
		/* Format: Field Label => fieldname */
		'Review No'=> 'reviewitno',
		'Why'=> 'whyreview',
		'Who'=> 'whoreview',
		'When'=> 'whenreview',
		'What'=> 'whatreview',
		'Result Type'=> 'reviewresulttype',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	public $list_link_field = 'reviewitno';

	// For Popup listview and UI type support
	public $search_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Review No'=> array('cbreview' => 'reviewitno'),
		'Why'=> array('cbreview' => 'whyreview'),
		'Who'=> array('cbreview' => 'whoreview'),
		'When'=> array('cbreview' => 'whenreview'),
		'What'=> array('cbreview' => 'whatreview'),
		'Result Type'=> array('cbreview' => 'reviewresulttype'),
		'Assigned To'=> array('cbreview' => 'smownerid')
	);
	public $search_fields_name = array(
		/* Format: Field Label => fieldname */
		'Review No'=> 'reviewitno',
		'Why'=> 'whyreview',
		'Who'=> 'whoreview',
		'When'=> 'whenreview',
		'What'=> 'whatreview',
		'Result Type'=> 'reviewresulttype',
		'Assigned To'=> 'assigned_user_id'
	);

	// For Popup window record selection
	public $popup_fields = array('reviewitno');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	public $sortby_fields = array();

	// For Alphabetical search
	public $def_basicsearch_col = 'reviewitno';

	// Column value to use on detail view record text display
	public $def_detailview_recname = 'reviewitno';

	// Required Information for enabling Import feature
	public $required_fields = array('reviewitno'=>1);

	// Callback function list during Importing
	public $special_functions = array('set_import_assigned_user');

	public $default_order_by = 'reviewitno';
	public $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = array('createdtime', 'modifiedtime', 'reviewitno');

	public function save_module($module) {
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id, $module);
		}
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param string Module name
	 * @param string Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	public function vtlib_handler($modulename, $event_type) {
		if ($event_type == 'module.postinstall') {
			// Handle post installation actions
			$modAccounts=Vtiger_Module::getInstance('Accounts');
			$modContacts=Vtiger_Module::getInstance('Contacts');
			$modQt=Vtiger_Module::getInstance('Quotes');
			$modPot=Vtiger_Module::getInstance('Potentials');
			$modDoc=Vtiger_Module::getInstance('Documents');
			$modRev=Vtiger_Module::getInstance('cbReview');

			if ($modAccounts) {
				$modAccounts->setRelatedList($modRev, 'cbReview', array('ADD'), 'get_dependents_list');
			}
			if ($modContacts) {
				$modContacts->setRelatedList($modRev, 'cbReview', array('ADD'), 'get_dependents_list');
			}
			if ($modQt) {
				$modQt->setRelatedList($modRev, 'cbReview', array('ADD'), 'get_dependents_list');
			}
			if ($modPot) {
				$modPot->setRelatedList($modRev, 'cbReview', array('ADD'), 'get_dependents_list');
			}
			if ($modDoc) {
				$modPot->setRelatedList($modRev, 'cbReview', array('ADD'), 'get_dependents_list');
			}

			$this->setModuleSeqNumber('configure', $modulename, 'review-', '0000001');
			//Workflows
			global $adb;
			$workflowManager = new VTWorkflowManager($adb);
			$taskManager = new VTTaskManager($adb);
			//Review workflow when review is created
			$reviewWorkflow = $workflowManager->newWorkFlow("cbReview");
			$reviewWorkflow->test = '[{"fieldname":"whatreview","operation":"is not empty","value":"true:boolean"}]';
			$reviewWorkflow->description = "Creating comments when making a review";
			$reviewWorkflow->executionCondition = VTWorkflowManager::$ON_FIRST_SAVE;
			$reviewWorkflow->defaultworkflow = 1;
			$workflowManager->save($reviewWorkflow);
			$task = $taskManager->createTask('CBUpsertTask', $reviewWorkflow->id);
			$task->active = true;
			$task->summary = 'Comment creation';
			$task->field_value_mapping = '[{"fieldname":"assigned_user_id","valuetype":"expression","value":"getCurrentUserId()","fieldmodule":"ModComments"},'
                		.'{"fieldname":"related_to","valuetype":"fieldname","value":"whatreview","fieldmodule":"ModComments"},'
                		.'{"fieldname":"commentcontent","valuetype":"expression","value":"concat(\'<a href=\"index.php?module=cbReview&action=DetailView&record=\',getCRMIDFromWSID(id),\'\">Hoy se ha hecho una revisión<\/a>\');","fieldmodule":"ModComments"}]';
			$task->upsert_module = 'ModComments';
			$task->test = '';
			$task->reevaluate = 0;
			$taskManager->saveTask($task);
		} elseif ($event_type == 'module.disabled') {
			// Handle actions when this module is disabled.
		} elseif ($event_type == 'module.enabled') {
			// Handle actions when this module is enabled.
		} elseif ($event_type == 'module.preuninstall') {
			// Handle actions when this module is about to be deleted.
		} elseif ($event_type == 'module.preupdate') {
			// Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// Handle actions after this module is updated.
		}
	}
}
?>
