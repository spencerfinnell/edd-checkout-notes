<?php
/**
 * Plugin Name: Easy Digital Downloads - Checkout Notes
 * Plugin URI:  https://github.com/Astoundify/edd-checkout-notes
 * Description: Add a textarea to the checkout form where customers can leave notes about their order.
 * Author:      Astoundify
 * Author URI:  http://astoundify.com
 * Version:     1.0
 * Text Domain: acn
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Astoundify_EDD_Checkout_Notes {

	/**
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Make sure only one instance is only running.
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Start things up.
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Set some smart defaults to class variables. Allow some of them to be
	 * filtered to allow for early overriding.
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 *
	 * @return void
	 */
	private function setup_globals() {
		$this->file         = __FILE__;
		
		$this->basename     = apply_filters( 'acn_plugin_basenname', plugin_basename( $this->file ) );
		$this->plugin_dir   = apply_filters( 'acn_plugin_dir_path',  plugin_dir_path( $this->file ) );
		$this->plugin_url   = apply_filters( 'acn_plugin_dir_url',   plugin_dir_url ( $this->file ) );

		$this->lang_dir     = apply_filters( 'acn_lang_dir',     trailingslashit( $this->plugin_dir . 'languages' ) );

		$this->domain       = 'acn'; 
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 *
	 * @return void
	 */
	private function setup_actions() {
		add_action( 'edd_purchase_form_after_cc_form', array( $this, 'field' ), 10, 999 );
		add_filter( 'edd_payment_meta', array( $this, 'save' ) );
		add_action( 'edd_payment_view_details', array( $this, 'display' ) );

		add_filter( 'edd_settings_extensions', array( $this, 'settings' ) );
		add_filter( 'atcf_csv_cols', array( $this, 'export_columns' ) );
		add_filter( 'atcf_csv_cols_values', array( $this, 'export_columns_values' ), 10, 2 );
		
		$this->load_textdomain();
	}

	/**
	 * Output the field on the bottom of the checkout.
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 *
	 * @return void
	 */
	public function field() {
		global $edd_options;

		$legend      = isset ( $edd_options[ 'acn_section_title' ] ) ? esc_attr( $edd_options[ 'acn_section_title' ] ) : null;
		$label       = isset ( $edd_options[ 'acn_field_label' ] ) ? esc_attr( $edd_options[ 'acn_field_label' ] ) : null;
		$description = isset ( $edd_options[ 'acn_field_description' ] ) ? esc_attr( $edd_options[ 'acn_field_description' ] ) : null;

		ob_start(); 
	?>
		<fieldset class="edd-acn">
			<?php if ( $legend ) : ?>
			<span><legend><?php echo $legend ?></legend></span>
			<?php endif; ?>

			<p id="atcf-edd-address-1-wrap">
				<?php if ( $label ) : ?>
				<label class="edd-label"><?php echo $label; ?></label>
				<?php endif; ?>

				<?php if ( $description ) : ?>
				<span class="edd-description"><?php echo $description; ?></span>
				<?php endif; ?>

				<textarea name="edd_acn" class="edd-input" rows="3" columns="40" style="width: 100%"></textarea>
			</p>
		</fieldset>
	<?php
		echo ob_get_clean();
	}

	/**
	 * When the payment is processed, save the extra data to the payment meta.
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 *
	 * @param array
	 * @return void
	 */
	public function save( $payment_meta ) {
		$payment_meta[ 'edd_acn' ] = isset( $_POST[ 'edd_acn' ] ) ? wp_kses_data( $_POST[ 'edd_acn' ] ) : null;

		return $payment_meta;
	}

	/**
	 * When viewing an order's details via Payment History, output the extra
	 * data that was collected.
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 *
	 * @param int
	 * @return void
	 */
	public function display( $payment_id ) {
		global $edd_options;

		$payment_meta = edd_get_payment_meta( $payment_id );
		$label        = isset ( $edd_options[ 'acn_field_label' ] ) ? esc_attr( $edd_options[ 'acn_field_label' ] ) : null; 

		if ( ! isset( $payment_meta[ 'edd_acn' ] ) )
			return;

		echo '<div class="order-data-column">';
		echo '<h4>' . $label . '</h4>';
		echo wpautop( $payment_meta[ 'edd_acn' ] );
		echo '</div>';
	}

	/**
	 * Add settings fields for the fieldset legend, input label,
	 * and input description.
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 *
	 * @param array $settings
	 * @return array $settings
	 */
	public function settings( $settings ) {
		$settings[ 'acn_settings' ] = array(
			'id'   => 'acn_settings',
			'name' => '<strong>' . __( 'Checkout Notes', 'acn' ) . '</strong>',
			'desc' => null,
			'type' => 'header'
		);

		$settings[ 'acn_section_title' ] = array(
			'id'   => 'acn_section_title',
			'name' => __( 'Section Title', 'acn' ),
			'desc' => null,
			'type' => 'text',
			'size' => 'regular'
		);

		$settings[ 'acn_field_label' ] = array(
			'id'   => 'acn_field_label',
			'name' => __( 'Field Label', 'acn' ),
			'desc' => null,
			'type' => 'text',
			'size' => 'regular'
		);

		$settings[ 'acn_field_description' ] = array(
			'id'   => 'acn_field_description',
			'name' => __( 'Field Description', 'acn' ),
			'desc' => null,
			'type' => 'text',
			'size' => 'regular'
		);

		return $settings;
	}

	/**
	 * Add the extra notes to the export columns.
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 *
	 * @param array
	 * @return array
	 */
	public function export_columns( $columns ) {
		global $edd_options;

		$label = isset ( $edd_options[ 'acn_field_label' ] ) ? esc_attr( $edd_options[ 'acn_field_label' ] ) : null; 

		$columns[ sanitize_title( $label ) ] = $label;

		return $columns;
	}

	/**
	 * Add the extra notes to the export columns.
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 *
	 * @param array
	 * @param int
	 * @return array
	 */
	public function export_columns_values( $values, $payment_id ) {
		global $edd_options;

		$payment_meta = edd_get_payment_meta( $payment_id );
		$label        = isset ( $edd_options[ 'acn_field_label' ] ) ? esc_attr( $edd_options[ 'acn_field_label' ] ) : null;

		$values[ sanitize_title( $label ) ] = isset( $payment_meta[ 'edd_acn' ] ) ? $payment_meta[ 'edd_acn' ] : null;

		return $values;
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since Easy Digital Downloads - Checkout Notes 1.0
	 */
	public function load_textdomain() {
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			return load_textdomain( $this->domain, $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			return load_textdomain( $this->domain, $mofile_local );
		}

		return false;
	}
}

/**
 * Start things up.
 *
 * Use this function instead of a global.
 *
 * $acn = acn();
 *
 * @since Easy Digital Downloads - Checkout Notes 1.0
 */
function acn() {
	return Astoundify_EDD_Checkout_Notes::instance();
}

acn();