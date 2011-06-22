<?php
function event_espresso_timereg_editor($event_id = 0){
	global $wpdb;
	$time_counter = 1;?>
	
	<ul id="staticTimeInput" style="margin:0; padding:0 0 0 10px;">
	
	<?php
	if  ($event_id > 0)
	{
		$timesx = $wpdb->get_results("SELECT * FROM " . EVENTS_DETAIL_TABLE . " WHERE id = '" . $event_id . "'");
		foreach($timesx as $timex)
		{
		echo '<li>' . __('Reg Start Time','event_espresso') .': <input size="10"  type="text" name="registration_startT" value="'.$timex->registration_startT.'"> - ' . __('Reg End Time','event_espresso') . ': <input size="10"  type="text" name="registration_endT" value="'.$timex->registration_endT.'"></li>';
		}
	}
	else
	{
	?>
		<li>
		<?php 	_e('Reg Start Time','event_espresso'); ?> <input size="10"  type="text"  name="registration_startT"> - <?php _e('Reg End Time:','event_espresso'); ?> <input size="10"  type="text"  name="registration_endT">
		</li>
<?php
	}
?>
   </ul>
<?php } 
function event_espresso_time_editor($event_id = 0){
	global $wpdb, $org_options;
	//$org_options['time_reg_limit'] = 'Y';
	$time_counter = 1;?>
          <ul id="dynamicTimeInput" style="margin:0; padding:0 0 0 10px;">
	<?php
            $times = $wpdb->get_results("SELECT * FROM " . EVENTS_START_END_TABLE . " WHERE event_id = '" . $event_id . "' ORDER BY id");
			if ($wpdb->num_rows > 0){
				foreach ($times as $time){
					echo '<li>' . __('Start','event_espresso') . ' ' .$time_counter++.': <input size="10"  type="text" name="start_time[]" value="'.$time->start_time.'"> - ' . __('End','event_espresso') . ': <input size="10"  type="text" name="end_time[]" value="'.$time->end_time.'"> ' . ($org_options['time_reg_limit'] == 'Y'? __('Qty','event_espresso') . ': <input size="3"  type="text" name="time_qty[]" value="'.$time->reg_limit.'">': '') . '<input type="button" value="Remove" onclick="this.parentNode.parentNode.removeChild(this.parentNode);"/></li>';
				}
			}else{
    ?>
    <li>
    <?php 	_e('Start:','event_espresso'); ?> <input size="10"  type="text"  name="start_time[]"> - <?php _e('End:','event_espresso'); ?> <input size="10"  type="text"  name="end_time[]"> <?php echo ($org_options['time_reg_limit'] == 'Y'? __('Qty','event_espresso') . ': <input size="3"  type="text" name="time_qty[]" value="'.$time->reg_limit.'">': '') ?>
    </li>
    <?php
        
			}
?>
          </ul>
     <?php global $espresso_premium; if ($espresso_premium != true) return;?>
    <input type="button" value="<?php _e('Add Additional Time','event_espresso'); ?>" onClick="addTimeInput('dynamicTimeInput');">
    <script type="text/javascript">
//Dynamic form fields
var counter = <?php echo $time_counter++ ?>;
function addTimeInput(divName){
          var newdiv = document.createElement('li');
          newdiv.innerHTML = "<?php _e('Start','event_espresso'); ?> " + (counter) + ": <input type='text' size='10' name='start_time[]'> - <?php _e('End: ','event_espresso'); ?>  <input type='text'  size='10' name='end_time[]'> <?php echo $org_options['time_reg_limit'] == 'Y'? __('Qty: ','event_espresso') . " <input type='text'  size='3' name='time_qty[]'>" : ''; ?><input type='button' value='Remove' onclick='this.parentNode.parentNode.removeChild(this.parentNode);'/>";
          document.getElementById(divName).appendChild(newdiv);
          counter++;
}
</script>
<?php
}

function event_espresso_meta_edit($event_meta=''){
	global $wpdb, $org_options;
	global $espresso_premium; if ($espresso_premium != true) return;
	$good_meta = array();
	$hiddenmeta = array("","venue_id","additional_attendee_reg_info","add_attendee_question_groups", "date_submitted", "event_host_terms");
	$meta_counter = 1;
	$good_meta = $event_meta;
	//print_r( $event_meta );
	
?>
	<ul id="dynamicMetaInput" style="margin:0; padding:0 0 0 10px;">
<?php
	if ($event_meta !=''){
		foreach($event_meta as $k=>$v){	?>
	<?php
				if( in_array($k,$hiddenmeta) ){
	//				echo "<input type='hidden' name='emeta[]' value='{$v}' />";
					unset($good_meta[$k]);
				}else{
	?>
				<li>
				<?php _e('Key:', 'event_espresso'); ?> <select id="emeta[]" name="emeta[]">
	<?php			foreach($good_meta as $k2=>$v2){	?>
						<option value="<?php echo $k2;?>" <?php echo ($k2 == $k ? "SELECTED" : null);?>><?php echo $k2;?></option>
	<?php				}	?>
						</select>
						&nbsp;<?php _e('Value:', 'event_espresso'); ?> <input size="20" type="text" value="<?php echo $v;?>" name="emetad[]" id="emetad[]">
	<?php
				echo '<img  onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/remove.gif" alt="' . __('Remove Meta', 'event_espresso') . '" />';
	?>
				</li>
	<?php		
					$meta_counter++;
				}	?>
	<?php	}
	echo '<li>'.__('Key:', 'event_espresso'); ?> <input size="20" type="text" value="" name="emeta[]" id="emeta[]"> <?php _e('Value:', 'event_espresso'); ?> <input size="20" type="text" value="" name="emetad[]" id="emetad[]"><?php echo '<img  onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/remove.gif" alt="' . __('Remove Meta', 'event_espresso') . '" />'.'</li>'; 
	}else{
		 echo '<li>'.__('Key:', 'event_espresso'); ?> <input size="20" type="text" value="" name="emeta[]" id="emeta[]"> <?php _e('Value:', 'event_espresso'); ?><input size="20" type="text" value="" name="emetad[]" id="emetad[]"><?php echo '<img  onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/remove.gif" alt="' . __('Remove Meta', 'event_espresso') . '" />'.'</li>'; 
		// $meta_counter++;
	}	?>
		</ul>

          <p><input type="button" value="<?php _e('Add A Meta Box','event_espresso'); ?>" onClick="addMetaInput('dynamicMetaInput');"></p>

<script type="text/javascript">
//Dynamic form fields
var meta_counter = <?php echo $meta_counter>1?$meta_counter-1:$meta_counter++; ?>;
function addMetaInput(divName){
		  var next_counter = counter_staticm(meta_counter);
          var newdiv = document.createElement('li');
          newdiv.innerHTML = "<?php _e('Key:', 'event_espresso'); ?><input size='20' type='text' value='' name='emeta[]' id='emeta[]'>&nbsp;<?php _e('Value:', 'event_espresso'); ?><input size='20' type='text' value='' name='emetad[]' id='emetad[]'><?php echo '<img  onclick=\"this.parentNode.parentNode.removeChild(this.parentNode);\" src=\"' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/remove.gif\" alt=\"' . __('Remove Meta', 'event_espresso') . '\" />'; ?>";
          document.getElementById(divName).appendChild(newdiv);
          counter++;
}

function counter_staticm(meta_counter) {
    if ( typeof counter_static.counter == 'undefined' ) {

        counter_static.counter = meta_counter;
    }
    return ++counter_static.counter;
}
</script>
<?php
}

function event_espresso_multi_price_update($event_id){
global $wpdb, $org_options;

$paypal_settings = get_option('event_espresso_paypal_settings');

$price_counter = 1;

?>
        <p><strong><?php _e('Standard Pricing','event_espresso'); ?></strong></p>
        <ul id="dynamicPriceInput" style="margin:0; padding:0 0 0 10px;">
<?php
		$prices = $wpdb->get_results("SELECT price_type, event_cost, surcharge, surcharge_type FROM ". EVENTS_PRICES_TABLE ." WHERE event_id = '".$event_id."' ORDER BY id");
		if ($wpdb->num_rows > 0){
		foreach ($prices as $price){
			echo '<li>';
			
			echo __('Name','event_regis') . ' ' . $price_counter++.': <input size="10"  type="text" name="price_type[]" value="' . $price->price_type . '"> ';
			echo  __('Price','event_regis') . ': ' . $org_options['currency_symbol'] . '<input size="5"  type="text" name="event_cost[]" value="' . $price->event_cost . '"> ';

			echo __('Surcharge:','event_regis') . ' <input size="5"  type="text"  name="surcharge[]" value="' . $price->surcharge . '" > ';
			echo __('Surcharge Type:','event_regis');

                        ?>
                    <select name="surcharge_type[]">
                        <option value = "flat_rate" <?php selected($price->surcharge_type, 'flat_rate') ?>><?php _e('Flat Rate','event_regis');?></option>
                        <option value = "pct" <?php selected($price->surcharge_type, 'pct') ?>><?php _e('Percent','event_regis');?></option>
                    </select>

            <?php

			echo '<img  onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/remove.gif" alt="' . __('Remove Attendee', 'event_espresso') . '" />';
			//echo '<input type="button" value="Remove" onclick="this.parentNode.parentNode.removeChild(this.parentNode);"/>';
			echo '</li>';
			}
	}else{
?>
		<li>
		<?php _e('Name','event_regis'); ?>
		<?php echo $price_counter ?>:
		<input size="10"  type="text"  name="price_type[]">
		<?php _e('Price:','event_regis'); ?>
		<?php echo $org_options['currency_symbol'] ?>
		<input size="5"  type="text"  name="event_cost[]">

		<?php _e('Surcharge:','event_regis');?>

		<input size="5"  type="text"  name="surcharge[]" value="<?php echo $org_options['surcharge'] ?>" >
                <?php _e('Surcharge Type:','event_regis');?> 
                 <select name="surcharge_type[]">
                        <option value = "flat_rate" <?php selected($org_options['surcharge_type'], 'flat_rate') ?>><?php _e('Flat Rate','event_regis');?></option>
                        <option value = "pct" <?php selected($org_options['surcharge_type'], 'pct') ?>><?php _e('Percent','event_regis');?></option>
                    </select>
		<?php echo '<img  onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/remove.gif" alt="' . __('Remove Attendee', 'event_espresso') . '" />';?>
		</li>
<?php
	}
?>
          </ul>
          <p>
            (<?php _e('enter 0.00 for free events, enter 2 place decimal i.e.','event_espresso'); ?> <?php echo $org_options['currency_symbol'] ?> 7.00)
          </p>
          <?php global $espresso_premium; if ($espresso_premium != true) return;?>
          <p><input type="button" value="<?php _e('Add A Price','event_espresso'); ?>" onClick="addPriceInput('dynamicPriceInput');"></p>

<script type="text/javascript">
//Dynamic form fields
var price_counter = <?php echo $price_counter>1?$price_counter-1:$price_counter++; ?>;
function addPriceInput(divName){
		  var next_counter = counter_static(price_counter);
          var newdiv = document.createElement('li');
          newdiv.innerHTML = "<?php _e('Name','event_regis'); ?> " + (next_counter) + ": <input type='text' size='10' name='price_type[]'> <?php _e('Price','event_regis'); ?>: <?php echo $org_options['currency_symbol'] ?> <input type='text' size='5' name='event_cost[]'> <?php _e('Surcharge','event_regis'); ?>: <input size='5'  type='text'  name='surcharge[]' value='<?php echo $org_options['surcharge'] ?>' > <?php _e('Surcharge Type','event_regis'); ?>: <select name='surcharge_type[]'><option value = 'flat_rate' <?php selected($org_options['surcharge_type'], 'flat_rate') ?>><?php _e('Flat Rate','event_regis');?></option><option value = 'pct' <?php selected($org_options['surcharge_type'], 'pct') ?>><?php _e('Percent','event_regis');?></option></select> <?php echo "<img  onclick='this.parentNode.parentNode.removeChild(this.parentNode);' src='" . EVENT_ESPRESSO_PLUGINFULLURL . "images/icons/remove.gif' alt='" . __('Remove Attendee', 'event_espresso') . '\' />';?>";
          document.getElementById(divName).appendChild(newdiv);
          counter++;
}

function counter_static(price_counter) {
    if ( typeof counter_static.counter == 'undefined' ) {

        counter_static.counter = price_counter;
    }


    return ++counter_static.counter;
}
</script>
<?php
}


//This function grabs the event categories and outputs checkboxes.
//@param optional $event_id = pass the event id to get the categories assigned to the event.
function event_espresso_get_categories($event_id = 0){
	global $wpdb;
	$event_categories = $wpdb->get_results("SELECT * FROM ". EVENTS_CATEGORY_TABLE);
	if ($wpdb->num_rows > 0){
		foreach ($event_categories as $category){
			$category_id = $category->id;
			$category_name = $category->category_name;

			$in_event_categories = $wpdb->get_results("SELECT * FROM " . EVENTS_CATEGORY_REL_TABLE . " WHERE event_id='".$event_id."' AND cat_id='".$category_id."'");
			foreach ($in_event_categories as $in_category){
				$in_event_category = $in_category->cat_id;
			}
			echo '<p id="event-category-' . $category_id . '"><label for="in-event-category-' . $category_id . '" class="selectit"><input value="' . $category_id . '" type="checkbox" name="event_category[]" id="in-event-category-' . $category_id . '"' . ($in_event_category == $category_id ? ' checked="checked"' : "" ) . '/> ' . $category_name. "</label></p>";
		}
	}else{
		_e('No Categories', 'event_espresso');
	}
}
