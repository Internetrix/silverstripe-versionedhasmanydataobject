<?php
/**
 * 
 * @author guy.watson@internetrix.com.au
 * @package versionedhasmanydataobject
 * 
 */
class VersionedHMDataObjectExtension extends DataExtension {
	/**
	 * Publish this Form Field to the live site
	 *
	 * Wrapper for the {@link Versioned} publish function
	 */
	public function doPublish($fromStage, $toStage, $createNewVersion = false) {
		$this->owner->publish($fromStage, $toStage, $createNewVersion);
		
		//the dataobject may also have its own versioned has_many relationships
		$has_many = $this->owner->config()->get('has_many');
		if($has_many){
			foreach($has_many as $key => $value){
				if($value::has_extension('VersionDataObjectExtension')) {
					$relationship 	= $this->owner->getComponents($key);
					$foreignKey 	= $relationship->getForeignKey();
					$live = Versioned::get_by_stage($value, "Live", "\"$value\".\"$foreignKey\" = " . $this->owner->ID );
						
					if($live) {
						foreach($live as $field) {
							$field->doDeleteFromStage('Live');
						}
					}
						
					// publish the draft pages
					if($relationship) {
						foreach($relationship as $field) {
							$field->doPublish('Stage', 'Live');
						}
					}
				}
				
			}
		}
		
	}
	
	/**
	 * Delete this form from a given stage
	 *
	 * Wrapper for the {@link Versioned} deleteFromStage function
	 */
	public function doDeleteFromStage($stage) {
		$this->owner->deleteFromStage($stage);
		
		$has_many = $this->owner->config()->get('has_many');
		if($has_many){
			foreach($has_many as $key => $value){
				if($value::has_extension('VersionDataObjectExtension')) {
					$relationship = $this->owner->getComponents($key);
					if($relationship) {
						foreach($relationship as $field) {
							$field->doDeleteFromStage('Live');
						}
					}
				}
			}
		}
	}
	
	/**
	 * checks wether record is new, copied from Sitetree
	 */
	function isNew() {
		if(empty($this->owner->ID)) return true;
	
		if(is_numeric($this->owner->ID)) return false;
	
		return stripos($this->owner->ID, 'new') === 0;
	}
	
	/**
	 * checks if records is changed on stage
	 * @return boolean
	 */
	public function getIsModifiedOnStage() {
		// new unsaved fields could be never be published
		if($this->owner->isNew()) return false;
	
		$stageVersion = Versioned::get_versionnumber_by_stage($this->owner->ClassName, 'Stage', $this->owner->ID);
		$liveVersion =	Versioned::get_versionnumber_by_stage($this->owner->ClassName, 'Live', $this->owner->ID);
		
		$isModified = ($stageVersion && $stageVersion != $liveVersion);
		
		if(!$isModified){
			$has_many = $this->owner->config()->get('has_many');
			if($has_many){
				foreach($has_many as $key => $value){
					if($value::has_extension('VersionDataObjectExtension')) {
						$relationship = $this->owner->getComponents($key);
						if($relationship) {
							foreach($relationship as $field) {
								if($field->getIsModifiedOnStage()) {
									$isModified = true;
									break;
								}
							}
						}
					}
				}
			}
		}
		
		return $isModified;
	}
	
	public function doRevertToLive(){
		
		$has_many = $this->owner->config()->get('has_many');
		if($has_many){
			foreach($has_many as $key => $value){
				if($value::has_extension('VersionDataObjectExtension')) {
					$relationship = $this->owner->getComponents($key);
					if($relationship) {
						foreach($relationship as $field) {
							$field->doRevertToLive();
							$field->delete();
						}
					}
				}
			}
		}
		
		$oldMode = Versioned::get_reading_mode();
		Versioned::set_reading_mode("Stage.Live");
		//move from live to staging
		if($has_many){
			foreach($has_many as $key => $value){
				if($value::has_extension('VersionDataObjectExtension')) {
					$relationship = $this->owner->getComponents($key);
					if($relationship) {
						foreach($relationship as $field) {
							$field->publish("Live", "Stage", false);
							$field->writeWithoutVersion();
						}
					}
				}
			}
		}
		//now delete the live objects and move what is one stage to live.
		Versioned::set_reading_mode($oldMode);
		
	}
	
	public function doDuplicate(Page $page){
		
		$has_many = $this->owner->config()->get('has_many');
		if($has_many){
			foreach($has_many as $key => $value){
				if($value::has_extension('VersionDataObjectExtension')) {
					$relationship = $this->owner->getComponents($key);
					if($relationship) {
						foreach($relationship as $field) {
							$field->doDuplicate($page);
						}
					}
				}
			}
		}
	
		$newField 			= $this->owner->duplicate();
		$newField->ParentID = $page->ID;
		$newField->write();
	}
	
	public function updateBetterButtonsActions(FieldList $actions){
		
		foreach($actions as $a) {
			if($a instanceof BetterButtonFrontendLinksAction){
				$actions->remove($a);
			}
		}

		$actions->removeByName('BetterButtonFrontendLinksAction');
		$actions->removeByName('frontend-links');
		$actions->removeByName('action_save');
		$actions->removeByName('action_publish');
		$actions->removeByName('action_rollback');
		$actions->removeByName('action_unpublish');
		$actions->removeByName('action_doDelete');
		
		$actions->push(BetterButton_Save::create());
		$actions->push(BetterButton_SaveAndClose::create());
		$actions->push(BetterButton_DeleteDraft::create());
	}
	
}
