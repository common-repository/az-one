<?php
/*
 Plugin Name: AZ-One
 Plugin URI: http://www.52lives.com/downloads
 Description: Don't leave money on the table! AZ-One finds out which Amazon store (com, ca, co.uk, de, fr, jp) is closest to your visitor and changes the associate links on the page to direct the visitor to the right store. And that means more money in your pocket! Go ahead and <a href="options-general.php?page=az_one_options">configure your AZ-One</a>!
 Version: 1.0
 Author: Harri Lammi
 Author URI: http://www.52lives.com/
 */
?>
<?php
/*  Copyright 2007  Harri Lammi  (email : harri.lammi(at)52lives.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
/*
This script uses the IP-to-Country Database
provided by WebHosting.Info (http://www.webhosting.info),
available from http://ip-to-country.webhosting.info. 

BECAUSE THE DATABASE IS LICENSED FREE OF CHARGE, THERE IS NO 
WARRANTY FOR THE DATABASE, TO THE EXTENT PERMITTED BY APPLICABLE 
LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT 
HOLDERS AND/OR OTHER PARTIES PROVIDE THE DATABASE "AS IS" 
WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESSED OR IMPLIED, 
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE OR ANY 
WARRANTIES REGARDING THE CONTENTS OR ACCURACY OF THE WORK.

IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN 
WRITING WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MAY 
MODIFY AND/OR REDISTRIBUTE THE DATABASE AS PERMITTED ABOVE, BE 
LIABLE TO YOU FOR DAMAGES, INCLUDING ANY GENERAL, SPECIAL, 
INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF THE USE OR 
INABILITY TO USE THE DATABASE, EVEN IF SUCH HOLDER OR OTHER PARTY 
HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
*/
?>
<?php
define("AZ_ONE_ASSOCID_PATTERN", "/^[a-z0-9-_]+$/i");
define("AZ_ONE_ASSOCLINK_PATTERN", "/(<a href=)(\S){1,15}(www\.amazon\.)(com|ca|de|co.uk|fr|jp)(\S+)(\")/"); 
define("AZ_ONE_ASSOCIMG_PATTERN", "/(<img\s)(\S){1,25}(\.assoc-amazon\.)(com|ca|de|co.uk|fr|jp)(.+)(>)/");

function az_one_plugin($content) {
	if( !strpos($_SERVER['REQUEST_URI'], 'wp-admin') ) {
		$content = preg_replace_callback(AZ_ONE_ASSOCLINK_PATTERN, 'az_one_replace_assoc_links', $content);
		$content = preg_replace_callback(AZ_ONE_ASSOCIMG_PATTERN, 'az_one_replace_assoc_img_links', $content);
	}
	return $content;
}

function az_one_mod_link($content) {
	$content = preg_replace_callback(AZ_ONE_ASSOCLINK_PATTERN, 'az_one_replace_assoc_links', $content);
	$content = preg_replace_callback(AZ_ONE_ASSOCIMG_PATTERN, 'az_one_replace_assoc_img_links', $content);
	echo $content;
}

function az_one_set_cookie() {
	if( !strpos($_SERVER['REQUEST_URI'], 'wp-admin') ) {
		if (!(isset($_COOKIE['az_one_store']))) {
			$store = az_one_get_amazon_store();
			setcookie("az_one_store", $store, time()+3600);
		}
	}
}

function az_one_get_amazon_store() {
	global $wpdb;
	$store = 'default';

	$ipadd = $_SERVER[REMOTE_ADDR];

	$table_name = $wpdb->prefix . 'az_one_countryIPs';

	$sql  = "SELECT store FROM " . $table_name .
			" WHERE ip_from <= INET_ATON('" . $ipadd . "') ".            "AND ip_to >= INET_ATON('" . $ipadd . "') ";

	$result = $wpdb->get_var( $sql );
	if( $result != NULL && $result !== FALSE ) {
		$store = $result;
	}
	return $store;

}

function az_one_install_SQL() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'az_one_countryIPs';	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (				ip_from DOUBLE NOT NULL,				ip_to DOUBLE NOT NULL,				country_code2 CHAR(2) NOT NULL,
				country_name VARCHAR(50),
				store VARCHAR(5),				UNIQUE KEY ip_from (ip_from)				);";
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');		dbDelta($sql);
	}

	$def_id = array( 'com' => 'amazondotone-20', 'co.uk' => 'amazondotone-21',
					  'de' => 'amazondotone-de-21', 'ca' => 'amazondotone-ca-20', 
					  'fr' => 'amazondotone-fr', 'jp' => 'amazondotone-20' );

	$installed_files = array( 'ip_set1.csv' => 0, 'ip_set2.csv' => 0,
							  'ip_set3.csv' => 0, 'ip_set4.csv' => 0,
							  'ip_set5.csv' => 0, 'ip_set6.csv' => 0,
							  'ip_set7.csv' => 0
	);
	
	$old_options = get_option('az_one_options');
	if(is_array($old_options)){
		foreach($old_options['installed_files'] as $file => $installed) {
			if($installed != 0){
				$installed_files[$file] = $installed;
			}
		}
	}
	
	$options = array( 'assoc_id' => $def_id, 'installed_files' => $installed_files, 'version' => '1.0' );
	update_option('az_one_options', $options);
}


function az_one_import_IPs($csvs_to_install) {
	$msg = "";
	global $wpdb;
	$table_name = $wpdb->prefix . 'az_one_countryIPs';


	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		return "No database table available. Please re-activate the plugin in the Options page. ";
	}

	foreach($csvs_to_install as $csv) {
		$row = 0;		$handle = fopen(dirname(__FILE__) . "/$csv", "r");
		$install_ok = 1;		while (($data = fgetcsv($handle, 1000, ";"))) {
    		$sql = "INSERT INTO " . $table_name . " (ip_from, ip_to, country_code2, country_name, store) " .
    					"VALUES (" . $wpdb->escape($data[0]) . ", " .
    				$wpdb->escape($data[1]) . ", '" .
    				$wpdb->escape($data[2]) . "', '" .
    				$wpdb->escape($data[3]) . "', '" .
    				$wpdb->escape($data[4]) . "')";    		$results = $wpdb->query( $sql );
    		if( $result === FALSE ) {
    			$install_ok = 0;
    		} else {
    			$row = $row + 1;
    		}
    				}		fclose($handle);
		if($install_ok == 1) {
			$options = get_option('az_one_options');
			$options['installed_files'][$csv] = 1;
			update_option('az_one_options', $options);
			$msg .= " $csv installed ($row rows). ";
		}
	}
	return $msg;
}

function az_one_replace_assoc_links($match) {

	$new_link = $match[0];
	$def_store = $match[4];

	$store = $def_store;
	if (isset($_COOKIE['az_one_store'])) {
		$store = $_COOKIE['az_one_store'];
	} else {
		$store = az_one_get_amazon_store();
	}

	if($store != $def_store && $store != 'default')
	{
		$associate_id = az_one_get_aff_code($store);
		if($associate_id != 'error'){
			$patterns = array('/(\.){1}(com|co\.uk|ca|fr|de|jp){1}(\/){1}/',
							  '/(tag=){1}([a-z0-9_-]+)/i',
							  '/(redirect.html)(\S+)(\.){1}(com|co\.uk|ca|fr|de|jp){1}/i'
							  );
							  	
							  $replacement = array('${1}' . $store . '${3}',
								 '${1}' . $associate_id,
								 '${1}${2}${3}' . $store
							  );
							  $new_link = preg_replace($patterns, $replacement, $new_link);
		}
	}

	return $new_link;
}

function az_one_replace_assoc_img_links($match) {
	$new_link = $match[0];
	$def_store = $match[4];

	$store = $def_store;
	if (isset($_COOKIE['az_one_store'])) {
		$store = $_COOKIE['az_one_store'];
	} else {
		$store = az_one_get_amazon_store();
	}

	if($store != $def_store && $store != 'default')
	{
		$the_o = az_one_get_o($store);
		$associate_id = az_one_get_aff_code($store);
		if($associate_id != 'error'){
			$patterns = array('/(\.){1}(com|co\.uk|ca|fr|de|jp){1}(\/){1}/',
							  '/(\?t=){1}([a-z0-9_-]+)/i',
							  '/(&amp;|&){1}(o=){1}([0-9]){1,3}/'
							  );
							  	
			$replacement = array('${1}' . $store . '${3}',
				 				 '${1}' . $associate_id,
				 				 '${1}${2}' . az_one_get_o($store)
			 					 );
			$new_link = preg_replace($patterns, $replacement, $new_link);
		}
	}
	return $new_link;
}

function az_one_update_store($store, $country_code2) {
	global $wpdb;
	$table_name = $wpdb->prefix . "az_one_countryIPs";
	$sql = "UPDATE " . $table_name . " SET store = '$store' WHERE country_code2 = '$country_code2'";
	$update_ok = 0;
	if( $wpdb->query($sql) ) {
		$update_ok = 1;
	}
	return $update_ok;
}

// UTILS

function az_one_get_aff_code($store) {
	$assoc_ids = get_option('az_one_options');
	$assoc_id = "error";
	if(is_array($assoc_ids)){
		$tmp_id = $assoc_ids['assoc_id'][$store];
		if($tmp_id != "") {
			$assoc_id = $tmp_id;
		}
	}

	return $assoc_id;
}

function az_one_country_list() {
	global $wpdb;
	$country_table = $wpdb->prefix . "az_one_countryIPs";
	$country_sql = "SELECT country_code2, country_name, store FROM " . $country_table . " GROUP BY country_name";
	$countries = $wpdb->get_results($country_sql);
	return $countries;
}

function az_one_get_o( $store ) {
	$value = 1;
	switch( $store ) {
		case 'com':
			$value = 1;
			break;
		case 'co.uk':
			$value = 2;
			break;
		case 'de':
			$value = 3;
			break;
		case 'fr':
			$value = 8;
			break;
		case 'ca':
			$value = 15;
			break;
		default:
			$value = 1;
			break;
	}
	return $value;
}

// PAGES

function az_one_add_pages() {	add_options_page('AZ-One Options', 'AZ-One', 'manage_options', 'az_one_options', 'az_one_options_page');
}

function az_one_options_page() {
	global $wpdb;	$opt_name = 'az_one_options';	$hidden_field_name = 'az_one_submit_hidden';	$data_field_name = 'az_one_aff_codes_';

	$com_dfn = $data_field_name . "com";
	$ca_dfn = $data_field_name . "ca";
	$uk_dfn = $data_field_name . "uk";
	$de_dfn = $data_field_name . "de";
	$fr_dfn = $data_field_name . "fr";
	$jp_dfn = $data_field_name . "jp";
	$default_store = "com";	$options = get_option( $opt_name );
		if( $_POST[ $hidden_field_name ] == 'Y' ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
      		die(__('CheatinÕ uh?'));
		}
		
		$msg = "";
		$opt_val = array('com' => $_POST[ $com_dfn ],
        				 'ca' => $_POST[ $ca_dfn ],
				         'co.uk' => $_POST[ $uk_dfn ],
				         'de' => $_POST[ $de_dfn ],
				         'fr' => $_POST[ $fr_dfn ],
				         'jp' => $_POST[ $jp_dfn ]
		);

		$id_check_ok = TRUE;
		$ids_changed = FALSE;
		$id_msg = "Illegal associate ids:";
		foreach ($opt_val as $key => $associate) {
			$match_ok = preg_match(AZ_ONE_ASSOCID_PATTERN, $associate);
			if( $associate != "" && !$match_ok ) {
				$id_check_ok = FALSE;
				$id_msg .= " $associate";
			}
			if( $options['assoc_id'][$key] != $associate ){
				$ids_changed = TRUE;
			}
		}
		$id_msg .= ". Associate IDs not saved. ";

		if($id_check_ok) {
			if($ids_changed){
				$options['assoc_id'] = $opt_val;				update_option( $opt_name, $options );
				$msg = "Associate IDs saved.";
			}
		} else {
			$msg = $id_msg;
		}

		$install_array = $_POST['csv_install'];
		if( is_array($install_array) && count($install_array) > 0 ) {
			$result = az_one_import_IPs( $install_array );
			$msg .= $result . " ";
		}
		
		$countries = az_one_country_list();
		foreach($countries as $country) {		
			$store_code = $country->country_code2 . "store_config";
			$store_value = $_POST[$store_code];
			if( $store_value != $country->store && $store_value != "" && isset($store_value) ) {
				$result = az_one_update_store( $store_value, $country->country_code2 );
				if( $result == 0 ) {
					$msg .= "Update for " . $country->country_name . "(" . $country->country_code2 . ")";
					$msg .= " was not succesful. Please try updating again. ";
				}
			}
		}		?>
<div class="updated">
<p><strong><?php _e($msg, 'az_one' ); ?></strong></p>
</div>
		<?php	}echo '<div class="wrap">';?>

<form method="post"
	action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<?php wp_nonce_field('update-options') ?> <input type="hidden"
	name="<?php echo $hidden_field_name; ?>" value="Y"> <?php

	$not_installed = array();
	$fresh_opts = get_option($opt_name);
	foreach($fresh_opts['installed_files'] as $install_file => $installed) {
		if( $installed == 0) {
			array_push( $not_installed, $install_file );
		}

	}

	if( count($not_installed) > 0 ) {		echo "<h2>" . __( 'Install IP Database', 'az_one' ) . "</h2>";
		?>
		<p>Before you can take full advantage of AZ-One, you need to install the files below. They contain the country 
		and IP address information used to identify your visitor's location. Since the files are quite big, consider 
		installing them one by one. And be patient :)</p>
		<?php
		echo "<table border=\"0\">";
		 
		foreach($not_installed as $file_to_install) {
			echo "<tr><td><input type=\"checkbox\" name=\"csv_install[]\" value=\"$file_to_install";
			echo "\"></td><td>$file_to_install</td></tr>";
		}
		echo "</table>";
		?>
<p class="submit"><input type="submit" name="Submit"
	value="<?php _e('Update Options', 'az_one' ) ?>" /></p>

		<?php
		 
}
echo "<h2>" . __( 'Amazon Associate IDs', 'az_one' ) . "</h2>";
?>
Enter your associate IDs to the fields below. If you don't have an account for them all, you have two 
options: 
<ol><li>Clear the field. If you clear the field, visitors from that store's area 
will be shown the original link in the post.</li>
<li>If you leave the default ID, the referral fees from that 
store will support AZ-One's development :)</li></ol>
<table border="0">
	<tr>
		<td>Amazon.com</td>
		<td><input type="text" name="<?php echo $com_dfn; ?>"
			value="<?php echo $options['assoc_id']['com']; ?>" size="20"></td>
	</tr>
	<tr>
		<td>Amazon.co.uk</td>
		<td><input type="text" name="<?php echo $uk_dfn; ?>"
			value="<?php echo $options['assoc_id']['co.uk']; ?>" size="20"></td>
	</tr>
	<tr>
		<td>Amazon.ca</td>
		<td><input type="text" name="<?php echo $ca_dfn; ?>"
			value="<?php echo $options['assoc_id']['ca']; ?>" size="20"></td>
	</tr>
	<tr>
		<td>Amazon.de</td>
		<td><input type="text" name="<?php echo $de_dfn; ?>"
			value="<?php echo $options['assoc_id']['de']; ?>" size="20"></td>
	</tr>
	<tr>
		<td>Amazon.fr</td>
		<td><input type="text" name="<?php echo $fr_dfn; ?>"
			value="<?php echo $options['assoc_id']['fr']; ?>" size="20"></td>
	</tr>
	<tr>
		<td>Amazon.jp</td>
		<td><input type="text" name="<?php echo $jp_dfn; ?>"
			value="<?php echo $options['assoc_id']['jp']; ?>" size="20"></td>
	</tr>
</table>

</p>
<p class="submit"><input type="submit" name="Submit"
	value="<?php _e('Update Options', 'az_one' ) ?>" /></p>

<?php
	echo "<h2>" . __( 'Store configuration', 'az_one' ) . "</h2>";
	?>
	Here is the list of countries stored in the database. <ol><li>You can choose which 
	Amazon store is used for each country.</li><li>Use the radio buttons to select a store 
	and </li><li>remember to press 'Update options' to save your selections.</li></ol>
	<?php
	echo "<table border=\"1\">";
	$stores = array('com', 'ca', 'co.uk', 'de', 'fr', 'jp');
	$echo_countries = az_one_country_list();
	foreach($echo_countries as $echo_country) {
		echo "<tr><td>" . $echo_country->country_name . "</td>";
		foreach($stores as $store) {
			$checked = "$store";
			if($echo_country->store == $store){ 
				$checked = "$store\" checked=\"checked"; 
			}
			?>
			<td><input type="radio" name="<?php echo $echo_country->country_code2; ?>store_config" value="<?php echo "$checked"; ?>"
			 >&nbsp;<?php echo $store; ?>
		</td>
	<?php
		}
		echo "</tr>";
	}
	echo "</table>";
?>
<hr />
<p class="submit"><input type="submit" name="Submit"
	value="<?php _e('Update Options', 'az_one' ) ?>" /></p>
</form>

</div>

<?php
}

// ACTIONS
add_action('admin_menu', 'az_one_add_pages');
register_activation_hook(__FILE__,'az_one_install_SQL');
add_action('init', 'az_one_set_cookie');
add_filter('the_content', 'az_one_plugin');

?>