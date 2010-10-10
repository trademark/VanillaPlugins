<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['AutoParagraph'] = array(
   'Name' => 'AutoParagraph',
   'Description' => '',
   'Version' => '1.0a',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'SettingsUrl' => '/plugin/authorizenet',
   'SettingsPermission' => 'Vanilla.Settings.Manage',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://www.tmprod.com/web-development/vanilla.php',
   'License' => 'GNU GPLv2'
);

class AutoParagraphPlugin extends Gdn_Plugin {
   
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      SaveToConfig('Plugins.AutoParagraph.Enabled', TRUE);
   }
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      SaveToConfig('Plugins.AutoParagraph.Enabled', FALSE);
   }
}