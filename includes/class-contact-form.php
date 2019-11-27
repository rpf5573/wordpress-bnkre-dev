<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class ML_Contact_Form {
  public function __construct() {
    add_filter( 'wpcf7_special_mail_tags', array($this, 'wpcf7_post_related_smt'), 100, 3 );
  }
  function wpcf7_post_related_smt( $output, $name, $html ) {
    if ( '_post_' != substr( $name, 0, 6 ) ) {
      return $output;
    }

    $submission = WPCF7_Submission::get_instance();

    if ( ! $submission ) {
      return $output;
    }

    $post_id = (int) $submission->get_meta( 'container_post_id' );

    if ( ! $post_id
    or ! $post = get_post( $post_id ) ) {
      return '';
    }

    $address = get_post_meta( $post_id, '_address', true );
    if ( '_post_address' == $name ) {
      return $html ? esc_html( $address ) : $address;
    }

    if ( '_post_title' == $name ) {
      return $html ? esc_html( $post->post_title ) : $post->post_title;
    }

    return $output;
  }
}
