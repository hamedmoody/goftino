<?php

/**
 * Add these filter for set user mobile number
 */
add_filter( 'goftino_user_phone', function(){

    /**
     * return your user phone base on your website structure
     */
    return get_user_meta( get_current_user_id(), 'phone_number', true );

} );

/**
 * Add these filter for set user description
 */
add_filter( 'goftino_user_about', function(){

    /**
     * return your user description base on your website structure
     * Note: this use default user description
     */
    return wp_get_current_user()->user_description;

} );

/**
 * Add these filter for set user description
 */
add_filter( 'goftino_user_avatar', function(){

    /**
     * return your user custom avatar base on your website structure
     */
    return get_user_meta( get_current_user_id(), 'avatar_image', true );

} );