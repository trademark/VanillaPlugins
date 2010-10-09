<?php if (!defined('APPLICATION')) exit();

define('AUTHORIZE_URL', 'https://secure.authorize.net/gateway/transact.dll');

class PaymentModel extends Gdn_Model {
   
   /* @var array Possible responses from Authorize.net */
   public $Responses;
   
   /** @var array Valid credit card types for form */
   public $CardTypes = array(
      'visa' => 'Visa',
      'mastercard' => 'MasterCard',
      'amex' => 'American Express',
      'discover' => 'Discover'
   );
   
   /** @var array Authentication settings */
   public $Settings;
   
   /** @var string API login ID for authorize.net */
   public $LoginID;
   
   /** @var string Transaction key for authorize.net */
   public $TransactionKey;
   
   /** @var array The format we want the card data in */
   public $Card;
   
   /** @var string Error from the API if we get one */
   public $ErrorMessage;
   
   /** @var string Unique transaction we're referring to */
   public $TransactionID;

   /**
    * Class constructor.
    */
   public function __construct() {
      parent::__construct('AuthorizeNetPayment');
      # These must be set in the local conf file
      $this->LoginID = Gdn::Config('Plugins.AuthorizeNet.LoginID', '');
      $this->TransactionKey = Gdn::Config('Plugins.AuthorizeNet.TransactionKey', '');
      $this->Settings = array(
         'x_login' => $this->LoginID,
         'x_tran_key' => $this->TransactionKey,
         'x_version' => '3.1',
         'x_delim_data' => 'TRUE',
         'x_delim_char' => '|',
         'x_relay_response' => 'FALSE',
         'x_type' => 'AUTH_CAPTURE',
         'x_method' => 'CC'
      );
      $this->Card = array(
         'Type' => '',
         'ExpMonth' => 0,
         'ExpYear' => 0,
         'ExpDate' => 0,
         'Number' => '',
         'CVV' => '',
         'FirstName' => '',
         'LastName' => '',
         'Address' => '',
         'City' => '',
         'State' => '',
         'Zip' => ''
      );
   }

   /**
    *   Logic for processing and sending a payment thru Authorize.net
    *
    * Sets $this->ErrorMessage with relevant error on failure, or $this->TransactionID on success
    * @param Card array All card-related data with the following keys:
         'Type', 'ExpMonth', 'ExpYear', 'Number', 'CVV', 'FirstName', 'LastName', 'Address', 'City', 'State', 'Zip', 'ExpDate'
    * @param Amount float Amount to be charged to the card
    * @param Description string Optional description of the transaction
    * @return bool TRUE on success and FALSE on error
    * @url http://developer.authorize.net/guides/AIM/Transaction_Response/Fields_in_the_Payment_Gateway_Response.htm
    * *** Keys in above doc are OFF BY ONE since they don't start at zero. ***
    */
   public function Process($UserID, $Amount, $Card = '', $Description = '') {
      if(is_array($Card)) # Alt way to set this->Card
         $this->Card = $Card;
      $this->Card['ExpDate'] = $Card['ExpMonth'].$Card['ExpYear'];
      if($Card['ExpMonth'] < 10)
         $this->Card['ExpDate'] = '0'.$this->Card['ExpDate'];

      $Valid = $this->ValidateCard();
      if($Valid !== TRUE)
         return $Valid;

      $Payment = array(
         'x_card_num'      => $this->SanitizeNumber($this->Card['Number']),
         'x_exp_date'      => $this->Card['ExpDate'],      
         'x_amount'         => $this->SanitizeNumber($Amount),      
         'x_first_name'      => $this->Card['FirstName'],
         'x_last_name'      => $this->Card['LastName'],
         'x_address'         => $this->Card['Address'],
         'x_state'         => $this->Card['State'],
         'x_zip'            => $this->Card['Zip'],
         'x_description'   => $Description
      );
      $Response = $this->Send($Payment);
      
      if($Response[0] == 1) { # Success
         $this->TransactionID = $Response[6];
         $this->Log($UserID, $Amount, $Description);
         return true;
      }
      else { # There was an error
         return $this->ErrorMessage($Response[2]);
      }
   }
   
   /**
    * 
    */
   public function ValidateCard() {
      $Response = array();
      
      $this->Card['Number'] = preg_replace('/[^0-9]/', '', $this->Card['Number']);
      if(!is_numeric($this->Card['Number']) || strlen($this->Card['Number']) < 12 || strlen($this->Card['Number']) > 20)
         $Response[] = 'Invalid credit card number.';
      if($this->Card['CVV'] == '')
         $Response[] = 'CVV is required.';
      if($this->Card['FirstName'] == '')
         $Response[] = 'First name is required.';
      if($this->Card['LastName'] == '')
         $Response[] = 'Last name is required.';
      if($this->Card['Address'] == '')
         $Response[] = 'Address is required.';
      if($this->Card['City'] == '')
         $Response[] = 'City is required.';
      if($this->Card['State'] == '')
         $Response[] = 'State is required.';
      if($this->Card['Zip'] == '')
         $Response[] = 'Zip is required.';
      
      $this->Card['ExpDate'];
      /*'Type' => '',
         'ExpMonth' => 0,
         'ExpYear' => 0,
         'ExpDate' => 0,
         'Number' => '',
         'CVV' => '',
         'FirstName' => '',
         'LastName' => '',
         'Address' => '',
         'City' => '',
         'State' => '',
         'Zip' */
      # Well?   
      if(count($Response) == 0)
         return TRUE;
      else
         return $Response;
   }
   
   /**
    * Connect to Authorize.net API, send payment data, and receive reply
    */
   protected function Send($Payment) {
      # Build the request
      $RequestString = '';
      $TransactionData = array_merge($this->Settings, $Payment);
      foreach($TransactionData as $Key => $Value ) { 
         $RequestString .= $Key . '=' . urlencode($Value) . '&';
      }
      $RequestString = rtrim($RequestString, '& ');
      # Create connection and send
      $Connection = curl_init(AUTHORIZE_URL);
      curl_setopt($Connection, CURLOPT_HEADER, 0);
      curl_setopt($Connection, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($Connection, CURLOPT_POSTFIELDS, $RequestString);
      curl_setopt($Connection, CURLOPT_SSL_VERIFYPEER, FALSE);
      $RawResponse = curl_exec($Connection);
      curl_close($Connection);
      # Return the reply
      return explode($this->Settings['x_delim_char'], $RawResponse);
   }
   
   /**
    * Create a new payment record
    */
   public function Log($UserID, $Amount = 0, $Description = '') {
      $Session = Gdn::Session();
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      
      $UserID = (is_numeric($UserID) && $UserID > 0 ) ? $UserID : 0;
      $Amount = $this->SanitizeNumber($Amount);
      $InsertUserID = ($Session->IsValid()) ? $Session->User->UserID : 0;
      
      $SQL->Insert('Payment', array(
         'UserID' => $UserID,
         'Amount' => $Amount,
         'Description' => $Description,
         'TransactionID' => $this->TransactionID,
         'InsertUserID' => $InsertUserID,
         'DateInserted' => date('Y-m-d H:i:s')
      ));
   }
   
   /**
    * Select and return Payment(s)
    */
   public function Get($PaymentID = '') {
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      
      $SQL->Select('p.*')
         ->Select('u.Name as Username')
         ->From('Payment p')
         ->Join('User u', 'u.UserID = p.UserID', 'left');
         
      if(is_numeric($PaymentID) && $PaymentID > 0)
         $Data = $SQL->Where('PaymentID', $PaymentID)->Get()->FirstRow();
      else
         $Data = $SQL->Get();
      
      return $Data;
   }
   
   /**
    * Returns error message from API
    * @url http://developer.authorize.net/guides/AIM/Transaction_Response/Response_Reason_Codes_and_Response_Reason_Text.htm
    */
   public function ErrorMessage($Code) {
      switch($Code) {
         case 6: return 'Invalid credit card number.';
         case 7: return 'Invalid expiration date.';
         case 8: return 'Credit card has expired.';
         case 13: return 'There was a technical error. Your transaction did not go through.';
         case 17: return 'That type of credit card is not accepted.';
         default: return 'Transaction was declined.';
      }
   }
   
   /**
    * Stip all except numeric and decimal point
    */
   public function SanitizeNumber($Number) {
      return preg_replace('/[^\d\.]/', '', $Number);
   }

}

?>