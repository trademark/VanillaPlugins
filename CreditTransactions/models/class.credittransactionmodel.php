<?php if (!defined('APPLICATION')) exit();

class CreditTransactionModel extends Gdn_Model {
   
   /**
    * Class constructor.
    */
   public function __construct() {
      parent::__construct('CreditTransaction');
   }
      
   /**
    * Get a single transaction or all.
    */
   public function Get($TransactionID = '') {
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      
      $SQL->Select('c.*')
         ->Select('u.Name', '', 'Username')
         ->From('CreditTransaction c')
         ->Join('User u', 'c.UserID = u.UserID', 'left');
         
      if(is_numeric($TransactionID) && $TransactionID > 0)
         $SQL->Where('TransactionID', $TransactionID);
      
      $SQL->OrderBy('c.DateInserted','desc');
      
      if(is_numeric($TransactionID) && $TransactionID > 0)
         return $SQL->Get()->FirstRow();
      else
         return $Data = $SQL->Get();
   }
   
   /**
    * Create a new credit transaction in the ledger.
    */
   public function Transact($UserID, $Credits, $Description = '') {
      $Session = Gdn::Session();
      $InsertUser = ($Session->IsValid()) ? $Session->User->UserID : 0;
      if(is_numeric($UserID) && $UserID > 0 && is_numeric($Credits)) {
         if(!$this->HasEnoughCredits($UserID, $Credits))
            return false;
         // Record transaction
         $TransactionID = $this->SQL->Insert($this->Name, array(
            'UserID' => $UserID,
            'Credits' => $Credits,
            'Description' => $Description,
            'DateInserted' => date('Y-m-d H:i:s'),
            'InsertUserID' => $InsertUser
         ));
         // Update user's credit total
         $this->UpdateUser($UserID, $Credits);
         return true;
      }
   }
   
   /**
    * Update user's credit total
    */
   protected function UpdateUser($UserID = 0, $Credits = 0) {
      $this->SQL->Update('User')
         ->Set('Credits', 'Credits + '.$Credits, FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }
   
   /**
    * Get user's credit total
    */
   public function GetAvailable($UserID = 0) {
      $Data = $this->SQL->Select('Credits')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Get()->FirstRow();
      return $Data->Credits;
   }

   /**
    * Check if user has enough credits to meet obligation
    */
   public function HasEnoughCredits($UserID, $Credits = 0) {
      if($Credits < 0) {
         $Available = $this->GetAvailable($UserID);
         if($Available < abs($Credits)) {
            return false;
         }
      }
      return true;
   }
   
   /**
    * Creates a transaction
    * No editing! This is a one-way ticket to a new user credit total
    */
   public function Save($FormPostValues) {
      $Session = Gdn::Session();
            
      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Credits', 'Required');
      $this->Validation->ApplyRule('Credits', 'Integer');
      $this->Validation->ApplyRule('Description', 'Required');      
      $this->AddInsertFields($FormPostValues);
            
      $UserID = ArrayValue('UserID', $FormPostValues, '');
      $Credits = ArrayValue('Credits', $FormPostValues, '');
      
      // Enough credits available?
      if(!$this->HasEnoughCredits($UserID, $Credits)) {
         $Total = $this->GetAvailable($UserID, $Credits);
         $this->Validation->AddValidationResult('Credits', 'User does not have enough credits ('.$Total.' available).');
         return false;
      }
      
      // Validate the form posted values
      $TransactionID = FALSE;
      if($this->Validate($FormPostValues)) {
         $Fields = $this->Validation->SchemaValidationFields(); // All fields on the form that relate to the schema
         $TransactionID = $this->SQL->Insert($this->Name, $Fields);
      }
      
      // Update user's credit total
      if($TransactionID !== FALSE) {
         $this->UpdateUser($UserID, $Credits);
      }
      
      return $TransactionID;
   }
   
}

?>