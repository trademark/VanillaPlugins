<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['DiscussionPrefix'] = array(
   'Name' => 'DiscussionPrefix',
   'Description' => 'Allows you to mark certain discussions with a prefix.',
   'Version' => '1.1',
   'RequiredApplications' => array('Vanilla' => '2.0.14'),
   'RegisterPermissions' => array(
      'Vanilla.DiscussionPrefix.Use',
      'Vanilla.DiscussionPrefix.Manage'),
   'SettingsUrl' => '/plugin/discussionprefix',
   'SettingsPermission' => 'Vanilla.DiscussionPrefix.Manage',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://www.tmprod.com/web-development/vanilla.php',
   'License' => 'GNU GPLv2'
);

class DiscussionPrefixPlugin extends Gdn_Plugin {
   
   /**
    * Adds "Prefix" menu option to the Forum menu on the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Forum', 'Discussion Prefix', 'plugin/discussionprefix', 'Vanilla.DiscussionPrefix.Manage');
   }
   
   /**
    * Creates a virtual DiscussionPrefix controller
    */
   public function PluginController_DiscussionPrefix_Create(&$Sender) {
      $Sender->Permission('Vanilla.DiscussionPrefix.Manage');
      $Sender->Title('Discussion Prefix');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
    
   /**
    * Insert checkbox on Discussion Post page (vanilla/views/post/discussion.php)
    */
   public function PostController_DiscussionFormOptions_Handler(&$Sender) {
      $Session = Gdn::Session();
      if ($Session->CheckPermission('Vanilla.DiscussionPrefix.Use'))
         $Sender->EventArguments['Options'] .= '<li>'.$Sender->Form->CheckBox('Prefixed', C('Plugins.DiscussionPrefix.Label'), array('value' => '1')).'</li>';
   }
   
   /**
    * Add prefix to discussion name in single discussion view (vanilla/controllers/class.discussioncontroller.php)
    */
   public function DiscussionController_BeforeDiscussionRender_Handler(&$Sender) {
      if($Sender->Discussion->Prefixed == 1)
         $Sender->Discussion->Name = C('Plugins.DiscussionPrefix.Prefix').' '.$Sender->Discussion->Name;
   }
   
   /**
    * Add prefix to each discussion name in list view
    */
   public function DiscussionsController_BeforeDiscussionName_Handler(&$Sender) {
      if($Sender->EventArguments['Discussion']->Prefixed == 1)
         $Sender->EventArguments['Discussion']->Name = C('Plugins.DiscussionPrefix.Prefix').' '.$Sender->EventArguments['Discussion']->Name;
   }
    
   /**
    * Creates a virtual Index method for the DiscussionPrefix controller
    *
    * Shows the settings for the plugin:
    * - Prefix to show before discussion title
    * - What the checkbox label should be
    */
   public function Controller_Index(&$Sender) {  
      $Sender->AddCssFile('admin.css');
      $Sender->AddSideMenu('plugin/discussionprefix');

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Plugins.DiscussionPrefix.Prefix',
         'Plugins.DiscussionPrefix.Label'
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
      $this->Structure();
      SaveToConfig('Plugins.DiscussionPrefix.Enabled', TRUE);
   }
   
   /**
    * Database structure changes
    *
    * 'Prefixed' column will be bool (1 or 0) to determine 
    * whether discussion gets the prefix
    */
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure->Table('Discussion')
         ->Column('Prefixed', 'tinyint(1)', 0)
         ->Set();
   }
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      SaveToConfig('Plugins.DiscussionPrefix.Enabled', FALSE);
   }
}