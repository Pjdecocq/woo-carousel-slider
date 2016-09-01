<?php
/**
 * Add custom option page for Woo Carouse Slider
 * 
 * */

function wc_slider_add_admin_menu(  ) { 
	add_options_page( 'Woo Carousel Slider', 'Woo Carousel Slider', 'manage_options', 'wc_carousel_slider', 'wc_slider_options_page' );
}

function wc_slider_options_page(  ) { 

	?>
	<form action='options.php' method='post'>

		<?php
		settings_fields( 'wc_slider' );
		do_settings_sections( 'wc_slider' );
		submit_button();
		?>

	</form>
	<?php

}

class wcSliderTinyMcE {
	
	protected $slug = 'wc_slider';
	
	public function __csontruct() {
		
		add_action( 'init', array( &$this, 'wc_slider_tinyMcE' ) );
		
	}
	
	public function wc_slider_tinyMcE() {
			
		add_filter( "mce_external_plugins", "wc_slider_add_buttons" );
		add_filter( 'mce_buttons', 'wc_slider_register_buttons' );
		
	}
	
	public function wc_slider_add_buttons( $plugin_array ) {
		
		$plugin_array['tiny_buttons'] = get_template_directory_uri() . '/woo-carousel-slider/js/'.$this->slug.'TinyMcE.js';		
		return $plugin_array;
	}

	public function wc_slider_register_buttons( $buttons ) {
		
		array_push( $buttons, 'wc_slider' );
		return $buttons;
		
	}
	
}

new wcSliderTinyMcE;
