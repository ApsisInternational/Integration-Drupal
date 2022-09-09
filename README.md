CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Api
 * Module files
 * Module database tables


INTRODUCTION
------------

Apsis One Drupal integration module.


REQUIREMENTS
------------
Supported versions:
Drupal 8

Required user roles for installation:
admin

Required user roles for using the module:
admin
any custom user role with permissions to add/create/edit blocks/nodes

This module requires no modules outside of Drupal core. To make use of the module
the Apsis One javascript snippet needs to be added to your site. You also needs
to get your Client ID and Client Secret from Apsis One (https://app.apsis.one/).

Supported field types:
 - image
 - link
 - text
 - text_long
 - text_with_summary
 - string
 - string_long
 
 Paragraphs are also supported.


INSTALLATION
------------

 1. Log in to Apsis One and add the site's domain.
 2. Copy the Apsis One tracking script and add to the site.
 3. * Install the Apsis One module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

 1. Configure the module at Administration > Configuration > Apsis One
   (admin/config/services/apsisone). Insert your Client ID and Client Secret. You can also select whether you want to use a select list(suitable when many segments) or checkboxes to select segments. A suggestion is to use https://www.drupal.org/project/chosen for your select lists.
 2. Go to admin/config/services/apsisone/apsisone and add a new Apsis One Configuration (enable a field to be segmented).
 3. Select the entity type for "Entity Type", and provide an administrative
   label.
 4. Next select the bundle for which you wish to enable Apsis One segmentation.
 5. Click "Save" to save your configuration.
 
 
USAGE
-------------
Edit a node that has the field you enabled and you will be able to select which segment the content of the field will be shown to.
You can also configure a block and segment it if you enable segmentation for its block type.


API
-------------
You are able to use the ApsisoneService and its functions in own custom modules as long
as you've configured Client ID and Client Secret. 

For example you can fetch the available segments like this:

    $apsis = new \Drupal\apsisone\ApsisoneService;
    $segments = $apsis->listSegments();
    
    
####Evaluate Profile
Using the evaluateProfile() function you can check if the visitor belongs to segments using these lines of code.
    
    // Instansiate the Apsis One Service
    $apsis = new \Drupal\apsisone\ApsisoneService;
    
    // Get profile
    $profile = $apsis->getApsisOneCookie();
    $evaluate = $apsis->evaluateProfile($existing_segment, $profile);
 
 
 MODULE FILES
 ------------
 .github/workflows/workflow.yml
 config/schema/apsisone_config.schema.yml
 src/Controller/ApsisoneConfigListBuilder.php
 src/Controller/ApsisoneController.php
 src/Entity/ApsisoneConfig.php
 src/Entity/Segmentation.php
 src/Form/AdminSettingsForm.php
 src/Form/ApsisoneConfigDeleteForm.php
 src/Form/ApsisoneConfigForm.php
 src/ApsisoneConfigInterface.php
 src/ApsisoneService.php
 src/SegmentationInterface.php
 src/SegmentationListBuilder.php
 tests/src/Unit/Entity/ApsisoneConfigTest.php
 tests/src/Unit/Entity/SegmentationTest.php
 tests/src/Unit/ApsisoneServiceTest.php
 tests/src/Unit/SegmentationListBuilderTest.php
 apsisone.info.yml
 apsisone.links.action.yml
 apsisone.links.menu.yml
 apsisone.module
 apsisone.permissions.yml
 apsisone.routing.yml
 apsisone.services.yml
 phpunit.xml.dist
 README.md
 
 
  MODULE DATABASE TABLES
  ------------
  segmentation

     CREATE TABLE `segmentation` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
       `uuid` varchar(128) CHARACTER SET ascii NOT NULL,
       `entity_id` int(10) unsigned DEFAULT NULL,
       `type` varchar(255) DEFAULT NULL,
       `segment` longtext,
       `segmented_field` varchar(255) DEFAULT NULL,
       `segmented_field_delta` varchar(255) DEFAULT NULL,
       `segmented_field_match` varchar(255) DEFAULT NULL,
       `created` int(11) DEFAULT NULL,
       `changed` int(11) DEFAULT NULL,
       PRIMARY KEY (`id`),
       UNIQUE KEY `segmentation_field__uuid__value` (`uuid`)
     ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='The base table for segmentation entities.';