<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo T('New Transaction'); ?></h1><ul>
   <li>
      <?php
         echo $this->Form->Label('Username', 'User');
         echo $this->Form->Input('User');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Credits', 'Credits');
         echo $this->Form->Input('Credits');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Description', 'Description');
         echo $this->Form->Input('Description');
      ?>
   </li>

</ul>
<?php echo $this->Form->Close('Save');