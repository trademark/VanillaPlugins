<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['DiscussionPrefix'] = array(
   'Name' => 'DiscussionPrefix',
   'Description' => 'Allows you to mark certain discussions with a prefix.',
   'Version' => '1.0a',
   'RegisterPermissions' => array(
      'Plugins.DiscussionPrefix.Prefix.Add',
      'Plugins.DiscussionPrefix.Prefix.Remove',
      'Plugins.DiscussionPrefix.Prefix.Manage'),
   'SettingsUrl' => '/plugin/discussionprefix',
   'SettingsPermission' => 'Plugins.DiscussionPrefix.Prefix.Manage',
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
     * Insert checkbox on Discussion Post page (vanilla/views/post/discussion.php)
     */
    public function PostController_BeforeFormButtons_Handler(&$Sender) {
      $Session = Gdn::Session();
      $Options = '';
      if ($Session->CheckPermission('Plugins.DiscussionPrefix.Prefix.Add'))
         $Options .= '<li>'.$Sender->Form->CheckBox('Prefixed', C('Plugins.AuthorizeNet.Label'), array('value' => '1')).'</li>';
      if($Options != '')
         echo '<ul class="PostOptions">' . $Options .'</ul>';
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