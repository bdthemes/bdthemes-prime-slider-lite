<?php

if ( ! defined( 'BDTPS_CORE_TITLE' ) ) {
    $white_label_title = get_option( 'ps_white_label_title' );
	define( 'BDTPS_CORE_TITLE', $white_label_title );
}

if ( ! defined( 'BDTPS_CORE_LO' ) ) {
    $hide_license = get_option( 'ps_white_label_hide_license', false );
    if ( $hide_license ) {
        define( 'BDTPS_CORE_LO', true );
    }
}

if ( ! defined( 'BDTPS_CORE_HIDE' ) ) {
    $hide_ps = get_option( 'ps_white_label_bdtps_hide', false );
    if ( $hide_ps ) {
        define( 'BDTPS_CORE_HIDE', true );
    }
}