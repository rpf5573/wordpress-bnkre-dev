<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class ML_Role {
  public static $approved_user = 'approved_user';
  public static $partner = 'partner';
  public static $vip = 'vip';
  public static $staff = 'staff';
  public static $instance;
  public static function getInstance() {
    // Check is $_instance has been set
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    // Returns the instance
    return self::$instance;
  }
  public function __construct() {
    add_action( 'load-themes.php', array($this, 'add_user_roles'), 100, 0 );
  }
  public function add_user_roles() {
    $administrator = get_role('administrator');
    $administrator->add_cap('box_visible_level_1');
    $administrator->add_cap('box_visible_level_2');
    $administrator->add_cap('box_visible_level_3');
    $administrator->add_cap('can_add_listing');

    $subscriber = get_role('subscriber');

    // 직원, 파트너, vip, 일반등업회원
    add_role( self::$staff, __( '직원' ), array_merge($subscriber->capabilities, array(
      'box_visible_level_1' => true,
      'box_visible_level_2' => true,
      'can_add_listing' => true,
      'edit_others_posts' => true
    )));

    add_role( self::$partner, __( '파트너' ), array_merge($subscriber->capabilities, array(
      'box_visible_level_1' => true,
      'can_add_listing' => true
    )));

    add_role( self::$vip, __( 'VIP' ), array_merge($subscriber->capabilities, array(
      'box_visible_level_1' => true,
      'can_add_listing' => true
    )));

    // 일반 등업 회원은 add listing 못한다잉~
    add_role( self::$approved_user, __( '일반등업회원' ), array_merge($subscriber->capabilities, array(
      'box_visible_level_1' => true
    )));
  }
  public function is_staff( $user_id ) {
    $data = get_userdata( $user_id );
    $user_roles = $data->roles;
    if ( in_array(self::$staff, $user_roles) ) {
      return true;
    }
    return false;
  }
}