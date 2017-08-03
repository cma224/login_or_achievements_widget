<?php


//widget displays login or achievements for the logged in user
class login_or_achievements_widget extends WP_Widget {

    //process the new widget
    function login_or_achievements_widget() {
        $widget_ops = array(
            'classname' => 'login_or_achievements_class',
            'description' => __( 'Displays achievements if the user is logged in or a login box if the user is logged out.', 'badgeos-login-or-achievements' )
        );
        $this->WP_Widget( 'login_or_achievements_widget', __( 'BadgeOS Login or Achievements', 'badgeos-login-or-achievements' ), $widget_ops );
    }

    //build the widget settings form
    function form( $instance ) {
        $defaults = array( 'title' => __( 'Login or Achievements', 'badgeos-login-or-achievements' ), 'number' => '10', 'point_total' => '', 'set_achievements' => '' );
        $instance = wp_parse_args( (array) $instance, $defaults );
        $title = $instance['title'];
        $number = $instance['number'];
        $point_total = $instance['point_total'];
        $set_achievements = ( isset( $instance['set_achievements'] ) ) ? (array) $instance['set_achievements'] : array();
        ?>
        <p><label><?php _e( 'Title', 'badgeos-login-or-achievements' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>"  type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>
        <p><label><?php _e( 'Number to display (0 = all)', 'badgeos-login-or-achievements' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'number' ); ?>"  type="text" value="<?php echo absint( $number ); ?>" /></label></p>
        <p><label><input type="checkbox" id="<?php echo $this->get_field_name( 'point_total' ); ?>" name="<?php echo $this->get_field_name( 'point_total' ); ?>" <?php checked( $point_total, 'on' ); ?> /> <?php _e( 'Display user\'s total points', 'badgeos-login-or-achievements' ); ?></label></p>
        <p><?php _e( 'Display only the following Achievement Types:', 'badgeos-login-or-achievements' ); ?><br />
            <?php
            //get all registered achievements
            $achievements = badgeos_get_achievement_types();

            //loop through all registered achievements
            foreach ( $achievements as $achievement_slug => $achievement ) {

                //hide the step CPT
                if ( $achievement['single_name'] == 'step' )
                    continue;

                //if achievement displaying exists in the saved array it is enabled for display
                $checked = checked( in_array( $achievement_slug, $set_achievements ), true, false );

                echo '<label for="' . $this->get_field_name( 'set_achievements' ) . '_' . esc_attr( $achievement_slug ) . '">'
                    . '<input type="checkbox" name="' . $this->get_field_name( 'set_achievements' ) . '[]" id="' . $this->get_field_name( 'set_achievements' ) . '_' . esc_attr( $achievement_slug ) . '" value="' . esc_attr( $achievement_slug ) . '" ' . $checked . ' />'
                    . ' ' . esc_html( ucfirst( $achievement[ 'plural_name' ] ) )
                    . '</label><br />';

            }
            ?>
        </p>
    <?php
    }

    //save and sanitize the widget settings
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['number'] = absint( $new_instance['number'] );
        $instance['point_total'] = ( ! empty( $new_instance['point_total'] ) ) ? sanitize_text_field( $new_instance['point_total'] ) : '';
        $instance['set_achievements'] = array_map( 'sanitize_text_field', $new_instance['set_achievements'] );

        return $instance;
    }

    //display the widget
	function widget( $args, $instance ) {
		global $user_ID;

		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( !empty( $title ) ) { echo $args['before_title'] . $title . $args['after_title']; };

		//user must be logged in to view earned badges and points
		if ( is_user_logged_in() ) {

			//display user's points if widget option is enabled
			if ( $instance['point_total'] == 'on' ) {
				echo '<p class="badgeos-total-points">' . sprintf( __( 'My Total Points: %s', 'badgeos' ), '<strong>' . number_format( badgeos_get_users_points() ) . '</strong>' ) . '</p>';
			}

			$achievements = badgeos_get_user_achievements(array('display'=>true));

			if ( is_array( $achievements ) && ! empty( $achievements ) ) {

				$number_to_show = absint( $instance['number'] );
				$thecount = 0;

				wp_enqueue_script( 'badgeos-achievements' );
				wp_enqueue_style( 'badgeos-widget' );

				//load widget setting for achievement types to display
				$set_achievements = ( isset( $instance['set_achievements'] ) ) ? $instance['set_achievements'] : '';

				//show most recently earned achievement first
				$achievements = array_reverse( $achievements );

				echo '<ul class="widget-achievements-listing">';
				foreach ( $achievements as $achievement ) {

					//verify achievement type is set to display in the widget settings
					//if $set_achievements is not an array it means nothing is set so show all achievements
					if ( ! is_array( $set_achievements ) || in_array( $achievement->post_type, $set_achievements ) ) {

						//exclude step CPT entries from displaying in the widget
						if ( get_post_type( $achievement->ID ) != 'step' ) {

							$permalink  = get_permalink( $achievement->ID );
							$title      = get_the_title( $achievement->ID );
							$img        = badgeos_get_achievement_post_thumbnail( $achievement->ID, array( 50, 50 ), 'wp-post-image' );
							$thumb      = $img ? '<a class="badgeos-item-thumb" href="'. esc_url( $permalink ) .'">' . $img .'</a>' : '';
							$class      = 'widget-badgeos-item-title';
							$item_class = $thumb ? ' has-thumb' : '';

							// Setup credly data if giveable
							$giveable   = credly_is_achievement_giveable( $achievement->ID, $user_ID );
							$item_class .= $giveable ? ' share-credly addCredly' : '';
							$credly_ID  = $giveable ? 'data-credlyid="'. absint( $achievement->ID ) .'"' : '';

							echo '<li id="widget-achievements-listing-item-'. absint( $achievement->ID ) .'" '. $credly_ID .' class="widget-achievements-listing-item'. esc_attr( $item_class ) .'">';
							echo $thumb;
							echo '<a class="widget-badgeos-item-title '. esc_attr( $class ) .'" href="'. esc_url( $permalink ) .'">'. esc_html( $title ) .'</a>';
							echo '</li>';

							$thecount++;

							if ( $thecount == $number_to_show && $number_to_show != 0 ) {
								break;
							}

						}

					}
				}

				echo '</ul><!-- widget-achievements-listing -->';

			}

		} else {

			//user is not logged in so display a message
				
		
			_e(wp_login_form( array(
				'echo'           => true,
				'remember'       => true,
				'redirect'       => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
				'form_id'        => 'loginform',
				'id_username'    => 'user_login',
				'id_password'    => 'user_pass',
				'id_remember'    => 'rememberme',
				'id_submit'      => 'wp-submit',
				'label_username' => __( 'Username' ),
				'label_password' => __( 'Password' ),
				'label_remember' => __( 'Remember Me' ),
				'label_log_in'   => __( 'Log In' ),
				'value_username' => '',
				'value_remember' => false
			)));
			
		}

		echo $args['after_widget'];
	}

}
