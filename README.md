silverstripe-versionedextensions
=======================================

Allows has_many dataobjects on pages to be versioned.

Allows many_many dataobjects on pages to be able to work with Draft and Live mode.

Maintainer Contact
------------------
*  Guy Watson (<guy.watson@internetrix.com.au>)
*  Jason Zhang (<jason.zhang@internetrix.com.au>)

## Requirements

SilverStripe 3.1.8. (Not tested with any other versions)

### Configuration

After installation, make sure you rebuild your database through `dev/build` and run `?flush=all`

#### has_many

You will then need to add the following extensions to any dataobject you require to be versioned.

	private static $extensions = array(
		"Versioned('Stage', 'Live')",
		"VersionedHMDataObjectExtension"
	);
	

For SilverStripe < 3.1.8, the Versioned class does not have the necessary extension hook, you will need to overide the following function in Page.php. 
Please add the following code to Page.php

	public function getIsModifiedOnStage() {
		$isModified = parent::getIsModifiedOnStage();
		$this->extend('getIsModifiedOnStage', $isModified);
		
		return $isModified;
	}

#### many_many

Apply this extension to the dataobject which has Versioned extension (e.g. Page).

	class Page extends SiteTree {
		private static $many_many = array('Slides' => 'Slide');
		
		private static $extensions = array(
			"VersionedMMDataObjectExtension"
		);
	}
		
Apply this extension to the dataobject which belongs to the above dataobject (e.g. Slide).

	class Slide extends DataObject {
		private static $belongs_many_many = array('Pages' => 'Page');
	
		private static $extensions = array(
			"VersionedMMBelongsDataObjectExtension"
		);
	}
	
You have to add the following function in Page. This function is copied from DataObject and modified for supporting versioning.

	public function getManyManyComponents($componentName, $filter = '', $sort = '', $join = '', $limit = '') {
		list($parentClass, $componentClass, $parentField, $componentField, $table) = $this->many_many($componentName);
	
		// If we haven't been written yet, we can't save these relations, so use a list that handles this case
		if(!$this->ID) {
			if(!isset($this->unsavedRelations[$componentName])) {
				$this->unsavedRelations[$componentName] =
				new UnsavedRelationList($parentClass, $componentName, $componentClass);
			}
			return $this->unsavedRelations[$componentName];
		}
		
		if(
			Versioned::current_stage() == 'Live' 
			&& $this->hasExtension('VersionedMMDataObjectExtension')
			&& $componentClass::has_extension('VersionedMMBelongsDataObjectExtension')
		){
			$table .= '_Live';
		}
	
		$result = ManyManyList::create($componentClass, $table, $componentField, $parentField,
				$this->many_many_extraFields($componentName));
		if($this->model) $result->setDataModel($this->model);
	
		// If this is called on a singleton, then we return an 'orphaned relation' that can have the
		// foreignID set elsewhere.
		$result = $result->forForeignID($this->ID);
			
		return $result->where($filter)->sort($sort)->limit($limit);
	}
	
If you want to push all many many relationship data to Live mode, please run 

`dev/build?copymanymanydata2live=all`

Please note : by running `copymanymanydata2live=all`, Live many many relationship tables will be truncated and all Stage data will be copied to Live many many tables.




