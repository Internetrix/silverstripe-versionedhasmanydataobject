<?php
/**
 * 
 * @author guy.watson@internetrix.com.au
 * @package versionedhasmanydataobject
 * 
 */
class VersionedMMDataObjectExtension extends DataExtension {
	
	public function augmentDatabase() {
		
		$extensions = null;
		
		// Build any child tables for many_many items
		if($manyMany = $this->owner->uninherited('many_many', true)) {
			$extras = $this->owner->uninherited('many_many_extraFields', true);
			foreach($manyMany as $relationship => $childClass) {
				// Build field list
				$manymanyFields = array(
					"{$this->owner->class}ID" => "Int",
				(($this->owner->class == $childClass) ? "ChildID" : "{$childClass}ID") => "Int",
				);
				if(isset($extras[$relationship])) {
					$manymanyFields = array_merge($manymanyFields, $extras[$relationship]);
				}

				// Build index list
				$manymanyIndexes = array(
					"{$this->owner->class}ID" => true,
				(($this->owner->class == $childClass) ? "ChildID" : "{$childClass}ID") => true,
				);
				
				DB::requireTable("{$this->owner->class}_{$relationship}_Live", $manymanyFields, $manymanyIndexes, true, null,$extensions);
			}
		}
		
		//copy all many many data from stage to live if required
		if(isset($_GET['copymanymanydata2live']) && $_GET['copymanymanydata2live'] == 'all'){
			
			$this->owner->publishManyManyComponents(true);
			
		}
	}
	
	
	/**
	 * write many_many table data into many_many Live table.
	 * 
	 * Please note :
	 * 
	 * By default, $CopyWholeTable is FALSE, only this record's relationship data will be pushed to Live.
	 * If $CopyWholeTable is TRUE, the WHOLE Live many many table data will be replaced by Stage many many data.(use this feature with caution)
	 * 
	 * @param $CopyWholeTable boolean 
	 */
	public function publishManyManyComponents($CopyWholeTable = false) {
		
		// If we're editing Live, then use (table)_Live instead of (table)
		$ManyMany = $this->owner->config()->get('many_many');
		
		$tableList = DB::tableList();
		
		foreach($ManyMany as $componentName => $ManyManyClassName) {
			
			list($parentClass, $componentClass, $parentField, $componentField, $StageManyManyTable) = $this->owner->many_many($componentName);
			
			//check if the many_many component has Live table.
			$LiveManyManyTable = "{$StageManyManyTable}_Live";
			
			if(in_array($LiveManyManyTable, $tableList)){
				//get all stage data
				$StageWhere = ($CopyWholeTable === true) ?  '1' : "\"{$parentField}\" = {$this->owner->ID}";
				$StageData = DB::query("SELECT * FROM \"{$StageManyManyTable}\" WHERE {$StageWhere}");
				
				//delete live data.
				if($CopyWholeTable === true){
					DB::query("TRUNCATE \"{$LiveManyManyTable}\"");
				}else{
					DB::query("DELETE FROM \"{$LiveManyManyTable}\" WHERE \"{$parentField}\" = {$this->owner->ID}");
				}
				
				
				//insert all stage data into Live Many Many table.
				if($StageData->numRecords()){
					$Columns = array();
					$Values = array();
					
					foreach ($StageData as $DataArray){
						if(empty($Columns)){
							$Columns = array_keys($DataArray);
						}
						
						$Values[] = "('" . implode("','", $DataArray) . "')";
					}
					
					$InsertQuery = "INSERT INTO \"{$LiveManyManyTable}\" (\"".implode('","', $Columns)."\") VALUES ";
					
					$InsertQuery .= implode(',', $Values);
					
					DB::query($InsertQuery);
				}
			}
		}
	}
	
	
	/**
	 * Publish many many relationship
	 */
	function onAfterPublish(&$original) {
	
		$this->owner->publishManyManyComponents();
	}
	
}

