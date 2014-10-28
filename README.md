silverstripe-versionedhasmanydataobject
=======================================

Allows has_many dataobjects on pages to be versioned.

Maintainer Contact
------------------
*  Guy Watson (<guy.watson@internetrix.com.au>)

## Requirements

SilverStripe 3.1.6. (Not tested with any other versions)


### Configuration

After installation, make sure you rebuild your database through `dev/build` and run `?flush=all`

You will then need to add the following extensions to any dataobject you require to be versioned.

	private static $extensions = array(
		"Versioned('Stage', 'Live')",
		"IRXDataObjectVersion"
	);
	
You will then need to add the following to each has_many side of the relationship. i.e

	private static $versioned_has_many = array(
		'<RelationshipName>' 	=> '<RelatedObject>'
	);

The module will only work on has_many relationships that are defined in this array. You still need to define the relationship in `$has_many` like normal
	
Unfortuatly because the Versioned class does not have the necessary extension hook, you will need to overide the following function in Page.php. 
Please add the following code to Page.php

	public function getIsModifiedOnStage() {
		$isModified = parent::getIsModifiedOnStage();
		$this->extend('afterGetIsModifiedOnStage', $isModified);
		
		return $isModified;
	}
	
I am in the process to submitting a pull request to add the above hook.