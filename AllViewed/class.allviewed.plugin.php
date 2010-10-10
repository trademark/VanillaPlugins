<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['AllViewed'] = array(
   'Name' => 'AllViewed',
   'Description' => 'Allows users to mark all discussions as viewed.',
   'Version' => '1.0b',
   //'RegisterPermissions' => array('General.Discussions.AllVieiwed'),
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://www.tmprod.com/web-development/vanilla.php',
   'License' => 'GNU GPLv2'
);

class AllVieiwedPlugin extends Gdn_Plugin {
   
   public function Base_Render_Before(&$Sender) {
      // Add menu items.
      $Session = Gdn::Session();
      if ($Sender->Menu && $Session->IsValid()) {
         $Sender->Menu->AddLink('AllViewed', T('Mark All Read'), '/discussions/markallviewed');
      }
   }
   
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
    * Modify CountUnreadComments to account for DateAllViewed
    *
    * Required in DiscussionModel->Get() just before the return:
    *    $this->EventArguments['Data'] = $Data;
    *    $this->FireEvent('AfterAddColumns');
    * @link http://vanillaforums.org/discussion/13227
    */
   function DiscussionModel_AfterAddColumns_Handler(&$Sender) {
      if (!C('Plugins.AllViewed.Enabled')) return;
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid()) return;

      // Recalculate New count with user's DateAllViewed   
      $Sender->Data = GetValue('Data', $Sender->EventArguments, '');
      $Result = &$Sender->Data->Result();
		foreach($Result as &$Discussion) {
			if(Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= $Session->User->DateAllViewed) {
				$Discussion->CountUnreadComments = 0; // Default to none
				if($Discussion->CountCommentWatch) {
					$Discussion->CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
				}
			}
		}
   }
   
   /**
    * Update user's AllViewed datetime.
    */
   function UserModel_UpdateAllViewed_Create(&$Sender) {
      if (!C('Plugins.AllViewed.Enabled')) return;
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid()) return;
      
      $UserID = $Session->User->UserID; // Can only activate on yourself
      
      // Validity check (in case get passed UserID from elsewhere some day)
      $UserID = (int) $UserID;
      if (!$UserID) {
         throw new Exception('A valid UserId is required.');
      }
      
      // Update User
      $Sender->SQL->Update('User')
         ->Set('DateAllViewed', Gdn_Format::ToDateTime());
      $Sender->SQL->Where('UserID', $UserID)->Put();
      
      // Update CountComments
      $Sender->SQL->Update('UserDiscussion')
         ->Set('CountComments', 0); // Zero = won't count (kinda hacky but definitely simpler than recounting them all)
      $Sender->SQL->Where('UserID', $UserID)->Put();
      
      // Set in current session
      $Session->User->DateAllViewed = Gdn_Format::ToDateTime();
   }
   
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure->Table('User')
         ->Column('DateAllViewed', 'datetime')
         ->Set();
   }
   
   protected function _Enable() {
      SaveToConfig('Plugins.AllViewed.Enabled', TRUE);
   }
   
   protected function _Disable() {
      RemoveFromConfig('Plugins.AllViewed.Enabled');
   }
}