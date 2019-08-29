<?php
/*
Plugin Name: Simple Advanced Custom Logo
Plugin URI: https://github.com/Niq1982/Simple-Advanced-Custom-Logo-for-WordPress
Description: Add more logos to your site.
Author: Niku Hietanen
Version: 1.0.0
Author URI: http://niq.kapsi.fi
*/

/**
 * Build customizer settings
 */
function sacl_customizer_setting( $wp_customize ) {
	$wp_customize->add_setting(
		'sacl_amount_setting',
		[
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sacl_sanitize_number_absint',
			'default'           => 1,
		]
	);
	$wp_customize->add_control(
		'sacl_amount_setting',
		[
			'type'        => 'number',
			'section'     => 'title_tagline',
			'priority'    => 8,
			'label'       => __( 'Amount of logos you need' ),
			'description' => __( '(refresh page to apply)' ),
		]
	);
	$logo_amount = get_theme_mod( 'sacl_amount_setting' );
	for ( $i = 1; $i <= $logo_amount; $i++ ) {
		$wp_customize->add_setting( 'sacl_custom_logo_' . $i );
		$custom_logo_args = get_theme_support( 'custom-logo' );
		$wp_customize->add_control(
			new WP_Customize_Cropped_Image_Control(
				$wp_customize,
				'sacl_custom_logo_' . $i,
				array(
					'label'    => 'Upload Custom logo ' . $i,
					'description' => 'Usage: <code>sacl_the_custom_logo(' . $i . ');</code>',
					'section'       => 'title_tagline',
					'priority'      => 8,
					'height'        => $custom_logo_args[0]['height'],
					'width'         => $custom_logo_args[0]['width'],
					'flex_height'   => $custom_logo_args[0]['flex-height'],
					'flex_width'    => $custom_logo_args[0]['flex-width'],
					'button_labels' => array(
						'select'       => __( 'Select logo' ),
						'change'       => __( 'Change logo' ),
						'remove'       => __( 'Remove' ),
						'default'      => __( 'Default' ),
						'placeholder'  => __( 'No logo selected' ),
						'frame_title'  => __( 'Select logo' ),
						'frame_button' => __( 'Choose logo' ),
					),
				)
			)
		);
	}
}

add_action( 'customize_register', 'sacl_customizer_setting' );
/**
 * Sanitize number input for logo amount
 */
function sacl_sanitize_number_absint( $number, $setting ) {
	// Ensure $number is an absolute integer (whole number, zero or greater).
	$number = absint( $number );

	// If the input is an absolute integer, return it; otherwise, return the default.
	return ( $number ? $number : $setting->default );
}
/**
 * Template function to get the custom logo
 */
function sacl_get_custom_logo( $logo_id = 1, $blog_id = 0 ) {
	$html          = '';
	$switched_blog = false;

	if ( is_multisite() && ! empty( $blog_id ) && (int) $blog_id !== get_current_blog_id() ) {
		switch_to_blog( $blog_id );
		$switched_blog = true;
	}
	$custom_logo_id = get_theme_mod( 'sacl_custom_logo_' . $logo_id );

	// We have a logo. Logo is go.
	if ( $custom_logo_id ) {
		$custom_logo_attr = array(
			'class' => 'custom-logo',
		);

		/*
			* If the logo alt attribute is empty, get the site title and explicitly
			* pass it to the attributes used by wp_get_attachment_image().
			*/
		$image_alt = get_post_meta( $custom_logo_id, '_wp_attachment_image_alt', true );
		if ( empty( $image_alt ) ) {
			$custom_logo_attr['alt'] = get_bloginfo( 'name', 'display' );
		}

		/*
			* If the alt attribute is not empty, there's no need to explicitly pass
			* it because wp_get_attachment_image() already adds the alt attribute.
			*/
		$html = sprintf(
			'<a href="%1$s" class="custom-logo-link" rel="home">%2$s</a>',
			esc_url( home_url( '/' ) ),
			wp_get_attachment_image( $custom_logo_id, 'full', false, $custom_logo_attr )
		);
	} elseif ( is_customize_preview() ) {
		// If no logo is set but we're in the Customizer, leave a placeholder (needed for the live preview).
		$html = sprintf(
			'<a href="%1$s" class="custom-logo-link" style="display:none;"><img class="custom-logo"/></a>',
			esc_url( home_url( '/' ) )
		);
	}
	return apply_filters( 'get_custom_logo', $html, $blog_id );
}
/**
 * Print the custom logo
 */
function sacl_the_custom_logo( $logo_id = 1, $blog_id = 0 ) {
	echo wp_kses_post( sacl_get_custom_logo( $logo_id, $blog_id ) );
}
