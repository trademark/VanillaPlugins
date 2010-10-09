<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo T('Discussion Prefix Settings'); ?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Prefix to use', 'Plugins.DiscussionPrefix.Prefix');
         echo $this->Form->Input('Plugins.DiscussionPrefix.Prefix');
      ?>
   </li>
   
   <li>
      <?php
         echo $this->Form->Label('Checkbox label', 'Plugins.DiscussionPrefix.Label');
         echo $this->Form->Input('Plugins.DiscussionPrefix.Label');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');