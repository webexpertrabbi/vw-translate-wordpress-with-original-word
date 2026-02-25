<?php
/**
 * Language switcher widget for VW Translate.
 *
 * @package VW_Translate
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VW_Translate_Widget
 *
 * Provides a language switcher widget for sidebars.
 *
 * @since 1.0.0
 */
class VW_Translate_Widget extends WP_Widget {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			'vw_translate_switcher',
			__( 'VW Language Switcher', 'vw-translate' ),
			array(
				'description'                 => __( 'Display a language switcher for your visitors.', 'vw-translate' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Output the widget content on the frontend.
	 *
	 * @since 1.0.0
	 * @param array $args     Widget display arguments.
	 * @param array $instance Widget settings.
	 */
	public function widget( $args, $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$style = ! empty( $instance['style'] ) ? $instance['style'] : 'dropdown';

		echo wp_kses_post( $args['before_widget'] );

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in get_language_switcher.
		echo VW_Translate_Frontend::get_language_switcher( $style );

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Output the widget settings form in the admin.
	 *
	 * @since 1.0.0
	 * @param array $instance Current widget settings.
	 * @return void
	 */
	public function form( $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Language', 'vw-translate' );
		$style = ! empty( $instance['style'] ) ? $instance['style'] : 'dropdown';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'vw-translate' ); ?>
			</label>
			<input class="widefat"
				   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				   type="text"
				   value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>">
				<?php esc_html_e( 'Display Style:', 'vw-translate' ); ?>
			</label>
			<select class="widefat"
					id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
				<option value="dropdown" <?php selected( $style, 'dropdown' ); ?>>
					<?php esc_html_e( 'Dropdown', 'vw-translate' ); ?>
				</option>
				<option value="list" <?php selected( $style, 'list' ); ?>>
					<?php esc_html_e( 'List', 'vw-translate' ); ?>
				</option>
				<option value="flags" <?php selected( $style, 'flags' ); ?>>
					<?php esc_html_e( 'Flags Only', 'vw-translate' ); ?>
				</option>
			</select>
		</p>
		<?php
	}

	/**
	 * Save widget settings.
	 *
	 * @since 1.0.0
	 * @param array $new_instance New settings.
	 * @param array $old_instance Previous settings.
	 * @return array Sanitized settings.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance          = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['style'] = ! empty( $new_instance['style'] ) ? sanitize_text_field( $new_instance['style'] ) : 'dropdown';

		// Validate style value.
		$allowed_styles = array( 'dropdown', 'list', 'flags' );
		if ( ! in_array( $instance['style'], $allowed_styles, true ) ) {
			$instance['style'] = 'dropdown';
		}

		return $instance;
	}
}
