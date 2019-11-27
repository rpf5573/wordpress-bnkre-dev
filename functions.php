<?php

require_once wp_normalize_path( get_stylesheet_directory() . '/includes/class-shortcodes.php' );
require_once wp_normalize_path( get_stylesheet_directory() . '/includes/class-init.php' );
require_once wp_normalize_path( get_stylesheet_directory() . '/includes/class-role.php' );
require_once wp_normalize_path( get_stylesheet_directory() . '/includes/class-contact-form.php' );
require_once wp_normalize_path( get_stylesheet_directory() . '/includes/class-members.php' );

new ML_Init();
new ML_Shortcodes();
ML_Role::getInstance();
new ML_Contact_Form();
ML_Members::getInstance(); // initialize