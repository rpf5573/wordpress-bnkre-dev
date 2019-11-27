<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class ML_Init {
  public function __construct() {
    add_action( 'wp_enqueue_scripts', array($this, 'theme_enqueue_styles') );
    add_action( 'after_setup_theme', [$this, 'include_classes_after_parent_theme_loaded'], 1000 );
  }
  public function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_uri() );
    wp_enqueue_script( 'child-js', get_stylesheet_directory_uri() . '/main.js', array('jquery'), '0.001', true );
    if ( is_rtl() ) {
    	wp_enqueue_style( 'mylisting-rtl', get_template_directory_uri() . '/rtl.css', [], wp_get_theme()->get('Version') );
    }
  }
  public function include_classes_after_parent_theme_loaded() {
    require_once wp_normalize_path( get_stylesheet_directory() . '/includes/class-map.php' );
  }
}