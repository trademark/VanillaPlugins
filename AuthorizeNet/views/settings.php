<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo T('Authorize.net Settings'); ?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->Label('API Login ID', 'Plugins.AuthorizeNet.LoginID');
         echo $this->Form->Input('Plugins.AuthorizeNet.LoginID');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Transaction Key', 'Plugins.AuthorizeNet.TransactionKey');
         echo $this->Form->Input('Plugins.AuthorizeNet.TransactionKey');
      ?>
   </li>

</ul>
<?php echo $this->Form->Close('Save');