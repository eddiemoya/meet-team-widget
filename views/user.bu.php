<div class="member-wrapper clearfix">
    <?php 
    	
    	$experts_settings = array(
				"user_id" => $user->ID, 
				//"width" => 'span4', 
				//"titling" => true, 
				//"show_name" => false, 
				//"show_address" => false
			);
    	
			
			if ( ( $show_specializations === 'on' ) && ( !empty( $user->categories ) ) ) {
				$experts_settings['specializations'] = $user->categories;
			}
			
			if ( $user->most_recent_post_date ) {
				$experts_settings['last_posted'] = $user->most_recent_post_date;
			}
			
			if ( $show_response_stats === 'on' ) {
				$experts_settings['stats'] = array(
					"answers"		=> $user->answer_count . ' ' . _n( 'answer', 'answers', $user->answer_count ),
					"posts"			=> $user->post_count . ' ' . _n( 'post', 'posts', $user->post_count ),
					"comments"	=> $user->comment_count . ' ' . _n( 'comment', 'comments', $user->comment_count )
				);
			}
			
    	get_partial( 'parts/crest', $experts_settings ); 
    	//get_partial( 'parts/crest', array( "user_id" => $user->ID, "width" => 'span4', "titling" => true, "show_name" => false, "show_address" => false ) ); 
    
    ?>
    
</div>