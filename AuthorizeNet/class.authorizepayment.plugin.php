<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['AuthorizeNet'] = array(
   'Name' => 'Authorize.net Payments',
   'Description' => 'Provides a model for sending payments through the Authorize.net API.',
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => array(
      'Plugins.AuthorizeNet.Payment.View',
      'Plugins.AuthorizeNet.Payment.Edit',
      'Plugins.AuthorizeNet.Payment.Manage'),
   'SettingsUrl' => '/plugin/authorizenet',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://tmprod.com',
   'License' => 'GNU GPLv3'
);

Gdn_LibraryMap::SafeCache('library','class.paymentmodel.php',dirname(__FILE__).DS.'models/class.paymentmodel.php');

class AuthorizeNetPlugin extends Gdn_Plugin {
   
   /**
    * Adds "Payment" menu option to the Forum menu on the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Payment', 'Payment');
      $Menu->AddLink('Payment', 'Settings', 'plugin/authorizenet', 'Plugins.AuthorizeNet.Payment.Edit');
      $Menu->AddLink('Payment', 'Payments', 'plugin/authorizenet/payments', 'Plugins.AuthorizeNet.Payment.View');
   }
   
   /**
    * Creates a virtual AuthorizeNet controller
    */
   public function PluginController_AuthorizeNet_Create(&$Sender) {
      $Sender->Permission('Plugins.AuthorizeNet.Payment.Edit');
      $Sender->Title('Authorize.net');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
    }
    
    /**
    * Creates a virtual Index method for the AuthorizeNet controller
    * Shows the settings for Authorize.net
    */
    public function Controller_Index(&$Sender) {  
      $Sender->AddCssFile($this->GetWebResource('css/payment.css'));
      $Sender->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      $Sender->AddCssFile('admin.css');
      $Sender->AddSideMenu('plugin/authorizenet');
            
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Plugins.AuthorizeNet.LoginID',
         'Plugins.AuthorizeNet.TransactionKey'
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
      
      $Sender->Title(T('Authorize.net Settings'));
      $Sender->Render($this->GetView('settings.php'));
   }
   
   /**
    * Creates a virtual Payments method for the AuthorizeNet controller
    * Lists all Payments logged by the plugin
    */
   public function Controller_Payments(&$Sender) {
      $Sender->Permission('Plugins.AuthorizeNet.Payment.View');
      $Sender->AddCssFile($this->GetWebResource('css/payment.css'));
      $Sender->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      $Sender->AddCssFile('admin.css');
      $Sender->AddSideMenu('plugin/authorizenet/payments');
      
      $PayentModel = new PaymentModel();
      $Sender->PaymentData = $PayentModel->Get();

      $Sender->Render($this->GetView('payments.php'));
   }
      
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      $Structure = Gdn::Structure();
      $Structure->Table('Payment')
         ->PrimaryKey('PaymentID')
         ->Column('UserID', 'int')
         ->Column('TransactionID', 'varchar(255)') # From Authorize.net
         ->Column('Description', 'varchar(255)', TRUE)
         ->Column('Amount', 'decimal(10,2)', 0)
         ->Column('DateInserted', 'datetime')
         ->Column('InsertUserID', 'int', FALSE, 'key') # 0 = System
         ->Set();

      SaveToConfig('Plugins.AuthorizeNet.Enabled', TRUE);
   }
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      SaveToConfig('Plugins.AuthorizeNet.Enabled', FALSE);
   }
}

?>