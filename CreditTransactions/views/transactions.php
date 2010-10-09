<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session(); ?>

<h1><?php echo T('Credit Transactions'); ?></h1>

<div class="FilterMenu"><?php echo Anchor('New Transaction', 'plugin/credittransactions/add', 'SmallButton'); ?></div>

<?php if($this->TransactionData->NumRows() > 0) : ?>

<table class="FormTable AltColumns" id="TransactionTable">
   <thead>
      <tr id="0">
         <th><?php echo T('User'); ?></th>
         <th><?php echo T('Amount'); ?></th>
         <th><?php echo T('Description'); ?></th>
         <th><?php echo T('Date'); ?></th>
      </tr>
   </thead>
   <tbody>

<?php foreach ($this->TransactionData as $Transaction) : ?>
   <tr id="<?php echo $Transaction->PaymentID; ?>">
      <td class="First"><strong><?php echo Anchor($Transaction->Username, 'profile/'.$Transaction->UserID.'/'.$Transaction->Username); ?></strong></td>
      <td class="Alt"><?php echo $Transaction->Credits; ?></td>
      <td><?php echo $Transaction->Description; ?></td>
      <td><?php echo Gdn_Format::Date($Transaction->DateInserted); ?></td>
   </tr>
   
<?php endforeach; ?>

   </tbody>
</table>

<?php endif; ?>
