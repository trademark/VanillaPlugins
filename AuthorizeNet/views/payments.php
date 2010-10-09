<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session(); ?>

<h1><?php echo T('Manage Payments'); ?></h1>

<?php if($this->PaymentData->NumRows() > 0) : ?>

<table class="FormTable AltColumns" id="PaymentTable">
   <thead>
      <tr id="0">
         <th><?php echo T('User'); ?></th>
         <th class="Alt"><?php echo T('Amount'); ?></th>
         <th><?php echo T('Description'); ?></th>
         <th><?php echo T('Options'); ?></th>
      </tr>
   </thead>
   <tbody>

<?php foreach ($this->PaymentData as $Payment) : ?>
   <tr id="<?php echo $Payment->PaymentID; ?>">
      <td class="First"><strong><?php echo Anchor($Payment->Username, 'profile/'.$Payment->UserID.'/'.$Payment->Username); ?></strong></td>
      <td class="Alt"><?php echo '$'.$Payment->Amount; ?></td>
      <td><?php echo $Payment->Description; ?></td>
      <td><?php echo Gdn_Format::Date($Payment->DateInserted); ?></td>
   </tr>
   
<?php endforeach; ?>

   </tbody>
</table>

<?php endif; ?>
