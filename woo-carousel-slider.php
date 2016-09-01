<?php
/*
 * Plugin Name: Woo Carousel Slider
 * Plugin URI: http://pjdecocq.nl
 * Description: Simple Woocommerce carousel slider, available options are: N.o. slides, autoplay, fade/slide, Slide speed and you're able to turn off/on content in the slide itself.
 * Version: 1.0
 * Author: Paul de Cocq
 * Author URI: http://pjdecocq.nl
*/

defined( 'ABSPATH' ) or die('File cannot be accessed directly!');

require_once('wc_slider-admin.php');

if(!function_exists( 'wc_slider_add_settings_link' ) ) {
	function wc_slider_add_settings_link( $links ) {
	    $settings_link = '<a href="options-general.php?page=wc_carousel_slider">' . __( 'Settings' ) . '</a>';
	    array_push( $links, $settings_link );
	  	return $links;
	}
	$plugin = plugin_basename( __FILE__ );
	add_filter( "plugin_action_links_$plugin", 'wc_slider_add_settings_link' );
}

if( !class_exists( 'wcSlider' ) ) {
 
 		class wcSlider {
 		
		/**
		 * Plugin name
		 * @var string 
		 */
		protected $tag = 'wc_slider';
		
		/**
		 * Plugin user friendly display name
		 * @var string
		 */
		protected $name = 'Woo Carousel Slider';
		
		/**
		 * Plugin version
		 * @var string
		 */	
		protected $version = '1.0';
		
		/**
		 * Plugin option array to pass jquery to frontend
		 * @var array()
		 */
		protected $options = array();
		
		/**
		 * Settings (optional in first version)
		 * @var array()
		 */
		protected $settings = array(
			'typeOfSlides'	=> array(
				'title'			=> 'Type of slides',
				'description'	=> '',
				'type'			=> 'select',
				'sort'			=> array('post', 'page', 'product') // default values
			),			
			'fade'	=> array(
				'title'			=> 'Fade',
				'description'	=> 'uncheck to disable',
				'type'			=> 'checkbox'
			),
			'dots'	=> array(
				'title'			=> 'Pagination',
				'description'	=> 'uncheck to disable',
				'type'			=> 'checkbox'
			),
			'autoplay'	=> array(
				'title'			=> 'Autoplay',
				'description'	=> 'autoplay slider',
				'type'			=> 'checkbox'
			),
			'autoplaySpeed'	=> array(
				'title'			=> 'Autoplay speed',
				'description'	=> 'in milliseconds (default 2000ms)',
				'validator'		=> 'numeric',
				'placeholder'	=> 2000
			),
			'slidesToShow' => array(
				'title'			=> 'Slides to show',
				'description' 	=> 'Visible slides',
				'validator' 	=> 'numeric',
				'placeholder' 	=> 1
			),
			'slidesToScoll' => array(
				'title'			=> 'Slides to scroll',
				'description'	=> 'Number of slides to scroll whilst scrolling',
				'validator'		=> 'numeric',
				'placeholder'	=> 1
			)
		);

		/**
		 * Init the plugin by loading default values and/or actions
		 * @access public
		 */
		public function __construct( )
		{			
			if ( $options = get_option( $this->tag ) ) {
				$this->options = $options;
			}
			add_shortcode( $this->tag, array( &$this, 'shortcode' ) );
			if ( is_admin() ) {
				add_action( 'admin_menu', 'wc_slider_add_admin_menu' );
				add_action( 'admin_init', array( &$this, 'settings') );
			}
		}
		
		public function settings() {
				
			$section = $this->tag;
			add_settings_section(
				$this->tag . '_settings_section',
				$this->name . ' settings',
				function () {
					echo '<p>Configuratie opties voor de ' . esc_html( $this->name ) . ' plugin.</p>';
				},
				$section
			);
			foreach( $this->settings as $id => $options ) {
				$options['id'] = $id;
				add_settings_field(
					$this->tag . '_' . $id . '_settings',
					$options['title'],
					array( &$this, 'settings_field'),
					$section,
					$this->tag . '_settings_section',
					$options
				);
			}
			register_setting(
				$section,
				$this->tag,
				array( &$this, 'settings_validate')
			);
		}
		
		/**
		 * Append a settings field to the the fields section.
		 *
		 * @access public
		 * @param array $args
		 */
		public function settings_field( array $options = array() )
		{	
			$atts = array(
				'id' => $this->tag . '_' . $options['id'],
				'name' => $this->tag . '[' . $options['id'] . ']',
				'type' => ( isset( $options['type'] ) ? $options['type'] : 'text' ),
				'value' => ( array_key_exists( 'default', $options ) ? $options['default'] : null )
			);
			if ( isset( $this->options[$options['id']] ) ) {
				$atts['value'] = $this->options[$options['id']];
			}			
			if ( isset( $options['placeholder'] ) ) {
				$atts['placeholder'] = $options['placeholder'];
			}
			if ( isset( $options['type'] ) && $options['type'] == 'checkbox' ) {
				if ( $atts['value'] ) {
					$atts['checked'] = 'checked';
				}	
				$atts['value'] = true;
			}
			if( isset( $options['sort'] ) ) {
				$post_types = get_post_types( $args = array('public' => true, '_builtin' => false), 'names', 'and' );
				foreach($post_types as $type) {
					array_push($options['sort'], $type);
				}
			}
			
			array_walk( $atts, function( &$item, $key ) {
				$item = esc_attr( $key ) . '="' . esc_attr( $item ) . '"';
			} );
			
			?>
			<label>
				<?php
					if( isset($options['type']) && $options['type'] == 'select' ) {
						echo '<select '.$atts['name'].'>';	
						for($i=0;$i < count($options['sort']);$i++){
						?>
							<option value="<?= $options['sort'][$i] ?>" <?php selected($this->options['typeOfSlides'], $options['sort'][$i]) ?>><?= $options['sort'][$i] ?></option>					
						<?php
						}
						echo '</select>';
					}else{
						echo '<input '.implode( ' ', $atts ).' />';
					}
				?>
				
				<?php if ( array_key_exists( 'description', $options ) ) : ?>
				<?php esc_html_e( $options['description'] ); ?>
				<?php endif; ?>
			</label>
			<?php
		}

		/**
		 * Validate the settings saved.
		 *
		 * @access public
		 * @param array $input
		 * @return array
		 */
		public function settings_validate( $input )
		{
			$errors = array();
			foreach ( $input AS $key => $value ) {
				if ( $value == '' ) {
					unset( $input[$key] );
					continue;
				}
				$validator = false;
				if ( isset( $this->settings[$key]['validator'] ) ) {
					$validator = $this->settings[$key]['validator'];
				}
				switch ( $validator ) {
					case 'numeric':
						if ( is_numeric( $value ) ) {
							$input[$key] = intval( $value );
						} else {
							$errors[] = $key . ' must be a numeric value.';
							unset( $input[$key] );
						}
					break;
					default:
						 $input[$key] = strip_tags( $value );
					break;
				}
			}
			if ( count( $errors ) > 0 ) {
				add_settings_error(
					$this->tag,
					$this->tag,
					implode( '<br />', $errors ),
					'error'
				);
			}
			return $input;
		}
		
		/**
		 * Validate the settings saved.
		 *
		 * @access public
		 * @param array $input
		 * @return array
		 */
		public function get_products( $number_of_posts ) {
				
			$item_args = array(
				'post_type'		=> $this->options['typeOfSlides'],
				'post_status' 	=> 'publish',
				'posts_per_page'=> $number_of_posts,
				'orderby'		=> 'date',
				'order'			=> 'ASC'
			);
			$items = get_posts( $item_args );
			return $items;
			
		}
		
		/**
		 * Allow the teletype shortcode to be used.
		 *
		 * @access public
		 * @param array $atts
		 * @param string $content
		 * @return string
		 */
		public function shortcode( $atts, $content = null ) 
		{
			
			extract( shortcode_atts( array(
				'number' => 2,
				'title'	 => 1, // Disable or Enable title
				'class'  => false,
				'width'	 => ''
			), $atts));

			$this->_enqueue();
			
			$styles = [];
			if( is_numeric( $number ) ) {
				$styles[] = esc_attr( $number );
			}
			
			if( is_numeric( $width ) ) {
				$slider_width = esc_attr( $width );
			}
			
			$title = esc_attr($title);
			if( $title === 'false' ) $title = false;
			$title = (bool) $title;

			// Build list of class names (default: .wc-slider)
			$classes = array(
				$this->tag
			);
			if( !empty( $class ) ) {
				$classes[] = esc_attr( $class );
			}

			// Start the OBJECT html
			ob_start();
			?>
			<div class="slide-container" <?= (isset($slider_width)?'style="width:'.$slider_width.'px"':'') ?>>
				<div class="<?php esc_attr_e( implode( ' ', $classes ) ); ?>">
				<?php
					foreach($this->get_products($number) as $item ){
						$slide_url = wp_get_attachment_url( get_post_thumbnail_id($item->ID) );
						
						echo '<div><a href="'.$item->guid.'">';
						echo '<img src="'.$slide_url.'" alt="'.$item->post_title.'" />';
						echo ($title?'<h3>'.$item->post_title.'</h3>':'');
						echo '<br>Producten item';
						echo '</a></div>';
						
					}
				?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
		
		/**
		 * Require te needed script & styles
		 * @access protected
		 */
		protected function _enqueue() {
			
			$plugin_dir = plugin_dir_url( __FILE__ );
			if( !wp_style_is( $this->tag, 'enqueued' ) ) {
				wp_enqueue_style(
					$this->tag,
					$plugin_dir . 'css/'.$this->tag.'.css',
					array(),
					$this->version
				);
				wp_enqueue_style(
					$this->tag . '-theme',
					$plugin_dir . 'css/'.$this->tag.'-theme.css',
					array(),
					$this->version
				);
			}
			if( !wp_script_is( $this->tag, 'enqueued' ) ) {
				wp_enqueue_script( 'jquery' );	
				wp_enqueue_script(
					'jquery-'.$this->tag,
					$plugin_dir . 'js/jquery.wc_slider.min.js',
					array( 'jquery' ),
					'1.6.0'
				);
				wp_register_script(
					$this->tag,
					$plugin_dir . 'js/'.$this->tag.'.js',
					array( 'jquery-' . $this->tag ),
					$this->version
				);
				$options = array_merge( array(
					'selector' => '.' .$this->tag,
				), $this->options );
				wp_localize_script( $this->tag, $this->tag, $options);
					wp_enqueue_script( $this->tag );
				}

			}			
 
		} // end of class statement
	
	new wcSlider;
}