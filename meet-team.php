<?php
/**
 * @package meet-team
 */
/*
Plugin Name: Meet the Community Team
Description: Creates a widget for viewing communities experts
Version: 1.0
Author: Harry Oh, Eddie Moya, Dan Crimmins
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

/**
 * A widget that creates a customizable list of users. 
 *
 * @author Harry Oh, Eddie Moya, Dan Crimmins
 * @version 1.0
 */
class Meet_Team_Widget extends WP_Widget {

	/**
	 * The name of this widget - shown in WP Admin widget section.
	 */
	var $widget_name = 'Meet the Community Team Widget';
	 
	/**
	 * This widget's ID
	 */
	var $id_base = 'meet_team_widget';

	/**
	 * Description of this widget
	 */
	public $description = 'A widget for interfacing with communities experts';

	/**
	 * Widget's CSS class name.
	 */
	public $classname = 'meet-team-widget';

	/**
	 *
	 *
	 * @author Eddie Moya
	 * @return void
	 */
	public function Meet_Team_Widget() {
		$widget_ops = array(
				'description' => $this->description,
				'classname' => $this->classname
		);
		
		add_action('admin_print_scripts-widgets.php', array($this, 'enqueue'));
		add_action('admin_print_scripts-post.php', array($this, 'enqueue'));
		add_action('wp_ajax_meet_team_user_query_flush_cache', array($this, 'meet_team_user_query_flush_cache'));
		add_action('set_user_role', array($this, 'meet_team_user_updated'), 10, 2);
		add_action('members_pre_edit_role_form', array($this, 'roles_edited'));

		parent::WP_Widget($this->id_base, $this->widget_name, $widget_ops);
	}

	/**
	 * 
	 */
	public function enqueue() {
		wp_register_script('meet-team', plugins_url('meet-team/meet-team.js'), array('jquery'));
		wp_enqueue_script('meet-team');
	}
	
	/**
	 * Self-registering widget method.
	 *
	 * This can be called statically.
	 *
	 * @author Eddie Moya
	 * @return void
	 */
	public function register_widget() {
		if (Meet_Team_Widget::are_plugins_available()) {
			add_action('widgets_init', create_function( '', 'register_widget("' . __CLASS__ . '");' ));
		}
	}

	public function roles_edited(){
		if( isset($_POST['new-cap']) ||  isset($_POST['role-caps']) || isset($_POST['submit']) ) {
			set_transient('meet_team_widget_user_query_uptodate', 0, 0);
		}

	}

	public function meet_team_user_updated($user_id, $role){
		$role = get_role($role);
		//$old_role = get_role($old_role);

		if($role->name == 'expert' )	{
			set_transient('meet_team_widget_user_query_uptodate', 0, 60*60*24*7);
		}
	}
	/**
	 * @author Eddie Moya
	 */
	public function meet_team_user_query_flush_cache(){

		set_transient('meet_team_widget_user_query_in_progress', 1, 60*60*1);
		
		ignore_user_abort(true);
		//set_time_limit(0);
		global $wpdb;

		delete_transient('meet_team_user_query');
		//wp_cache_delete( 'user_query', 'meet_team_widget'  );
		set_transient('meet_team_widget_user_query_uptodate', 0, 0);

		$q = $this->get_user_role_tax_intersection(array('roles' => array('expert')));
		$all_users = $wpdb->get_results($q);

		// if ( !empty($all_users) ){
		// 	wp_cache_set( 'user_query', $all_users, 'meet_team_widget', 60*60*24*7);
		// 	set_transient('meet_team_widget_user_query_uptodate', true, 60*60*24*7);
		// }

		if ( !empty($all_users) ){
			set_transient('meet_team_user_query', $all_users, 60*60*24 *7);
			set_transient('meet_team_widget_user_query_uptodate', 1, 0);
		}

		set_transient('meet_team_widget_user_query_in_progress', 0, 0);
		ignore_user_abort(false);
		exit('Cache Updated');
	}

	/**
	 * Checks whether any of the dependent plugins for this widget have not been activated. For the time begin,
	 * this widget will gracefully unregister itself if any of the dependent plugins are not activated. 
     *
	 * @author Harry Oh
	 *
	 * @return boolean
	 */
	public static function are_plugins_available()
	{
		if (
				//is_plugin_active('communities-user-taxonomies/communities-user-taxonomies.php')
				//&& is_plugin_active('media-categories-2/media-categories.php')
				//&& is_plugin_active('user_meta_taxonomy/user_meta_taxonomy.php')
				//&& is_plugin_active('user-taxonomies/user-taxonomies.php')
				 is_plugin_active('user-titles/user-titles.php')) {

			$are_plugins_active = true;
		} else {
			$are_plugins_active = false;
		}
	
		return $are_plugins_active;
	}

	/**
	 * Prints out experts in accordance to the conditions specified in this widget's admin. There are only 3 
	 * possible conditions in which experts are displayed: 
	 * 
	 * (1) When a page has a category, and display_method is 'automatic'
	 * (2) When a page has no category, but display_method is 'automatic'
	 * (3) Regardless of whether a page has a category, display_method is 'manual'       
	 * 
	 * Finally, the number of experts displayed is specified by $instance['number_of_experts']
	 * 
	 * @author Harry Oh, Eddie Moya, Dan Crimmins
	 * 
	 * @param array $instance - [REQUIRED]
	 * @param string $display_method - [OPTIONAL] 'automatic' or 'manual'
	 */
	private function display_experts($instance, $display_method = 'automatic') {
        extract($instance);
		$current_category = get_query_var('cat');

		// When in a page that has a category
		if ('automatic' === $display_method && '' !== $current_category) {
		
			// Only save the specified number of users
			$users = array_slice(
					get_users_by_taxonomy('category', $current_category),
					0,
					$instance['number_of_experts']
			);

		// When there is no category
		} else if ('automatic' === $display_method && '' == $current_category) {
			
			$all_categories = get_terms('category');
			$total_categories = count($all_categories);
			$users = array();

			// Randomly select users from ALL categories. Random selection algorithm used: 
			// Step 1: Select a random category from $all_categories
			// Step 2: Add all of the users from that category (that have not already been added) onto $users
			// Step 3: If number of users added to $users is still less than the number of experts that was 
			//    	   specified to be shown, then go to Step 1
			// Step 4: Complete
			while (count($users) < $instance['number_of_experts']) {
				$random_index = rand(0, $total_categories - 1);
				$retrieved_users_from_random_category = get_users_by_taxonomy('category', $all_categories[$random_index]);
				
				// Keep adding users until 
				for ($i = 0; $i < count($retrieved_users_from_random_category) && count($users) < $instance['number_of_experts']; $i += 1) {
					if (!in_array($retrieved_users_from_random_category[$i], $users)) {
						$users[] = $retrieved_users_from_random_category[$i];
					}
				}
			}

		// When internal user has manually selected experts to display
		} else if ('manual' === $display_method) {
		
			// Retrieve users selected in the widget admin section
			for ($i = 0; $i < $instance['number_of_experts']; $i += 1) {
				$selected_user_id = $instance['user-' . ($i + 1)]; // starts from $user-1
				$users[] = get_userdata($selected_user_id);
			}
		}


        // Print out the users that were retrieved based from one of the three conditions accounted for by the above 
        // if/elseif/elseif statement - after retrieving values required for front-end
        foreach ($users as $user) {
            $user_meta = get_user_meta($user->ID);
            # $user_badge = get_user_badges($user->ID);
            # $user_badge = $user_badge[0]; // For now, we can only have one badge
            # $user->badge_name = $user_badge->name;
            # $user->badge_image_url = $user_badge->image;

            $user->meta = $user_meta; // We still need this meta info for flexibility
            $user->address = return_address( $user->ID );
            
            // Get the stats
            $user->answer_count  = get_comments( array( 'user_id' => $user->ID, 'status' => 'approved', 'count' => true, 'type' => 'answer' ) );
            $user->comment_count = get_comments( array( 'user_id' => $user->ID, 'status' => 'approved', 'count' => true, 'type' => 'comment' ) );
            $user->post_count    = return_post_count( $user->ID );
            
            // Query database for most recent post date
            $last_post_date = return_last_post_date( $user->ID );

            if ( $last_post_date == 0) {
                $user->most_recent_post_date = false;
                $user->pubdate = false;

    		} else {
    		    $last_activity = ($last_comment_date > $last_post_date) ? $last_comment_date : $last_post_date;
    		    $user->most_recent_post_date = date( "M d, Y", $last_activity );
    		    $user->pubdate = $user->most_recent_post_date;
    		}
            
            $user->categories = get_terms('category', array('include' => $user->meta['um-taxonomy-category']));
        } 

        // Include the VIEW
        include 'views/users.php';
	}
	
	/**
	 * The front end of the widget. Invokes display_experts to do the heavy-lifting. 
	 *
	 * Do not call directly, this is called internally to render the widget.
	 *
	 * @author Harry Oh, Eddie Moya, Dan Crimmins
	 *
	 * @param array $args       [Required] Automatically passed by WordPress - Settings defined when registering the sidebar of a theme
	 * @param array $instance   [Required] Automatically passed by WordPress - Current saved data for the widget options.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		extract($args);
		extract($instance);

		// Automatic or manual? 
		if ($auto === 'on') {
			$this->display_experts($instance);
		} else {
			$this->display_experts($instance, 'manual'); 
		}
	}

	/**
	 * Input validation. 
	 *
	 * @author Harry Oh, Eddie Moya
	 * @uses esc_attr() http://codex.wordpress.org/Function_Reference/esc_attr
	 *
	 * @param array $new_instance   [Required] Automatically passed by WordPress
	 * @param array $old_instance   [Required] Automatically passed by WordPress
	 * @return array|bool Final result of newly input data. False if update is rejected.
	 */
	public function update($new_instance, $old_instance){

		/* Lets inherit the existing settings */
		$instance = $old_instance;

		/* Sanitize input fields */
		foreach($new_instance as $key => $value){
			$instance[$key] = esc_attr($value);
		}

		foreach($instance as $key => $value){
			if($value == 'on' && !isset($new_instance[$key])){
				unset($instance[$key]);
			}
		}

		return $instance;
	}

	/**
	 * Generates the form for this widget, in the WordPress admin area.
	 * Default fields are 
	 *
	 * The use of the helper functions form_field() and form_fields() is not
	 * neccessary, and may sometimes be inhibitive or restrictive.
	 *
	 * @author Harry Oh, Eddie Moya, Dan Crimmins
	 *
	 * @uses wp_parse_args() http://codex.wordpress.org/Function_Reference/wp_parse_args
	 * @uses self::form_field()
	 * @uses self::form_fields()
	 *
	 * @param array $instance [Required] Automatically passed by WordPress
	 * @return void
	 */
	public function form($instance) {

		global $wpdb;
		
		/* Setup default values for form fields - associtive array, keys are the field_id's */
		$defaults = array(
				'title' => 'Meet the Community Team',
				'subtitle' => "Whatever your question or issue, we're here to help",
				'number_of_experts' => '3',
				'category-1' => 'all',
				'category-2' => 'all',
				'category-3' => 'all'
		);

		$instance = wp_parse_args((array) $instance, $defaults);

		/* Basic options: title, select number of experts, response stats, show categories, and automatic selection toggle */
		$fields = array(

				// Title of this widget
				array(
						'field_id' => 'title',
						'type' => 'text',
						'label' => 'Widget Title'
				),

				// Subtitle of this widget
				array(
						'field_id' => 'subtitle',
						'type' => 'text',
						'label' => 'Widget Subtitle'
				),

				// Select number of experts
				array(
						'field_id' => 'number_of_experts',
						'type' => 'select-slim',
						'label' => 'Number of Experts to Display: ',
						'options' => array(
								'1' => 1,
								'2' => 2,
								'3' => 3
						)
				),

                // User response stats options
                array(
                        'field_id' => 'show_response_stats',
                        'type' => 'checkbox',
                        'label' => 'Show response stats'
                ),

                // Option to show categories
                array(
                        'field_id' => 'show_specializations',
                        'type' => 'checkbox',
                        'label' => "Show user's specializations"
                ),
                
                array(
                        'field_id' => 'hide_show_all',
                        'type' => 'checkbox',
                        'label' => "Hide 'show all' link"
                ),

				// Choose automatic expert selection
				array(
						'field_id' => 'auto',
						'type' => 'checkbox',
						'label' => 'Automatically select users by category'
				),
				
				//More link target
				array(
						'field_id' 	=> 'more_link',
						'type'		=> 'select-slim',
						'label'		=> 'More link target: ',
						'options'	=> $this->get_pagelist_options()
				)
		);

        // Display ALL of the above widget options form field
		$this->form_fields($fields, $instance);

        // User selection drop-down menus - two drop-down menus per user, one for selecting category and 
        // the other for selecting the user's username (nicename)
		if ($instance['auto'] == '') {
		    echo '<hr />'; // Form styling: just a line separating widget configuration and user selection

			$category_terms = get_terms('category');
			$length = count($category_terms);
			$categories = array();

			for ($i = 0; $i < $length; $i += 1) {
				$categories[$category_terms[$i]->term_id] = ucfirst($category_terms[$i]->slug);
				$category_term_ids[] = $category_terms[$i]->term_id;
			}

			//Get all users for each tax term
			//$all_users = wp_cache_get( 'user_query', 'meet_team_widget');

			// NOT using transients, because transients may sometimes be stored in the database.
			$all_users = get_transient('meet_team_user_query');
			$in_progress = get_transient('meet_team_widget_user_query_in_progress');
			$cache_uptodate = get_transient('meet_team_widget_user_query_uptodate');

			// echo "<pre>";var_dump($in_progress);echo "</pre>";
			// echo "<pre>";var_dump($cache_uptodate);echo "</pre>";
			// echo "<pre>";print_r($all_users);echo "</pre>";

			if(!$in_progress){
				if( false === $all_users){// || !$cache_uptodate ){
					//Show Cache Buster button
					echo '
					<p class="update-nag">User Cache out of date! <br />	                    
						<img src="images/wpspin_light.gif" class="ajax-feedback" title="" alt="" style="display: none; margin-right:auto;margin-left:auto;">
						<a href="" class="meet-team-widget-flush-user-cache">Click Here to update.</a> 
						<small style="display:block;">(May take several minutes)</small> 
					</p>';
				}
			} else {
					echo '
					<p class="update-nag">User Cache update In Progress! <br />	                    
						<small style="display:block;">(May take several minutes)</small> 
					</p>';
			}
			
			// NOT using transients, because transients may sometimes be stored in the database.
			// $all_users = get_transient('meet_team_user_query');

			// if(false === $all_users){
			// 	$all_users = $wpdb->get_results($q);
			// 	set_transient('meet_team_user_query', $all_users, 60 * 60 * 24 * 7);
			// }

			// $q = $this->get_user_role_tax_intersection(array('roles' => array('expert')));
			// $all_users = $wpdb->get_results($q);//get_users_by_taxonomy('category', $category_term_ids);
			
			/*echo '<pre>';
			var_dump($all_users);
			exit;*/
			
            // Create the exact number of drop-down menus specified in number_of_experts
            if(false !== $all_users){
				for ($i = 1; $i <= $instance['number_of_experts']; $i += 1) {
					if(function_exists('get_users_by_taxonomy')){
						if ($instance['category-' . $i] == 'all') {
							$user_list = $all_users;//get_users_by_taxonomy('category', $category_term_ids);
						} else {
							$user_list = get_users_by_taxonomy('category', array($instance['category-' . $i]));
						}

						$categories = array('all' => 'All Categories') + $categories;
						$this->form_field('category-' . $i, 'select', 'Expert #' . $i, $instance, $categories);
						$this->user_list_form_field($user_list, $instance, $i); // custom form field generating function
					}
				}
			}
		}
	}

	/**
	 * Prints outs a drop-down menu for selecting users to display on the front-end. 
	 * 
	 * This custom form function was used in place of $this->form_field because of the
	 * conditionals involved. 
	 *
	 * @author Harry Oh
	 * 
	 * @param array $user_list
	 * @param array $instance
	 * @param int $i
	 * @return void
	 */
	private function user_list_form_field($user_list, $instance, $i) {
		/*if(isset($instance['user-'.$i]))
			return '';*/
		?>
		<p>
			<select id="<?php echo $this->get_field_id('user-' . $i); ?>"
				name="<?php echo $this->get_field_name('user-' . $i); ?>"
				class="widefat">
				<option value="not-selected"
				<?php selected("not-selected", $instance['user-' . $i]); ?>>
					<?php echo 'Select Expert #'. $i; ?>
				</option>
				<?php foreach ($user_list as $user) :  ?>
				<option value="<?php echo $user->ID; ?>"
				<?php selected($user->ID, $instance['user-' . $i]); ?>>
					<?php echo $user->display_name; ?>
				</option>
				<?php endforeach; ?>
			</select>
		</p>
		<br />
		<?php
	}
	
	/**
	 * Returns associative array of pages for site
	 * 
	 * @param void
	 * @return array - array of pages (pageID => Page Title)
	 */
	private function get_pagelist_options() {
		
		$pages = get_pages();
		$opts = array();
		
		foreach($pages as $page) {
			
			$opts[$page->ID] = $page->post_title;
		}
		
		return $opts;
	}

	/**
	 * Helper function - does not need to be part of widgets, this is custom, but
	 * is helpful in generating multiple input fields for the admin form at once.
	 *
	 * This is a wrapper for the singular form_field() function.
	 *
	 * @author Eddie Moya
	 *
	 * @uses self::form_fields()
	 *
	 * @param array $fields     [Required] Nested array of field settings
	 * @param array $instance   [Required] Current instance of widget option values.
	 * @return void
	 */
	private function form_fields($fields, $instance){
		foreach($fields as &$field){
			extract($field);

			$this->form_field($field_id, $type, $label, $instance, $options);
		}
	}
	
	
	
	function get_user_role_tax_intersection($args = array()){
		
        global $wpdb;

        $default_args = array(
            'hide_untaxed' => true,
            'terms'         => array(),
            'roles'         => array(),
        );

        $args = array_merge($default_args, $args);

        $roles = implode("|", $args['roles']);

        $query['SELECT'] = "SELECT DISTINCT u.ID, u.user_login, u.user_nicename, u.user_email, u.display_name, m2.meta_value AS role FROM {$wpdb->users} AS u";

        $query['JOIN'] = array(
            //"JOIN {$wpdb->usermeta} AS m  ON u.ID = m.user_id AND m.meta_key = 'um-taxonomy-category'",
            "JOIN {$wpdb->usermeta} AS m2 ON u.ID = m2.user_id AND m2.meta_key = '{$wpdb->prefix}capabilities' AND m2.meta_value REGEXP '{$roles}'"
        );

        $query['GROUP'] = 'GROUP BY u.ID';
        $query['ORDER'] = 'ORDER BY u.user_nicename';

        $query['ORDER'] = (isset($args['order']))? $args['order'] : 'DESC';

        if($args['hide_untaxed'] == false){
            $query['JOIN'][0] = 'LEFT '. $query['JOIN'][0];
        }

        $query['JOIN'] = implode(' ', $query['JOIN']);

        //print_r($query);
        return  implode(' ', $query);

    }
    

	/**
	 * Helper function - does not need to be part of widgets, this is custom, but
	 * is helpful in generating single input fields for the admin form at once.
	 *
	 * @author Eddie Moya
	 *
	 * @uses get_field_id() (No Codex Documentation)
	 * @uses get_field_name() http://codex.wordpress.org/Function_Reference/get_field_name
	 *
	 * @param string $field_id  [Required] This will be the CSS id for the input, but also will be used internally by wordpress to identify it. Use these in the form() function to set detaults.
	 * @param string $type      [Required] The type of input to generate (text, textarea, select, checkbox]
	 * @param string $label     [Required] Text to show next to input as its label.
	 * @param array $instance   [Required] Current instance of widget option values.
	 * @param array $options    [Optional] Associative array of values and labels for html Option elements.
	 *
	 * @return void
	 */
	private function form_field($field_id, $type, $label, $instance, $options = array()) {

	?>
        <p>
            <?php switch ($type):


                case 'text': ?>
    	            <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?>:</label>
    	            <input id="<?php echo $this->get_field_id( $field_id ); ?>" style="<?php echo $style; ?>" class="widefat" name="<?php echo $this->get_field_name( $field_id ); ?>" value="<?php echo $instance[$field_id]; ?>" />
    	            <?php break;


                    case 'select': ?>
    	            <select onchange="bananas(this)" id="<?php echo $this->get_field_id( $field_id ); ?>"
                            class="widefat meet-team-instant-change" name="<?php echo $this->get_field_name($field_id); ?>">
    		                <?php foreach ( $options as $value => $label ): ?>
    		                    <option value="<?php echo $value; ?>"
                                    <?php selected($value, $instance[$field_id]) ?>>
                                    <?php echo $label ?>
                                </option>
    		                <?php endforeach; ?>
    	            </select>
    	            <?php break;


                    case 'select-slim': ?>
    	                <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?></label>
        	            <select onchange="bananas(this)" class="meet-team-instant-change" id="<?php echo $this->get_field_id( $field_id ); ?>"
                                name="<?php echo $this->get_field_name($field_id); ?>">
            		        <?php foreach ( $options as $value => $display ): ?>
            		            <option value="<?php echo $value; ?>"
                                    <?php selected($value, $instance[$field_id]) ?>>
                                    <?php echo $display ?>
                                </option>
            		        <?php endforeach; ?>
                        </select>
                    <?php break;


                    
                case 'textarea':
                    $rows = (isset($options['rows'])) ? $options['rows'] : '16';
                    $cols = (isset($options['cols'])) ? $options['cols'] : '20'; ?>

                    <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?>:</label>
                    <textarea class="widefat" rows="<?php echo $rows; ?>" cols="<?php echo $cols; ?>"
                            id="<?php echo $this->get_field_id($field_id); ?>"
                            name="<?php echo $this->get_field_name($field_id); ?>">
                            <?php echo $instance[$field_id]; ?>
                    </textarea>
    	            <?php break;


                case 'radio': ?>
                    <?php for ($i = 0; $i < count($options); $i += 1) : ?>
                        <input  type="radio"
                                name="<?php echo $this->get_field_name($field_id); ?>"
                                id="<?php echo $this->get_field_id($field_id); ?>"
                                value="<?php echo $options[$i]; ?>"
                                <?php if ($instance[$field_id] === $options[$i])  : echo 'checked'; endif; ?>/>
                        <label for="<?php echo $this->get_field_id($field_id); ?>"><?php echo $label[$i]; ?></label>
                    <?php endfor; ?>
                    <?php break;


                case 'checkbox': ?>
                    <input type="checkbox" class="checkbox meet-team-instant-change" onclick="bananas(this)"
                            id="<?php echo $this->get_field_id($field_id); ?>"
                            name="<?php echo $this->get_field_name($field_id); ?>"
                            <?php checked( (!empty($instance[$field_id]))); ?> />
                            <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?></label>
    	            <?php break; ?>

    	        <?php endswitch; ?>

    </p><?php
	}
}

Meet_Team_Widget::register_widget();