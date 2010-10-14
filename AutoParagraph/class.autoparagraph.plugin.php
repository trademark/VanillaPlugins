<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

// Define the plugin:
$PluginInfo['AutoParagraph'] = array(
   'Name' => 'AutoParagraph',
   'Description' => 'Automatically creates nice paragraph tags in comments.',
   'Version' => '1.0a',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://www.tmprod.com/web-development/vanilla.php',
   'License' => 'GNU GPLv2'
);

class AutoParagraphPlugin extends Gdn_Plugin {

   /**
    * Replaces double line-breaks with paragraph elements.
    *
    * This method (and its doc) is from Wordpress 3.0.1; hat's off to them.
    * A group of regex replaces used to identify text formatted with newlines and
    * replace double line-breaks with HTML paragraph tags. The remaining
    * line-breaks after conversion become <<br />> tags, unless $br is set to '0'
    * or 'false'.
    *
    * @since 0.71
    *
    * @param string $pee The text which has to be formatted.
    * @param int|bool $br Optional. If set, this will convert all remaining line-breaks after paragraphing. Default true.
    * @return string Text which has been converted into correct paragraph tags.
    */
   function AutoParagraph($pee, $br = 1) {
   
   	if ( trim($pee) === '' )
   		return '';
   	$pee = $pee . "\n"; // just to make things a little easier, pad the end
   	$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
   	// Space things out a little
   	$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
   	$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
   	$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
   	$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
   	if ( strpos($pee, '<object') !== false ) {
   		$pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
   		$pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
   	}
   	$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
   	// make paragraphs, including one at the end
   	$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
   	$pee = '';
   	foreach ( $pees as $tinkle )
   		$pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
   	$pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
   	$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
   	$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
   	$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
   	$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
   	$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
   	$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
   	$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
   	if ($br) {
   		$pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', create_function('$matches', 'return str_replace("\n", "<WPPreserveNewline />", $matches[0]);'), $pee);
   		$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
   		$pee = str_replace('<WPPreserveNewline />', "\n", $pee);
   	}
   	$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
   	$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
   	if (strpos($pee, '<pre') !== false)
   		$pee = preg_replace_callback('!(<pre[^>]*>)(.*?)</pre>!is', 'clean_pre', $pee );
   	$pee = preg_replace( "|\n</p>$|", '</p>', $pee );
   
   	return $pee;
   }
   
   /**
    * 
    */
   public function StripWrapperParagraph($Body) {
      if(preg_match('/^<p>/', $Body))
         $Body = substr($Body, 3);
      if(preg_match('/<\/p>$/', $Sender->Discussion->Body))
         $Body = substr($Body, 0, -4);
      $Body = str_replace('</p><br />', '</p>', $Body);
      return $Body;
   }
   
   /**
    * 
    */
   public function DiscussionController_BeforeDiscussionRender_Handler(&$Sender) {
      $Sender->Discussion->Body = $this->AutoParagraph($Sender->Discussion->Body, false);
      $Sender->Discussion->Body = $this->StripWrapperParagraph($Sender->Discussion->Body);
   }
   
   /**
    * 
    */
   public function DiscussionController_AfterCommentFormat_Handler(&$Sender) {
      //print_r($Sender);
      foreach($Sender->Data['Comments'] as &$Comment) {
         $Comment->Body = $this->AutoParagraph($Comment->Body, false);
         $Comment->Body = $this->StripWrapperParagraph($Comment->Body);
      }
   }
   
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      SaveToConfig('Plugins.AutoParagraph.Enabled', TRUE);
   }
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      SaveToConfig('Plugins.AutoParagraph.Enabled', FALSE);
   }
}