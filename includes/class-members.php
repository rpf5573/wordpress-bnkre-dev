<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class ML_Members {
  public static $levelup_endpoint = 'levelup';
  private static $instance;
  public static $WAITING_CODE = 0;
  public static $ALLOW_CODE = 1;
  public static $REJECT_CODE = 2;
  public static $TABLE_NAME = 'wp_levelup_requests';
  public static $NONCE_KEY = 'levelup_register_allow';
  public static $SMS_AUTH_NONCE = 'sms_auth_nonce';
  public static $SMS_AUTH_REST_API_KEY = '3389788097877985';
  public static $SMS_AUTH_REST_API_SECRET = 'ztrbbJqt3jtM9hM93AVLwMju895Y4tdDXFP8ofCsz5eJm1SqCOb8AvDJEIIIcWm9Bbi0rUs10GKmIDNk';
  public static $IMPORT_API_URL = 'https://api.iamport.kr';
  public $redirect_url = '';
  public function __construct() {
    add_action( 'init', array($this, 'remove_login_default_fields'), 100 );
    if ( $this->validate_settings_for_levelup() ) {
      $this->settings_for_levelup();
    }
    add_filter( 'woocommerce_account_menu_items', [$this, 'remove_account_tabs'], 1000, 2 );

    add_action( 'admin_post_nopriv_process_levelup_form', [$this, 'process_levelup_form'] );
    add_action( 'admin_post_process_levelup_form', [$this, 'process_levelup_form'] );

    add_action( 'admin_menu', [$this, 'add_user_levelup_request_history_menu'], 1000 );

    add_action( 'wp_enqueue_scripts', [$this, 'enqueue_scripts'], 1000 );

    add_action( 'admin_post_process_sms_auth', [$this, 'process_sms_auth'], 1000 );
    
    add_filter( 'login_url', [$this, 'login_url'], 1000, 3 );

    add_action( 'woocommerce_edit_account_form', [$this, 'user_level'], 1000 );
  }
  public static function getInstance() {
    // Check is $_instance has been set
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    // Returns the instance
    return self::$instance;
  }
  public function send_json($result, $message) {
    if ( ! $this->redirect_url ) { $this->redirect_url = home_url(); }
    
    wp_send_json([
      'success' => $result,
      'redirect_url' => "{$this->redirect_url}/?isSuccess={$result}&message={$message}"
    ]);
  }
  public function validate_settings_for_levelup() {
    // 직원이 아닐 경우에만 등업신청을 보여준다
    $ml_role = ML_Role::getInstance();
    $user_id = get_current_user_id();
    if ( $user_id > 0 && ! $ml_role->is_staff($user_id) ) {
      return true;
    }
    return false;
  }
  public function settings_for_levelup() {
    add_filter( 'woocommerce_account_menu_items', [$this, 'add_levelup_menu_item'], 1000, 2 );
    add_action( 'init', [$this, 'add_levelup_endpoint'], 1000, 0 );
    add_action( 'woocommerce_account_'.self::$levelup_endpoint.'_endpoint', [$this, 'levelup_endpoint_content'], 1000 );
    add_filter( 'query_vars', [$this, 'add_levelup_query_vars'], 0 );
  }

  public function remove_login_default_fields() {
    // $login_form = tml_get_form( 'login' );
    tml_remove_form_field('login', 'log');
    tml_remove_form_field('login', 'pwd');
    tml_remove_form_field('login', 'rememberme');
    tml_remove_form_field('login', 'submit');
    tml_remove_form_field('login', 'login_form');
  }
  public function remove_account_tabs($items, $endpoints) {
    $items = array_diff_key($items, ['downloads' => "xy", 'edit-address' => "xy", 'orders' => 'xy']);
    return $items;
  }
  public function add_levelup_endpoint() {
    add_rewrite_endpoint( self::$levelup_endpoint, EP_ROOT | EP_PAGES );
  }
  public function add_levelup_menu_item($items) {
    $items[self::$levelup_endpoint] = '등업신청';
    return $items;
  }
  public function levelup_endpoint_content() {
    $levelup_endpoint = self::$levelup_endpoint;
    $dir = get_stylesheet_directory() . '/woocommerce/myaccount/levelup-page.php';
    require_once $dir;
  }
  public function add_levelup_query_vars($vars) {
    $vars[] = self::$levelup_endpoint;
    return $vars;
  }

  // 유저페이지에서 등업할때
  public function process_levelup_form() {
    // 아직까지는 일반등업과 Partner등업의 폼 차이가 없기 때문에 같게 대응해도 된다

    // redirect url 체크
    if ( ! isset($_POST['redirect_url']) ) { $this->send_json(false, 'redirect_url이 정의되지 않았습니다'); }
    $this->redirect_url = $_POST['redirect_url'];

    // nonce 체크 , levelup_request_nonce뒤에 _check를 붙인 이유는 보안을 더 강화하기 위해서!
    if ( !isset($_POST['levelup_request_nonce']) || !wp_verify_nonce( $_POST['levelup_request_nonce'], 'levelup_request_nonce_check' ) ) {
      $this->send_json(false, '잘못된 접근입니다');
    }

    // user id 체크
    $user_id = get_current_user_id();
    if ( ! $user_id ) { $this->send_json(false, '로그인 상태가 아닙니다'); }

    if ( ! isset($_POST['new-role']) ) {
      wp_send_json( ['success' => false, 'redirect_url' => $redirect_url.'/?isSuccess=false&message=등업유형을 정하지 않으셨습니다'] );
    } else {
      $type = $_POST['new-role'];
      if ( ! in_array($type, [ML_Role::$approved_user, ML_Role::$partner, ML_Role::$vip] ) ) {
        wp_send_json( ['success' => false, 'redirect_url' => $redirect_url.'/?isSuccess=false&message=등업유형이 올바르지 않습니다'] );
      }
    }

    // 성명 체크
    if ( ! isset($_POST['user-name']) ) { 
      $this->send_json(false, '성함이 입력되지 않았습니다');
    }

    // 가입목적 체크
    if ( ! isset($_POST['register-purpose']) ) { $this->send_json(false, '가입목적이 정의되지 않았습니다'); }

    // 본인인증 체크
    if ( ! isset($_POST['sms_auth_imp_uid']) ) { $this->send_json(false, '본인인증이 필요합니다'); }
    $token = $this->get_import_token();
    if ( ! $token ) { $this->send_json(false, "일시적인 문제로 본인인증서버에 문제가 생겼습니다. 토큰을 얻을 수 없습니다. 다날혹은 아임포트에 문의해주시기 바랍니다"); }
    $response = $this->get_sms_auth_user_data($_POST['sms_auth_imp_uid'], $token);
    if ( ! ($response && $response->certified) ) { $this->send_json(false, "본인인증이 필요합니다"); }

    // 위의 체크를 다 통과했으면, 이제 DB에 insert하자
    $result = $this->insert_levelup_request($user_id, $_POST['user-name'], $_POST['register-purpose'], $_POST['new-role']);

    if ( $result ) {
      $this->send_json(true, '등업신청완료');
    } else {
      $this->send_json(false, 'DB에러로 등업신청이 안되었습니다. 관리자에게 문의해주시기 바랍니다.');
    }
  }
  public function insert_levelup_request( $user_id, $user_name, $purpose, $new_role) {
    global $wpdb;
    $info = $this->get_levelup_request_info($user_id);
    if ( is_null( $info ) ) {
      $result = $wpdb->insert(ML_Members::$TABLE_NAME, array(
        'user_id'       => $user_id,
        'name'          => $user_name,
        'purpose'       => $purpose,
        'date'          => current_time( 'mysql' ),
        'new_role'      => $new_role,
      ));
    } else {
      $result = $wpdb->update(ML_Members::$TABLE_NAME, array(
        'name'          => $user_name,
        'purpose'       => $purpose,
        'date'          => current_time( 'mysql' ),
        'new_role'      => $new_role,
        'status'        => 0 // 업데이트 했으니까 다시 승인을 받아야겠지?
      ), array(
        'user_id' => $user_id
      ));
    }
    return $result;
  }
  public function get_levelup_request_info( $user_id ) {
    global $wpdb;
    return $wpdb->get_row(sprintf("SELECT * FROM `%s` WHERE `user_id` = %d", ML_Members::$TABLE_NAME, $user_id));
  }
  public function allow_levelup_request($user_id, $new_role) {
    global $wpdb;
    $result = $wpdb->update(ML_Members::$TABLE_NAME, array(
      'status' => ML_Members::$ALLOW_CODE,
    ),
    array(
      'user_id' => $user_id
    ));
    if ( $result ) {
      $this->user_levelup($user_id, $new_role);
    }
  }
  public function reject_levelup_request($user_id) {
    global $wpdb;
    $result = $wpdb->update(ML_Members::$TABLE_NAME, array(
      'status' => ML_Members::$REJECT_CODE,
    ),
    array(
      'user_id' => $user_id
    ));
    
  }
  public function add_user_levelup_request_history_menu() {
    add_submenu_page(
			'users.php',                              // Parent slug.
			'등업 신청 리스트',                           // Page title.
			'등업 신청 리스트',                           // Menu title.
			'manage_options',                         // Capability. => 이 페이지를 볼 수 있는 조건
      'levelup_request_list',             // Menu slug.
      [$this, 'levelup_request_list_page_callback']
		);
  }
  public function levelup_request_list_page_callback() {
    $this->process_leveup_request(); ?>

    <div class="wrap">
      <h2>WP_List_Table Class Example</h2>
      <div id="poststuff">
        <div id="post-body" class="metabox-holder">
          <div id="post-body-content">
            <div class="meta-box-sortables ui-sortable">
              <form method="POST" action=""> <?php
                $list_table = new Levelup_Request_List_Table();
                $list_table->prepare_items();
                $list_table->display(); ?>
              </form>
            </div>
          </div>
        </div>
        <br class="clear">
      </div>
    </div>
    <?php
  }
  public function get_import_token() {
    $data = array(
      'imp_key' => self::$SMS_AUTH_REST_API_KEY,
      'imp_secret' => self::$SMS_AUTH_REST_API_SECRET
    );
    $url = self::$IMPORT_API_URL . '/users/getToken';
    $ch = curl_init();                                 //curl 초기화
    curl_setopt($ch, CURLOPT_URL, $url);               //URL 지정하기
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    //요청 결과를 문자열로 반환
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);      //connection timeout 10초
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   //원격 서버의 인증서가 유효한지 검사 안함
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));       //POST data
    curl_setopt($ch, CURLOPT_POST, true);              //true시 post 전송
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response);
    if ( $response->code == 0 ) {
      return $response->response->access_token;
    } else {
      return false;
    }
    return false;
  }
  public function get_sms_auth_user_data($imp_uid, $token) {
    $url = self::$IMPORT_API_URL . "/certifications/" . $imp_uid;    
    $ch = curl_init();                                 //curl 초기화
    curl_setopt($ch, CURLOPT_URL, $url);               //URL 지정하기
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    //요청 결과를 문자열로 반환 
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);      //connection timeout 10초 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   //원격 서버의 인증서가 유효한지 검사 안함
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Authorization:' . $token ));
    
    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response);
    if ( $response->code == 0 ) {
      return $response->response;
    } else {
      return false;
    }
    return false;
  }

  // 관리자페이지에서 등업 승인/거절 할때
  public function process_leveup_request() {
    $nonce_result = wp_verify_nonce( $_GET['_wpnonce'], ML_Members::$NONCE_KEY );
    
    if ( isset($_GET['_wpnonce']) && wp_verify_nonce( $_GET['_wpnonce'], ML_Members::$NONCE_KEY ) ) {
      
      if ( isset($_GET['ml_user_id']) && isset($_GET['ml_allow']) && isset($_GET['new_role']) ) {
        
        $uid = $_GET['ml_user_id'];
        if ( $_GET['ml_allow'] == ML_Members::$ALLOW_CODE ) {
          $this->allow_levelup_request( $uid, $_GET['new_role'] );
        }
        else if ( $_GET['ml_allow'] == ML_Members::$REJECT_CODE ) {
          $this->reject_levelup_request( $uid );
        }
      }
    }
  }
  public function user_levelup($user_id, $new_role) {
    if ( ! $user_id || ! $new_role ) { return; }
    
    $result = wp_update_user(array('ID'=>$user_id, 'role'=>$new_role));
  }
  public function enqueue_scripts() {
    if ( $this->is_level_page() ) {
      wp_enqueue_script( 'import_library', 'https://cdn.iamport.kr/js/iamport.payment-1.1.5.js', array('jquery'), '1.1.5', false );
      wp_enqueue_script( 'sms', get_stylesheet_directory_uri() . '/sms.js', array('import_library'), '0.0.1', true );
      wp_enqueue_script( 'loading-overlay', get_stylesheet_directory_uri() . '/LoadingOverlay.js', array('jquery'), '0.0.1', false );
    }
  }
  public function is_level_page() {
    global $wp_query;
    $is_endpoint = isset( $wp_query->query_vars[ self::$levelup_endpoint ] );
    if ( $is_endpoint && ! is_admin() && is_account_page() ) {
      return true;
    }
    return false;
  }
  public function process_sms_auth() {
    if ( ! wp_verify_nonce( $_POST['nonce'], ML_Members::$SMS_AUTH_NONCE )) { exit("failed verity nonce"); }
    if ( ! isset( $_POST['imp_uid'] ) ) { exit("no imp_uid"); }
    $imp_uid = $_POST['imp_uid'];
    $url = 'https://api.iamport.kr/users/getToken';
    $response = wp_remote_post($url, array(
      'method'        => 'POST',
      'timeout'       => 45,
      'redirection'   => 5,
      'headers'     => array(
        'Content-Type' => 'application/json'
      ),
      'body'        => array(
        'imp_key' => ML_Members::$SMS_AUTH_REST_API_KEY,
        'imp_secret' => ML_Members::$SMS_AUTH_REST_API_SECRET
      ),
    ));
  }
  public function login_url($login_url, $redirect, $force_reauth) {
    $login_url = home_url('wp-login.php');
    return $login_url;
  }

  // edit form에 사용자의 등급을 표시해주자
  public function user_level() {
    $user_id = get_current_user_id();
    if ( ! $user_id ) { return; }
    $user_meta = get_userdata( $user_id );
    $user_roles = $user_meta->roles;
    $user_role = '일반회원';
    $levelup_link = wc_get_account_endpoint_url( self::$levelup_endpoint );
    $anchor = '<a href="' . $levelup_link . '">여기</a>';
    if ( in_array( ML_Role::$approved_user, $user_roles ) ) {
      $user_role = '일반등업회원';
    }
    else if ( in_array( ML_Role::$partner, $user_roles ) ) {
      $user_role = '파트너';
    }
    else if ( in_array( ML_Role::$staff, $user_roles ) ) {
      $user_role = '직원';
    }
    else if ( in_array( ML_Role::$vip, $user_roles ) ) {
      $user_role = 'VIP';
    }
    else {
      $user_role = '일반회원';
    }
    ?>
      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label>회원등급&nbsp;<?php if ( $user_role != '직원' ) { echo "(등업신청은 {$anchor}서 가능합니다)"; } ?></label>
        <input type="text" disabled class="woocommerce-Input woocommerce-Input--email input-text" value="<?php echo $user_role; ?>">
      </p>
    <?php
  }
}

class Levelup_Request_List_Table extends WP_List_Table {
  private $current_url;
	/** Class constructor */
	public function __construct() {
    add_filter( 'removable_query_args', [$this, 'add_removeable_query_args_for_table_pagination'], 1000, 1 );

    // $this->current_url = esc_attr( wp_get_referer() );
		parent::__construct( [
			'singular' => '등업신청', //singular name of the listed records
			'plural'   => '등업신청 리스트', //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		] );
  }

  public function get_list( $per_page = 5, $page_number = 1 ) {
    global $wpdb;
    $sql = sprintf("SELECT `user_id`, `name`, `purpose`, `new_role`, `date` FROM `%s` WHERE `status` = %d", ML_Members::$TABLE_NAME, ML_Members::$WAITING_CODE);
    $sql .= " LIMIT {$per_page}";
    $sql .= " OFFSET " . ( $page_number - 1 ) * $per_page;
    
    $result = $wpdb->get_results( $sql, 'ARRAY_A' );
    return $result;
  }

  public function get_counts() {
    global $wpdb;
    $sql = sprintf("SELECT COUNT(*) FROM `%s` WHERE `status` = %d", ML_Members::$TABLE_NAME, ML_Members::$WAITING_CODE);
    return $wpdb->get_var( $sql );
  }

  public function no_items() {
    echo "등업신청이 없습니다";
  }

  public function column_default( $item, $column_name ) {
    switch ( $column_name ) {
      case 'user_id':
        return $item['user_id'];
      case 'name':
        return $item['name'];
      case 'purpose':
        return $item['purpose'];
      case 'new_role':
        $new_role = '없음';
        if ( $item['new_role'] == 'partner' ) {
          $new_role = '파트너';
        } 
        else if ( $item['new_role'] == 'approved_user' ) {
          $new_role = '일반등업회원';
        }
        else if ( $item['new_role'] == 'vip' ) {
          $new_role = 'VIP';
        }
        return $new_role;
      case 'date':
        return $item['date'];
      case 'process':
        if ( ! isset($item['user_id']) ) { return 'ERROR : user id가 없습니다'; }
        $wpnonce = wp_create_nonce( ML_Members::$NONCE_KEY );
        $query_args = array(
          'page'      => 'levelup_request_list',
          'paged'     => (isset($_GET['paged']) ? $_GET['paged'] : 1 ),
          'ml_allow'     => ML_Members::$ALLOW_CODE,
          'ml_user_id'   => $item['user_id'],
          'new_role'    => $item['new_role'],
          '_wpnonce'  => $wpnonce
        );
        $ok = add_query_arg($query_args, 'users.php');

        $query_args['ml_allow'] = ML_Members::$REJECT_CODE;
        $no = add_query_arg($query_args, 'users.php');
        
        return "<a href='{$ok}' class='btn-allow'>승인</a> <a href='{$no}' class='btn-reject'>거절</a>";
      default:
        return print_r( $item, true ); //Show the whole array for troubleshooting purposes
    }
  }

  public function get_columns() {
    $columns = [
      'cb'        => '<input type="checkbox" />',
      'user_id'   => '유저 아이디',
      'name'      => '성명',
      'purpose'   => '가입목적',
      'new_role'  => '등업유형',
      'date'      => '신청날짜',
      'process'   => '처리(승인/거절)'
    ];
  
    return $columns;
  }
  
  public function prepare_items() {
    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);

    $per_page     = 2;
    $current_page = $this->get_pagenum();
    $total_items  = $this->get_counts();

    $this->set_pagination_args( [
      'total_items' => $total_items, //WE have to calculate the total number of items
      'per_page'    => $per_page //WE have to determine how many items to show on a page
    ] );

    $this->items = $this->get_list( $per_page, $current_page );
  }

  public function add_removeable_query_args_for_table_pagination( $args ) {
    $args[] = 'ml_user_id';
    $args[] = 'ml_allow';
    $args[] = '_wpnonce';
    $args[] = 'new_role';
    return $args;
  }
}