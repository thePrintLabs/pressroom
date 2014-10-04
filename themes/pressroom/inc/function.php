<?php

// remove_filter( 'the_content', 'wpautop' );

add_filter('the_content', 'remove_empty_p', 20, 1);

function remove_empty_p($content){
    $content = force_balance_tags($content);
    return preg_replace('#<p>\s*+(<br\s*/*>)?\s*</p>#i', '', $content);
}

add_filter('the_content', 'filter_images', 30, 1);

function filter_images($content){
    return preg_replace('/<img (.*) \/>\s*/iU', '<figure><img \1 /></figure>', $content);
}
