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

    $glossary_options = get_option( 'bluet_glossary_options' );

    if ( !empty( $glossary_options['kttg_glossary_text']['kttg_glossary_text_all'] ) and $glossary_options['kttg_glossary_text']['kttg_glossary_text_all'] != "" ) {
        $text_all = $glossary_options['kttg_glossary_text']['kttg_glossary_text_all'];
    } else {
        $text_all = __('ALL','tooltipy-lang');
    }

    $tax_query = array();
    if ( $atts['cat'] !== '' ) {
        $terms = array_map( 'trim', explode( ',', $atts['cat'] ) );
        $tax_query = [
            [
                'taxonomy' => 'tooltipy_category',
                'field'    => 'slug',
                'terms'    => $terms,
            ],
        ];
    }

    $current_letter_class = '';
    if ( empty ( $_GET['letter'] ) ) {
        $current_letter_class = 'bluet_glossary_current_letter';
    }

    $all_link = get_permalink();

    ob_start();
    echo '<div class="kttg_glossary_div">';

    echo '<div class="kttg_glossary_header"><span class="bluet_glossary_all ' . $current_letter_class . '"><a href=\'' . $all_link . '\'>' . $text_all . '</a></span> - ';

    // Get the letters
    $chars_count = array();
    $args = [
        'post_type'      => 'my_keywords',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => $tax_query,
    ];
    $q = new WP_Query( $args );
    while ( $q->have_posts() ) {
        $q->the_post();
        $my_char = strtoupper( mb_substr( get_the_title(), 0, 1, 'utf-8' ) );
        if ( empty( $chars_count[$my_char] ) ) {
            $chars_count[$my_char] = 0;
        }
        $chars_count[$my_char]++;
    }
    wp_reset_postdata();

    foreach ( $chars_count as $my_char => $nb ) {
        $current_class = '';
        $glossary_page_url = get_permalink();
        $link_to_the_letter_page = add_query_arg( 'letter', $my_char, $glossary_page_url );
        if ( !empty( $_GET['letter'] ) ) {
            $current_letter_class = 'bluet_glossary_current_letter';
        }
        echo ' <span class="bluet_glossary_letter bluet_glossary_found_letter ' . $current_class . '"><a href=\'' . $link_to_the_letter_page . '\'>' . $my_char . '<span class="bluet_glossary_letter_count">' . $nb . '</span></a></span>';
    }
    echo '</div>';

    $args = [
        'post_type'      => 'my_keywords',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
        'tax_query'      => $tax_query,
    ];


    $q = new WP_Query( $args );

    if ( ! $q->have_posts() ) {
        return '<p>Aucun terme trouvé.</p>';
    }

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
            echo '&nbsp;<small>–&nbsp;<a href="' . esc_url( $edit_link ) . '">modifier</a></small>';
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

