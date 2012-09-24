## Goals of the project.

As developers we hate stuffing a site into a content management system. It is not natural to us.  We are always altering our libraries just to work with different systems. We are learning the different functions that require a CMS to work. We are dealing with the custom routing rules of the particular CMS. Cloudmanic CMS lets you develop the way you want to develop. Cloudmanic CMS gives you a robust admin panel on top of your code base. 

Cloudmanic CMS has a number of goals.....

* The CMS can be an afterthought. Build your site then add the CMS. 

* Default storage of assets in 3rd party services like Amazon s3 and Rackspace Cloudfiles (as well as local storage).

* Asset manipulation. Upload and Crop your images on the fly.

* Scale. A traditional CMS does not easily scale. Scale your apps anyway you want and Cloudmanic CMS will come along for the ride.

* Easy, fast, simple. Cloudmanic CMS does not do it all. But the things we do we do very well.

* No complex database tables. Build your own tables then connect the CMS. Never again will you have to dig into a cryptic database schema to understand how your data is really being stored. 

## Requirements

* Codeigniter 2.1.x
* php5.3+
* gd
* imagemagick
* https://github.com/cloudmanic/wideimage-ci 
 
## Installing 

* Set CMS version constant in constants.php - define('CMSVERSON', '0.7.0');

* Make sure you have a working database in database.php

* Follow the "setting up config.php" instructions below.

* Follow the "setting up javascript" instructions below.

* Follow the "setting up auto loader" instructions below.

* Config ../application/sparks/cloudmanic-cms/CMS-VERSION-GOES-HERE/config/sigcms.php

* Create a link from your document root (called "cms") to the asset folder in the sparks dir. (ln -s ../application/sparks/cloudmanic-cms/0.8.0/assets/ cms)

* Make sure you have mod rewrite removing your index.php. $config['index_page'] should be blank. ($config['index_page'] = '';)
 
* Add `define('CMSVERSON', '0.9.0');` to config/constants.php
 
## Setting up the javascript 

Link it back to the assets folder.

```
ln -s ../application/sparks/cloudmanic-cms/CMS-VERSION-GOES-HERE/assets/ cms
```

## Setting Up The Auto Loader

```
/*
| -------------------------------------------------------------------
|  Native Auto-load
| -------------------------------------------------------------------
| 
| Nothing to do with cnfig/autoload.php, this allows PHP autoload to work
| for base controllers and some third-party libraries.
|
*/
function __autoload($class)
{
	if((strpos($class, 'CI_') !== 0) && (strpos($class, 'MY_') !== 0))
	{
		switch($class)
		{			
			case 'CMS_Controller':		
				include_once(SPARKPATH . 'cloudmanic-cms/' . CMSVERSON . '/libraries/'. $class . EXT);
			break;
			
			default:
				@include_once(APPPATH . 'core/'. $class . EXT);	
			break;
		}
	}
}
```

## Setting up the control panel. 

Create a controller name "cp.php".

```
<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

class Cp extends CMS_Controller { }

/* End File */
```


## Info on Buckets.

* A column with the keyword 'Format' is reserved for formatting textareas and will be ignored in the add / edit.

## Setting up relations.

$d[] = array('table' => 'BlogCategories', 'type' => 'checked', 'name' => 'Categories');
$d[] = array('table' => 'BlogLabels', 'type' => 'tags', 'name' => 'Labels');
echo json_encode($d);

## Custom labels

$q['BlogAuthorId'] = 'Author';
echo json_encode($q);

## Setting up custom fields 

We currently support.

* system-textarea-style: adds a format switch at the bottom of a text area.


We install these fields by adding a key / value match with json in the BucketsFields column

```
{"GalleryImage":{"type":"cms-image-crop","target-width":"120","target-height":"","target-aspect":"1.3"}}
```

## Modifying the default listview screen.

DB Col = BucketsListview

```
{ 
	"joins": [
		{ "table": "Categories", "left": "PhotographyCategory", "right": "CategoriesId", "type": "left" }
	],
	"columns": { "PhotographyTitle": "Name", "CategoriesTitle": "Category", "PhotographyForSale": "For Sale", "PhotographyStatus": "Status", "CreateDateFormat1": "Created" }
}
```