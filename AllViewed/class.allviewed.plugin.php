<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['AllVieiwed'] = array(
   'Name' => 'AllViewed',
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
   'License' => 'GNU GPLv2'
);

class AllVieiwedPlugin extends Gdn_Plugin {
   
   /**
    * Allows user to mark all discussions as viewed.
    */
   function DiscussionsController_MarkAllViewed_Create(&$Sender) {
      $UserModel = Gdn::UserModel();
      $UserModel->UpdateAllViewed();
      
      $Sender->RedirectUrl = Url('discussions');
      $Sender->StatusMessage = T('All discussed marked as viewed.');
      $Sender->Render();
   }
   
   /**
    * Modify UnreadCommentCount to account for DateAllViewed
    *
    * Required in DiscussionModel->Get() just before the return:
    * $this->EventArguments['Data'] = $Data;
    * FireEvent('AfterAddColumns') ;
    * @link http://vanillaforums.org/discussion/13227
    */
   function DiscussionModel_AfterAddColumns_Handler(&$Sender) {
      if (!C('Plugins.AllViewed.Enabled'))
         return;
      
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid())
         return;
         
      // Get user's DateAllViewed
      
      // Recalculate New count with user's DateAllViewed   
      $Sender->Data = GetValue('Data', $Sender->EventArguments, '');
      
   }
   
   /**
    * Update user's AllViewed datetime.
    */
   function UserModel_UpdateAllViewed_Create(&$Sender) {
      if (!C('Plugins.AllViewed.Enabled'))
         return;
      
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid())
         return;
      
      $UserID = $Session->User->UserID; // Can only activate on yourself
      
      // Validity check (in case get passed UserID from elsewhere some day)
      $UserID = (int) $UserID;
      if (!$UserID) {
         throw new Exception('A valid UserId is required.');
      }

      $Sender->SQL->Update('User')
         ->Set('DateAllViewed', Gdn_Format::ToDateTime());

      $Sender->SQL->Where('UserID', $UserID)->Put();
      
      // Set in current session?
      
   }
   
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      $Structure = Gdn::Structure();
      $Structure->Table('User')
         ->Column('DateAllViewed', 'datetime')
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