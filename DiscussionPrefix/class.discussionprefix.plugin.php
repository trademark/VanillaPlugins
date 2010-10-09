<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['DiscussionPrefix'] = array(
   'Name' => 'DiscussionPrefix',
   'Description' => '',
   'Version' => '1.0a',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => array(
      'Plugins.DiscussionPrefix.Prefix.Add',
      'Plugins.DiscussionPrefix.Prefix.Remove',
      'Plugins.DiscussionPrefix.Prefix.Manage'),
   'SettingsUrl' => '/plugin/discussionprefix',
   'SettingsPermission' => 'Plugins.DiscussionPrefix.Prefix.Manage',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://www.tmprod.com/web-development/vanilla.php',
   'License' => 'GNU GPLv3'
);

class DiscussionPrefixPlugin extends Gdn_Plugin {
   
   /**
    * Adds "Prefix" menu option to the Forum menu on the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Forum Settings', 'Discusion Prefix', 'plugin/discussionprefix', 'Plugins.DiscussionPrefix.Prefix.Manage');
   }
   
   /**
    * Creates a virtual DiscussionPrefix controller
    */
   public function PluginController_DiscussionPrefix_Create(&$Sender) {
      $Sender->Permission('Plugins.DiscussionPrefix.Prefix.Manage');
      $Sender->Title('Discussion Prefix');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
    }
    
    /**
    * Creates a virtual Index method for the DiscussionPrefix controller
    * Shows the settings for the plugin
    */
    public function Controller_Index(&$Sender) {  
      //$Sender->AddCssFile($this->GetWebResource('css/payment.css'));
      //$Sender->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      $Sender->AddCssFile('admin.css');
      $Sender->AddSideMenu('plugin/discussionprefix');

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Plugins.AuthorizeNet.Prefix',
         'Plugins.AuthorizeNet.Label'
      ));
      
      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         $Saved = $Sender->Form->Save();
         if($Saved) {
            $Sender->StatusMessage = T("Your changes have been saved.");
         }
      }
      
      $Sender->Title(T('Discussion Prefix Settings'));
      $Sender->Render($this->GetView('settings.php'));
   }

   /**
    * 1-Time on Enable
    */
   public function Setup() {
      SaveToConfig('Plugins.DiscussionPrefix.Enabled', TRUE);
   }
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      SaveToConfig('Plugins.DiscussionPrefix.Enabled', FALSE);
   }
}