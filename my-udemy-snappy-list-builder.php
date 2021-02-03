<?php
	
/*
Plugin Name: My Udemy Snappy List Builder
Plugin URI: http://wordpressplugincourse.com/plugins/snappy-list-builder
Description: Uses the custom posts Subscriber and List to allow admin users to create lists and add new subscribers to them using names and emails. This info is stored in the database. 
Version: 1.0
Author: Udemy Course and Sarah Jones
Author URI: sarahjones.co.za
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Hooks 
// Filter hooks allow us to get data from WordPress, modify it, and return it back customized
// Whereas action hooks let us run our own code when a certain event takes place in the WordPress lifecycle

// The filter manage_{}_columns is one that is made available by WordPress for  custom post types
// This filter has a dynamic portion in the hook name (edit-slb_subscriber) which is the $screen->id (can see this using inspect eleent on the subscriber page)
add_filter('manage_edit-slb_subscriber_columns','slb_subscriber_column_headers');
add_filter('manage_edit-slb_list_columns','slb_list_column_headers');
// do_action( "add_meta_boxes_{$post_type}", WP_Post $post )
// Fires after all built-in meta boxes have been added, contextually for the given post type.
// Once built in metaboxes have been added the code here adds the custom slb-subscriber-details metabox
add_action('add_meta_boxes_slb_subscriber','slb_add_subscriber_metaboxes');
add_action('save_post', 'slb_save_slb_subscriber_meta', 10, 3);
// admin_head - Fires in head section for all admin pages.
add_action('admin_head','slb_edit_post_change_title');



//Functions

// Registers custom admin column headers
// $columns is an associative array that contains information about the column headers
// It is accessible becasue it is an argument of the filter.
 function slb_subscriber_column_headers( $columns ) {
	
	// creating custom column header data - i.e. override data that is coming in through the argument
	$columns = array(
		// cb - check box - keeps them there but empty (unchecked)
		'cb'=>'<input type="checkbox" />',
		// __() translates - 
		// Changes title from Title to Subscriber Name
		'title'=>__('Subscriber Name')
	);
	
	// returning new column info
	return $columns;
	
}


//Same as above but for the custom Lists post type
 function slb_list_column_headers( $columns ) {
	
	// creating custom column header data - i.e. override data that is coming in through the argument
	$columns = array(
		// cb - check box - keeps them there but empty (unchecked)
		'cb'=>'<input type="checkbox" />',
		// __() translates - 
		// Changes title from Title to Subscriber Lists
		'title'=>__('Subscriber Lists')
	);
	
	// returning new columns
	return $columns;

}

// A post meta box is a draggable box shown on the post editing screen. 
// Its purpose is to allow the user to select or enter information in addition to the main post content.
// These are the custom fields which appear on the custom post types, in this case the subscriber post type
function slb_add_subscriber_metaboxes(){

	// Built in WordPress function
	add_meta_box(
		'slb-subscriber-details', /**ID */
		'Subscriber Details', /**Title which appears on the slb_subscriber custom post type */
		'slb_subscriber_metabox', /**Callback function */
		'slb_subscriber', /*screen as in slug for post type which was defined on the custom post type that was craeted*/
		'normal', /**Context - as in where it appears on the screen */
		'default' /**Priority */
	);

}

// Creates the html for the custom fields in the metabox
// Specified inside the add_meta_box function inside the slb_add_subscriber_metaboxes function 
function slb_subscriber_metabox(){
	/* wp_nounce is a security measure for a database to protect from attacks 
	The nonce field is used to validate that the contents of the form came from the location on the current site and not somewhere else. 
	?? The course explains the nounce value changes as you refresh your page and add new subscribers but when using inspect element it doesn't seem to change for me - this might indicate a problem, I am not sure.
	*/
	// First argument specifies an action - __FILE__ applies to the file you are currently in -somehow using the page we are on as an action ??
	// The basename() function in PHP is an inbuilt function which is used to return the base name of a file 
	// if the path of the file is provided as a parameter to the function. 
	// E.g. basename("/etc/sudoers.d"); returns sudoers.d 
	// Second argument specifies the name for the field - used to refer back to it later.
	
	wp_nonce_field( basename(__FILE__), 'slb_subscriber_nonce' );
	
	// I still don't understand pulling the global variable into the function scope??
	global $post;
	$post_id = $post->ID;

	$first_name = (!empty(get_post_meta($post_id, 'slb_first_name', true))) ? get_post_meta($post_id, 'slb_first_name', true) : '';
	$last_name = (!empty(get_post_meta($post_id, 'slb_last_name', true))) ? get_post_meta($post_id, 'slb_last_name', true) : '';
	$email = (!empty(get_post_meta($post_id, 'slb_email', true))) ? get_post_meta($post_id, 'slb_email', true) : '';
	$lists = (!empty(get_post_meta($post_id, 'slb_list', false))) ? get_post_meta($post_id, 'slb_list',false) : [];

	?>

	<style>
		.slb-field-container label span {
			color: red;
		}
	</style>

	<div>
		<div class="slb-field-container">
			<p>
			<label>First Name <span>*</span></label>
			<input type="text" name="slb_first_name" require="required" class="widefat" 
			value = "<?php
				echo $first_name;
			?>" />
			</p>
		</div>
		<div class="slb-field-container">
			<p>
			<label>Last Name <span>*</span></label>
			<input type="text" name="slb_last_name" require="required" class="widefat" 
			value = "<?php
				echo $last_name;
			?>" />
			</p>
		</div>
	</div>
	<div class="slb-field-row">
		<div class="slb-field-container">
			<p>
			<label>Email <span>*</span></label>
			<input type="email" name="slb_email" require="required" class="widefat" 
			value = "<?php
				echo $email;
			?>" />
			</p>
		</div>
		<div>
			<p>
			<label>Lists</label>
			<ul>
				<?php
				
				// ?? Still don't understand pulling global variables into the function's scope
				// $wpdb is the WordPress database access abstraction class.
				global $wpdb;

				//$list_query is an array. Each index key has a value which is an object which contains a list ID and a list name (post_title). must be a table with column names, rows and values
				// The IDs are system generated and titles are specified by admin
				// Note $wpdb->posts not wp_posts as seen in the databse??
				$list_query = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'slb_list' AND post_status IN ('publish','draft')");
				
				if( !is_null($list_query) ) {
					
					// Each $list is a row from the table and has an ID and a post_title as per the query
					foreach($list_query as $list ){
						$checked = (in_array($list->ID, $lists)) ? 'checked="checked" ' : '';
						// slb_list is the name of the custom post type, slb_list[] is the name of the array that holds the lists the user checked.
						echo '<li><label><input type="checkbox" name="slb_list[]" value="'. $list->ID .'" '.$checked.' /> '. $list->post_title .'</label></li>';

					}

				}

				?>
			</ul>
			</p>
		</div>
	</div>
	<?php
}

// Adding data to databse - capture fields from our form and save them after they have been submitted
// $post_id, $post, $update are passed as parameters through the save_post hook.
function slb_save_slb_subscriber_meta($post_id, $post, $update) {

	// Is $_POST is an associative array? Where is it being accesed from??
	// Verify nonce - if not set in this post or not correct then stops it from doing anything else
	// wp_verify_nonce takes the field the nonce is in as a fisrt argument and an action as a second argument ?? Also don't really understand this
	// How is slb_subscriber_nonce available here when it was declared inside the scope of a separate function??
	if( !isset( $_POST['slb_subscriber_nonce'] ) || !wp_verify_nonce( $_POST['slb_subscriber_nonce'], basename(__FILE__) ) ) {
		// Why return $post_id instead of just returning nothing??
		return $post_id;
	}

	// get the post type object using the $post argument passed to the function
	$post_type = get_post_type_object($post->post_type);

	// check if the current user has permission to edit the post, if they don't return the $post_id to prevent anything from happening
	// current_user_can is an inbuilt WordPress function
	// First argument is the capability (cap) and second is the object id.
	if( !current_user_can($post_type->cap->edit_post, $post_id) ) {
		return $post_id;
	}

	// get the posted data and sanatize it - get fields through the form and values are safe for our database
	// Here $_POST is used to access all info entered into the custome fields on the subsriber post type
	// update_post_meta below only works if the meta_key has already been created - where is this being done?? Because it must already be set here by the look of it?
	// The key must be set during the add_metadata function - slb_subscriber_metabox() called by add_meta_box()
	$first_name = ( isset($_POST['slb_first_name']) ) ? sanitize_text_field($_POST['slb_first_name']) : '';
	$last_name = ( isset($_POST['slb_last_name']) ) ? sanitize_text_field($_POST['slb_last_name']) : '';
	$email = ( isset($_POST['slb_email']) ) ? sanitize_text_field($_POST['slb_email']) : '';
	// Don't understand what is happening here in terms of slb_list as an array which is also the name of the custom post type?
	// I know the idea is to save the various user-selected lists from their subscription options
	$lists = ( isset($_POST['slb_list']) && is_array($_POST['slb_list']) ) ? (array) $_POST['slb_list'] : [];


	global $wpdb;

	// Returns all current subscriber emails
	$email_query = $wpdb->get_col('SELECT meta_value FROM wp_postmeta WHERE meta_key = "slb_email"');

	$email_found = false;
	// Checks if current entered email is in this list
	if (in_array($email, $email_query))
	{
		$email_found = true;
		// change post published message to an error message??
	}

	// Don't add subscriber info to database if the email has been found and the subscriber isn't being updated
	if( $email_found && ($post->post_date == $post->post_modified)) {
		return $post_id;
	}

	// Still need a pop up message to explain the error if you added a new subscriber using an email that is already on the list
	// Still creates an empty post if you break the rules
	// This isn't working yet... ?? Can't find an appropriate hook yet.

	function publish_success_msg() {
		echo '<div class="notice is-dismissible notice-success">
		<p>Post published</p>
		</div>';
	}

	function publish_error_msg() {
		echo '<div class="notice is-dismissible notice-error">
		<p>Post failed to publish</p>
		</div>';
	}

	// Only allow updates using the subscribers update functionality or the new addition of original emails (as in a new subscriber)
	// Had to use $post->post_date != $post->post_modified to check if an existing subscriber was being modified
	if(!$email_found || ($post->post_date != $post->post_modified)){

		// update post meta
		update_post_meta($post_id, 'slb_first_name', $first_name);
		update_post_meta($post_id, 'slb_last_name', $last_name);
		update_post_meta($post_id, 'slb_email', $email);

		// delete the existing list meta for this post
		delete_post_meta( $post_id, 'slb_list');

		// add new list meta
		if(!empty($lists)){
			foreach( $lists as $index=>$list_id ) {

				// add list relational meta value
				add_post_meta( $post_id, 'slb_list', $list_id, false ); // NOT unique meta key

			}
		}
	}
	else{
		
		// change post published message to an error message??
	}

}

function slb_edit_post_change_title(){

	global $post;

	if($post && $post->post_type == 'slb_subscriber') {

		// Edits subscriber list title
		add_filter('the_title','slb_subscriber_title',100,2);

	}

}

function slb_subscriber_title($title, $post_id) {
	$new_title = get_post_meta($post_id,'slb_first_name',true) .' '. get_post_meta($post_id,'slb_last_name',true). ' ' . get_post_meta($post_id,'slb_email',true) ;

	return $new_title;
}
