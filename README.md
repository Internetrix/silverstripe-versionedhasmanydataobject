silverstripe-versionedhasmanydataobject
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
	
If you want to push all many many relationship data to Live mode, please run `dev/build?copymanymanydata2live=all`. 

Please note : by running copymanymanydata2live=all, Live many many relationship tables will be truncated and all Stage data will be copied to Live many many tables.




