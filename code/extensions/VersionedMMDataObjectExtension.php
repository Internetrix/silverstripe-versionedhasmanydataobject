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
		
		
		
	}
	
	
	
	
	/**
	 * Update any requests to show published many many relationship.
	 */
	function augmentSQL(SQLQuery &$query, DataQuery &$dataQuery = null) {
		
		// Don't run on delete queries
		if ($query->getDelete()) return;
		
		//only show published relationship when it's Live mode.
		if($this->owner->hasExtension('Versioned') && Versioned::current_stage() == 'Live'){
			//apply where condition if this query is based on many_many relationship
			if($query->filtersOnFK()){
						
// 				$query->addWhere("\"IsPublished\" = 1");
					
			}
		}
	}
	
	
	
	
	public function augmentWrite(&$manipulation) {
		
		// If we're editing Live, then use (table)_Live instead of (table)
		if(
			$this->owner->hasExtension('Versioned')
			&& Versioned::current_stage() == 'Live'
		) {
			$ManyMany = $this->owner->config()->get('many_many');
			
			$tableList = DB::tableList();
			
			foreach($ManyMany as $componentName => $ManyManyClassName) {
				
				list($parentClass, $componentClass, $parentField, $componentField, $StageManyManyTable) = $this->owner->many_many($componentName);
				
				//check if the many_many component has Live table.
				$LiveManyManyTable = "{$StageManyManyTable}_Live";
				
				if(in_array($LiveManyManyTable, $tableList)){
					//get all stage data
					$StageData = DB::query("SELECT * FROM \"{$StageManyManyTable}\" WHERE \"{$parentField}\" = {$this->owner->ID}");
					
					//delete live data.
					DB::query("DELETE FROM \"{$LiveManyManyTable}\" WHERE \"{$parentField}\" = {$this->owner->ID}");
					
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
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
