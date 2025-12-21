<?php
function cptt_avada_render_post_metadata($layout, $settings = array())
{
    $html     = '';
    $author   = '';
    $date     = '';
    $metadata = '';

    $settings = ( is_array( $settings ) ) ? $settings : [];

    if ( is_search() ) {
        $search_meta = array_flip( fusion_library()->get_option( 'search_meta' ) );

        $default_settings = [
            'post_meta'          => empty( $search_meta ) ? false : true,
            'post_meta_author'   => isset( $search_meta['author'] ),
            'post_meta_date'     => isset( $search_meta['date'] ),
            'post_meta_cats'     => isset( $search_meta['categories'] ),
            'post_meta_tags'     => isset( $search_meta['tags'] ),
            'post_meta_comments' => isset( $search_meta['comments'] ),
            'post_meta_type'     => isset( $search_meta['post_type'] ),
            'meta_terms1' =>        isset( $search_meta['meta_terms1'] ),
            'meta_terms2' =>        isset( $search_meta['meta_terms2'] ),
            'post_cpt_cfield1' =>   isset( $search_meta['post_cpt_cfield1'] ),
            'post_key_text1' =>     isset( $search_meta['post_key_text1'] ),
            'post_cpt_cfield2' =>   isset( $search_meta['post_cpt_cfield2'] ),
            'post_key_text2' =>     isset( $search_meta['post_key_text2'] ),
        ];
    } else {
        $default_settings = [
            'post_meta' => fusion_library()->get_option('post_meta'),
            'post_meta_author' => fusion_library()->get_option('post_meta_author'),
            'post_meta_date' => fusion_library()->get_option('post_meta_date'),
            'post_meta_cats' => fusion_library()->get_option('post_meta_cats'),
            'post_meta_tags' => fusion_library()->get_option('post_meta_tags'),
            'post_meta_comments' => fusion_library()->get_option('post_meta_comments'),
            'meta_terms1' => fusion_library()->get_option('meta_terms1'),
            'meta_terms2' => fusion_library()->get_option('meta_terms2'),
            'post_cpt_cfield1' => fusion_library()->get_option('post_cpt_cfield1'),
            'post_key_text1' => fusion_library()->get_option('post_key_text1'),
            'post_cpt_cfield2' => fusion_library()->get_option('post_cpt_cfield2'),
            'post_key_text2' => fusion_library()->get_option('post_key_text2'),
        ];
    }

    $settings = wp_parse_args($settings, $default_settings);
    $post_meta = get_post_meta(get_queried_object_id(), 'pyre_post_meta', true);

    // Check if meta data is enabled.
    if (($settings['post_meta'] && 'no' !== $post_meta) || (!$settings['post_meta'] && 'yes' === $post_meta)) {

        // For alternate, grid and timeline layouts return empty single-line-meta if all meta data for that position is disabled.
        if (in_array($layout, array('alternate', 'grid_timeline'), true) && !$settings['post_meta_author'] && !$settings['post_meta_date'] && !$settings['meta_terms1'] && !$settings['meta_terms2'] && !$settings['post_meta_cats'] && !$settings['post_meta_tags'] && !$settings['post_meta_comments']) {
            return '';
        }

        // Render author meta data.
        if ($settings['post_meta_author']) {
            ob_start();
            the_author_posts_link();
            $author_post_link = ob_get_clean();

            // Check if rich snippets are enabled.
            if ( fusion_library()->get_option( 'disable_date_rich_snippet_pages' ) && fusion_library()->get_option( 'disable_rich_snippet_author' ) ) {
                /* translators: The author. */
                $metadata .= sprintf( esc_html__( 'By %s', 'fusion-builder' ), '<span class="vcard"><span class="fn">' . $author_post_link . '</span></span>' );
            } else {
                /* translators: The author. */
                $metadata .= sprintf( esc_html__( 'By %s', 'fusion-builder' ), '<span>' . $author_post_link . '</span>' );
            }
            $metadata .= '<span class="fusion-inline-sep">|</span>';
        } else { // If author meta data won't be visible, render just the invisible author rich snippet.
            $author .= fusion_render_rich_snippets_for_pages( false, true, false );
        }

        // Render the updated meta data or at least the rich snippet if enabled.
        if ( $settings['post_meta_date'] ) {
            $metadata .= fusion_render_rich_snippets_for_pages( false, false, true );

            $formatted_date = get_the_date( fusion_library()->get_option( 'date_format' ) );
            $date_markup    = '<span>' . $formatted_date . '</span><span class="fusion-inline-sep">|</span>';

            $metadata      .= apply_filters( 'fusion_post_metadata_date', $date_markup, $formatted_date );
        } else {
            $date .= fusion_render_rich_snippets_for_pages( false, false, true );
        }

        // Render rest of meta data.
        // Render categories.
        if ($settings['post_meta_cats']) {
            $post_type  = get_post_type();
            $taxonomies = [
                'avada_portfolio' => 'portfolio_category',
                'avada_faq'       => 'faq_category',
                'product'         => 'product_cat',
                'tribe_events'    => 'tribe_events_cat',
            ];
            ob_start();
            if ( 'post' === $post_type ) {
                the_category( ', ' );
            } elseif ( 'page' !== $post_type && isset( $taxonomies[ $post_type ] ) ) {
                the_terms( get_the_ID(), $taxonomies[ $post_type ], '', ', ' );
            }
            $categories = ob_get_clean();

            if ($categories) {
                $metadata .= ($settings['post_meta_tags']) ? sprintf(esc_html__('Categories: %s', 'fusion-builder'), $categories) : $categories;
                $metadata .= '<span class="fusion-inline-sep">|</span>';
            }
        }

        // Render tags.
        if ($settings['post_meta_tags']) {
            ob_start();
            the_tags('');
            $tags = ob_get_clean();

            if ($tags) {
                $metadata .= '<span class="meta-tags">' . sprintf(esc_html__('Tags: %s', 'fusion-builder'), $tags) . '</span><span class="fusion-inline-sep">|</span>';
            }
        }

        //render terms for custom taxonomies

        global $post;
        $taxonomy1_name = get_taxonomy($settings['meta_terms1']);
        $taxonomy2_name = get_taxonomy($settings['meta_terms2']);

        if ($taxonomy1_name) {
            ob_start();
            the_terms($post->ID, $settings['meta_terms1'],'<span class="cptt-taxonomy-label">' . $taxonomy1_name->labels->name . ':</span> ', ' , ');
            $cus_term1 = ob_get_clean();

            if ($cus_term1) {

                $metadata .= sprintf('%s<span class="fusion-inline-sep">|</span>', $cus_term1);
            }
        }

        if ($taxonomy2_name) {
            ob_start();
            the_terms($post->ID, $settings['meta_terms2'], '<span class="cptt-taxonomy-label">' . $taxonomy2_name->labels->name . ':</span> ', ' , ');
            $cus_term2 = ob_get_clean();

            if ($cus_term2) {
                $metadata .= sprintf('<span class="meta-tags">%s %s</span><span class="fusion-inline-sep">|</span>', '', $cus_term2);
            }
        }

        // Custom Fields
        if ($settings['post_cpt_cfield1']) {
            $cfield1_value = get_post_meta($post->ID, $settings['post_cpt_cfield1'], true);
            if (is_array ($cfield1_value)) {$cfield1_value = implode (',', $cfield1_value); }
            $cfield1_text = $settings['post_key_text1'];

            if(function_exists('get_field')){
                $cfield1_value = get_field($settings['post_cpt_cfield1'], $post->ID );
            }


            $cfield1 = $cfield1_value;
            if ($cfield1_text) {
                $cfield1 = $cfield1_text . ": " . $cfield1_value;
            }

            if ($cfield1_value) {
                $metadata .= sprintf('%s<span class="fusion-inline-sep">|</span>', $cfield1);
            }
        }

        if ($settings['post_cpt_cfield2']) {
            $cfield2_value = get_post_meta($post->ID, $settings['post_cpt_cfield2'], true);
            if (is_array ($cfield2_value)) {$cfield2_value = implode (',', $cfield2_value); }
            $cfield2_text = $settings['post_key_text2'];

            if(function_exists('get_field')){
                $cfield2_value = get_field($settings['post_cpt_cfield2'], $post->ID );
            }


            $cfield2 = $cfield2_value;

            if ($cfield2_text) {
                $cfield2 = $cfield2_text . ": " . $cfield2_value;
            }

            if ($cfield2_value) {
                $metadata .= sprintf('<span class="meta-tags">%s %s</span><span class="fusion-inline-sep">|</span>', '', $cfield2);
            }
        }

        // Render comments.
        if ($settings['post_meta_comments'] && 'grid_timeline' !== $layout) {
            ob_start();
            comments_popup_link(esc_html__('0 Comments', 'fusion-builder'), esc_html__('1 Comment', 'fusion-builder'), esc_html__('% Comments', 'fusion-builder'));
            $comments = ob_get_clean();
            $metadata .= '<span class="fusion-comments">' . $comments . '</span>';
        }

        // Render the HTML wrappers for the different layouts.
        if ($metadata) {
            $metadata = $author . $date . $metadata;

            if ('single' === $layout) {
                $html .= '<div class="fusion-meta-info"><div class="fusion-meta-info-wrapper">' . $metadata . '</div></div>';
            } elseif (in_array($layout, array('alternate', 'grid_timeline'), true)) {
                $html .= '<p class="fusion-single-line-meta">' . $metadata . '</p>';
            } elseif ('recent_posts' === $layout) {
                $html .= $metadata;
            } else {
                $html .= '<div class="fusion-alignleft">' . $metadata . '</div>';
            }
        } else {
            $html .= $author . $date;
        }
    } else {
        // Render author and updated rich snippets for grid and timeline layouts.
        if ($fusion_settings->get('disable_date_rich_snippet_pages')) {
            $html .= fusion_builder_render_rich_snippets_for_pages(false);
        }
    }

    return apply_filters('fusion_post_metadata_markup', $html);
}
