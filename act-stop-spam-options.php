<?php
/**
 * Act Stop Spam Option Settings Page.
 * @package   ActStopSpam/Settings
 * @author    ActSupport <plugin@actsupport.com>
 * @license   GPL-2.0+
 * @link      http://www.actsupport.com
 * @copyright 2015 ActSupport.com
 * @version   1.0
 * act-stop-spam-options.php
 */?>
 <div class="wrap">
  <h1> Act Stop Spam Settings </h1>
  <?php if ( isset( $_POST['ActStopSpamUpdate'] )&&$status===true ): ?>
  <div id="message" class="updated fade">
    <p>
      <?php _e( 'Settings have been saved.' ) ?>
    </p>
  </div>
  <?php 
		elseif(isset( $_POST['ActStopSpamUpdate'] )&&$status===false):?>
  <div id="message" class="error fade">
    <p>
      <?php _e( 'Content dosen\'t have any modifications to update.' ) ?>
    </p>
  </div>
  <?php 
		endif; ?>
  <form method="post">
    <table class="form-table">
      <tr>
        <th scope="row"> <label for="actstopspam_confidence"> Stop Spam Confidence: </label>
        </th>
        <td><select id="actstopspam_confidence" name="actstopspam_confidence" >
            <?php 
					for($ix=1;$ix<100;$ix++)
					{
						$selected="";
						if($actstopspam_confidence==$ix){
						$selected="selected='selected'";
						}
						echo '<option value="'.$ix.'" '.$selected.'>'.$ix.'</option>';
					}
?>
          </select>%<br />
          Select the minimum confidence level for checking spammers (the higher the level the more likely the user is a spammer). A visitor who generates a level higher than this will be denied for accessing site.<br />
          Example: A user who has been mistakenly added to the spam database might generate a 0-20% level. A hardened spammer will be upwards of 40-50% and more. </td>
      </tr>
      <tr>
        <th scope="row"> <label for="actstopspam_BlockMessage"> Block Message: </label>
        </th>
        <td><?php
                    $content =$actstopspam_BlockMessage;
$editor_id = 'actstopspam_BlockMessage';

wp_editor( $content, $editor_id,array('editor_height' => 200) );
					?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td><input id="ActStopSpamUpdate" name="ActStopSpamUpdate" type='submit' value="Save Settings" class="button-primary" /></td>
      </tr>
    </table>
  </form>
</div>