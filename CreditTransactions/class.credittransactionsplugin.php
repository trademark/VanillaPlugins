<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['CreditTransactions'] = array(
   'Name' => 'Credit Transactions',
   'Description' => 'Lets you assign credits to users as an in-house currency.',
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => array(
   'Plugins.CreditTransactions.Transaction.View',
   'Plugins.CreditTransactions.Transaction.Edit'),
   'SettingsUrl' => '/plugin/credittransactions',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://tmprod.com',
   'License' => 'GNU GPLv3'
);

/** @todo Make these options in the Dashboard */
define('CREDITS_PER_DOLLAR', 10);
define('CREDITS_PER_TEXT', 2);
define('CREDITS_PER_COUPON', 4);

Gdn_LibraryMap::SafeCache('library','class.credittransactionmodel.php',dirname(__FILE__).DS.'models/class.credittransactionmodel.php');

class CreditTransactionsPlugin extends Gdn_Plugin {
   
   /**
    * Adds "Payment" menu option to the Forum menu on the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Credits', 'Credits');
      $Menu->AddLink('Credits', 'Ledger', 'plugin/credittransactions', 'Plugins.CreditTransactions.Transaction.View');
      //$Menu->AddLink('Credits', 'Ledger', 'plugin/credittransactions/ledger', 'Plugins.AuthorizeNet.Payment.View');
   }
   
   /**
    * Creates a virtual CreditTransactions controller
    */
   public function PluginController_CreditTransactions_Create(&$Sender) {
      $Sender->Permission('Plugins.CreditTransactions.Transaction.View');
      $Sender->Title('Credit Transactions');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
    }
    
    /**
    * Creates a virtual CreditTransactions controller
    */
   public function ProfileController_BuyCredits_Create(&$Sender) {
      $Sender->Title('Buy Credits');
      $Sender->Form = new Gdn_Form();
      $Sender->GetUserInfo($Session->User->UserID);
      if ($Sender->Form->AuthenticatedPostBack()) {
         $Credits = ArrayValue('Credits', $Sender->Form->FormValues(), '');
         Redirect('buy/credits/'.$Credits);
      }
      //$this->Dispatch($Sender, $Sender->RequestArgs);
      $Sender->Render($this->GetView('buy.php'));
    }
   
   /**
    * Settings for credit transactions
    * plugin/credittransactions
    */
   public function Controller_Index(&$Sender) {
      $Sender->AddCssFile($this->GetWebResource('css/credittransactions.css'));
      $Sender->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      $Sender->AddCssFile('admin.css');
      $Sender->AddSideMenu('plugin/credittransactions');

      $TransactionModel = new CreditTransactionModel();
      $Sender->TransactionData = $TransactionModel->Get();
      
      $Sender->Render($this->GetView('transactions.php'));
   }

   /**
    * Create a new transaction
    * plugin/credittransactions/add
    */
   public function Controller_Add(&$Sender) {
      $Sender->Permission('Plugins.CreditTransactions.Transaction.View');
      $Sender->AddCssFile($this->GetWebResource('css/credittransactions.css'));
      $Sender->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      $Sender->AddCssFile('admin.css');
      $Sender->AddSideMenu('plugin/credittransactions/add');
      
      $Sender->CreditTransactionModel = new CreditTransactionModel();
      $Sender->Form->SetModel($Sender->CreditTransactionModel);
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $UserModel = new UserModel();
         $UserID = 0;
         $Name = $Sender->Form->GetFormValue('User', '');
         if (trim($Name) != '') {
            $User = $UserModel->GetByUsername(trim($Name));
            if (is_object($User))
               $UserID = $User->UserID;
         }
         if($UserID > 0) { # Good user, save
            $Sender->Form->SetFormValue('UserID', $UserID);
            $TransactionID = $Sender->Form->Save($Sender->CreditTransactionModel);
            if ($TransactionID !== FALSE)
               Redirect('plugin/credittransactions');
            else 
               $Sender->StatusMessage = T('Failed to add transaction.');
         }
         else { # No user
            $Sender->StatusMessage = T('User &ldquo;'.$Name.'&rsquo; was not found.');
         }
      }
      $Sender->Render($this->GetView('add.php'));
   }
   
   /**
    * Add "Buy Credits" option to profile
    */
   public function ProfileController_AfterAddSideMenu_Handler(&$Sender) {
      $Session = Gdn::Session();
      if ($Session->IsValid() && $Session->UserID == $Sender->User->UserID) {
         $SideMenu = $Sender->EventArguments['SideMenu'];
         $SideMenu->AddLink('Options', T('Buy Credits'), '/profile/buycredits', 'Garden.SignIn.Allow', array('class' => ''));
         $Sender->EventArguments['SideMenu'] = $SideMenu;
      }
   }
   
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      $Structure = Gdn::Structure();
      $Structure->Table('CreditTransaction')
         ->PrimaryKey('TransactionID')
         ->Column('UserID', 'int', FALSE, 'key')
         ->Column('Description', 'varchar(255)', TRUE)
         ->Column('Credits', 'int', 0)
         ->Column('DateInserted', 'datetime')
         ->Column('InsertUserID', 'int', FALSE, 'key')
         ->Set();
         
      $Structure->Table('User')
         ->Column('Credits', 'int', '0')
         ->Set();

      SaveToConfig('Plugins.CreditTransactions.Enabled', TRUE);
   }
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      SaveToConfig('Plugins.CreditTransactions.Enabled', FALSE);
   }
}

?>