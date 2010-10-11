<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

$PluginInfo['AllViewed'] = array(
   'Name' => 'AllViewed',
   'Description' => 'Allows users to mark all discussions as viewed.',
   'Version' => '1.0b',
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
         $Sender->Menu->AddLink('AllViewed', T('Mark All Viewed'), '/discussions/markallviewed');
      }
   }
   
   /**
    * Allows user to mark all discussions as viewed.
    */
   function DiscussionsController_MarkAllViewed_Create(&$Sender) {
      $UserModel = Gdn::UserModel();
      $UserModel->UpdateAllViewed();
      Redirect('discussions');
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
			if(Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= Gdn_Format::ToTimestamp($Session->User->DateAllViewed)) {
				$Discussion->CountUnreadComments = 0; // 
			}
			elseif($Discussion->CountCommentWatch == 0) {
			   $Discussion->CountCommentWatch = -1; // hack around "incomplete comment count" logic in WriteDiscussion
			   $Discussion->CountUnreadComments = $Discussion->CountComments;
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
         ->Set('CountComments', 0); // Hack to avoid massive update query
      $Sender->SQL->Where('UserID', $UserID)->Put();
      
      // Set in current session
      $Session->User->DateAllViewed = Gdn_Format::ToDateTime();
   }
   
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      $this->Structure();
      SaveToConfig('Plugins.AllViewed.Enabled', TRUE);
   }
   
   /**
    * Database changes
    */
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure->Table('User')
         ->Column('DateAllViewed', 'datetime')
         ->Set();
   }
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      RemoveFromConfig('Plugins.AllViewed.Enabled');
   }
}