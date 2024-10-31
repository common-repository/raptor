<?php
/*
Plugin Name: Raptor
Plugin URI: https://wordpress.org/plugins/raptor/
Version: 1.1
Author: pipdig
Description: Deploy a super scary velociraptor on failed login attempts.
Author URI: https://www.pipdig.co/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: raptor

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

if ( !defined('ABSPATH') ) {
	exit;
}

/**
* Load text domain.
*
* @since 1.0
*/
function raptor_textdomain() {
	load_plugin_textdomain('raptor', false, 'raptor/languages');
}
add_action('plugins_loaded', 'raptor_textdomain');


/**
* ...display raptor on failed admin login attempts
*
* @since 1.0
*/
function raptor_login_clever_girl() {
	?>
	<script>
	jQuery(document).ready(function($) {
		(function($) {

		    $.fn.raptor = function(options) {

			   var defaults = {  
				  enterOn: 'timer',
				  delayTime: 200
				  };
			   
			   var options = $.extend(defaults, options); 
			
			   return this.each(function() {

					var raptorImageMarkup = '<img id="elRaptor" style="display: none" alt="Raptor" src="https://pipdigz.co.uk/img/raptor.png" />'
					var raptorAudioMarkup = '<audio id="elRaptorShriek" preload="auto"><source src="<?php echo plugin_dir_url(__FILE__); ?>/raptor-sound.mp3" /><source src="<?php echo plugin_dir_url(__FILE__); ?>/raptor-sound.ogg" /></audio>';	
					var locked = false;
					
					$('body').append(raptorImageMarkup).append(raptorAudioMarkup);
					var raptor = $('#elRaptor').css({
						"position":"fixed",
						"bottom": "-700px",
						"right" : "0",
						"display" : "block"
					})
					
					function init() {
						locked = true;
					
						function playSound() {
							document.getElementById('elRaptorShriek').play();
						}
						playSound();
										
						raptor.animate({
							"bottom" : "0"
						}, function() { 			
							$(this).animate({
								"bottom" : "-130px"
							}, 100, function() {
								var offset = (($(this).position().left)+400);
								$(this).delay(300).animate({
									"right" : offset
								}, 2000, function() {
									raptor = $('#elRaptor').css({
										"bottom": "-700px",
										"right" : "0"
									})
									locked = false;
								})
							});
						});
					}
					setTimeout(init, options.delayTime);
			   });
		    }
		})(jQuery);
		
		$('#wp-submit').raptor();
		$('#login').fadeOut(700);
	});
	</script>
	<?php
}
function raptor_failed_login() { // Does not fire if user/password field is left blank.
	$username = strip_tags($_POST['log']);
	//if ($username == 'admin') {
		wp_enqueue_script('jquery');
		add_action('login_footer', 'raptor_login_clever_girl', 999);
		$raptor_attacks = get_option('raptor_attacks');
		if (empty($raptor_attacks[$username])) {
			$raptor_attacks[$username] = 1;
		} else {
			$raptor_attacks[$username]++;
		}
		update_option('raptor_attacks', $raptor_attacks);
	//}
}
add_action('wp_login_failed', 'raptor_failed_login');


/**
* Dashboard widget with login attempts (raptor attacks)
*
* @since 1.0
*/
function raptor_dash_widgets() {
	add_meta_box( 
		'raptor_dash_widget',
		__('Raptor Kill Count', 'raptor'),
		'raptor_dash_widget_func',
		'dashboard',
		'side',
		'high'
	);
}
add_action( 'wp_dashboard_setup', 'raptor_dash_widgets' );

function raptor_dash_widget_func() {
	
	if (current_user_can('manage_options')) {
		// clear stats if button clicked
		if (isset($_POST['raptor_clear_log'])) {
			delete_option('raptor_attacks');
			echo '<div id="message" class="updated fade"><p>'. __('Raptor attack stats cleared', 'raptor'). '</p></div>';
		}
	}
	
	$raptor_attacks = get_option('raptor_attacks');
	
	if (!empty($raptor_attacks)) {
		arsort($raptor_attacks);
		echo '<p>'.__('Top 5 usernames which have been <a href="https://www.youtube.com/watch?v=S7Pu6u33aVc" target="_blank">eaten by a velociraptor</a>:', 'raptor').'</p>';
		$i = 0;
		foreach ($raptor_attacks as $username => $attacks) {
			echo '<p>'.$username.' - '.$attacks.'</p>';
			if (++$i == 5) break;
		}
	
		if (current_user_can('manage_options')) {
		?>
			<form action="index.php" method="post">
				<?php wp_nonce_field('raptor-nonce'); ?>
				<input type="hidden" value="true" name="raptor_clear_log" />
				<p class="submit">
					<input name="submit" class="button" value="<?php _e('Clear stats', 'raptor'); ?>" type="submit" />
				</p>
			</form>
		<?php
		}
	} else {
		echo '<p>'.__("No raptor attacks yet! So here's a quote from <a href='http://www.imdb.com/character/ch0002047/' target='_blank'>Robert Muldoon</a>:", 'raptor').'</p>';
		echo '<p style="font-style:italic">"They show extraordinary intelligence, even problem-solving intelligence. Especially the big one. We bred eight originally, but when she came in she took over the pride and killed all but two of the others. That one... when she looks at you, you can see she\'s working things out."</p>';
	}
}
