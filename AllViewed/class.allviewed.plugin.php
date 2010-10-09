<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['AllVieiwed'] = array(
   'Name' => 'AllVieiwed',
   'Description' => '',
   'Version' => '1.0a',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => array('General.Discussions.ViewAll'),
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://www.tmprod.com/web-development/vanilla.php',
   'License' => 'GNU GPLv3'
);

class AllVieiwedPlugin extends Gdn_Plugin {
   
   
   
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      $Structure = Gdn::Structure();
      $Structure->Table('User')
         ->Column('AllViewed', 'datetime')
         ->Set();

      SaveToConfig('Plugins.AllViewed.Enabled', TRUE);
   }
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      SaveToConfig('Plugins.AllViewed.Enabled', FALSE);
   }
}