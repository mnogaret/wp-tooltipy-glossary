<?php
/**
 * Plugin Name: WP Custom Tooltipy Glossary
 * Description: Custom shortcode for filtering Tooltipy glossary by family/category.
 * Author: Mathieu Nogaret
 * Version: 0.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_DEBUG', true );

add_action( 'init', function() {
    register_taxonomy(
        'tooltipy_category',
        'my_keywords',
        [
            'label'        => 'Catégories du glossaire',
            'hierarchical' => true,
            'public'       => true,
            'show_ui'      => true,
            'show_in_rest' => true,
        ]
    );
});

function custom_tooltipy_glossary( $atts ) {
    $atts = shortcode_atts(
        [
            'cat'     => '',
            'orderby' => 'title',
            'order'   => 'ASC',
        ],
        $atts,
        'custom_tooltipy_glossary'
    );

    $args = [
        'post_type'      => 'my_keywords',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
    ];

    if ( $atts['cat'] !== '' ) {
        $terms = array_map( 'trim', explode( ',', $atts['cat'] ) );
        $args['tax_query'] = [
            [
                'taxonomy' => 'tooltipy_category',
                'field'    => 'slug',
                'terms'    => $terms,
            ],
        ];
    }

    $q = new WP_Query( $args );

    if ( ! $q->have_posts() ) {
        return '<p>Aucun terme trouvé.</p>';
    }

    ob_start();
    echo '<dl class="wp-custom-tooltipy-glossary">';

    while ( $q->have_posts() ) {
	$q->the_post();
	$post_id = get_the_ID();
        $slug = get_post_field( 'post_name' );

	echo '<dt id="glossary-' . esc_attr( $slug ) . '">';

	echo '<h2 class="glossary_element_title">';

	echo '<span class="no-tooltipy">' . esc_html( get_the_title() ) . '</span>';

	if ( current_user_can( 'edit_post', $post_id ) ) {
		$edit_link = get_edit_post_link( $post_id );
		echo '&nbsp;–&nbsp;<a href="' . esc_url( $edit_link ) . '">modifier</a>';
	}

	echo '</h2>';

	echo '</dt>';

        echo '<dd>' . wp_kses_post( apply_filters( 'the_content', get_the_content() ) ) . '</dd>';
    }

    echo '</dl>';
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'custom_tooltipy_glossary', 'custom_tooltipy_glossary' );

