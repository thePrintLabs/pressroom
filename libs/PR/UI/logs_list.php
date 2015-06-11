<?php
require_once( ABSPATH . 'wp-admin/includes/template.php' );
require_once( ABSPATH . 'wp-includes/pluggable.php' );

if ( !class_exists( 'WP_List_Table' ) ) {
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( !class_exists( 'WP_Screen' ) ) {
   require_once( ABSPATH . 'wp-admin/includes/screen.php' );
}

/**
 * Pressroom_List_Table class.
 *
 * @extends WP_List_Table
 */
class Pressroom_Logs_Table extends WP_List_Table
{
  protected $_per_page;

  public function __construct() {

    if ( !is_admin() ) {
       return;
    }

    parent::__construct( array(
       'singular'  => __( 'log', 'logs' ),
       'plural'    => __( 'logs', 'logs' ),
       'screen'	   => 'pr_logs',
       'ajax'      => true,
    ) );

    add_action( 'wp_ajax_presslist', array( $this, 'presslist_ajax_callback' ) );
    add_action( 'wp_ajax__ajax_fetch_presslist', array( $this, 'ajax_fetch_presslist_callback' ) );
    add_action('init', array( $this, 'init_thickbox' ) );
    add_action( 'admin_footer', array( $this, 'add_custom_styles' ) );

  }


  /**
  *
  * Override default prepare item setting pagination
  *
  * @return void
  */
  public function prepare_items() {

    $sortable = '';
    $hidden = array();
    $pr_logs = new PR_Logs();

    if( isset( $_GET['start_date'],$_GET['end_date'] ) ) {
      $start_date = $_GET['start_date'];
      $end_date = $_GET['end_date'];
    }
    else {
      $start_date = date('Y-m-d H:i:s', strtotime("-90 days"));
      $end_date = date('Y-m-d H:i:s');
    }


    $data = $pr_logs->get_logs( $start_date, $end_date );
    $columns = $this->get_columns();
    //$sortable = $this->get_sortable_columns();

    $this->_column_headers = array( $columns, $hidden, $sortable );

    $per_page = !empty( $_REQUEST['post_per_page'] ) ? $_REQUEST['post_per_page'] : 10;
    $this->_per_page = $per_page;
    $current_page = $this->get_pagenum();
    $total_items = count( $data );
    $data = array_slice( $data, ($current_page - 1) * $per_page , $per_page );
    $this->items = $data;

    $this->set_pagination_args( array(
       'total_items'  => $total_items,
       'per_page'     => $per_page,
       'total_pages'  => ceil( $total_items / $per_page ),
       'orderby'      => !empty( $_REQUEST['orderby'] ) && strlen( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'title',
       'order'        => !empty( $_REQUEST['order'] ) && strlen( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'asc',
    ) );
  }

  /**
   *	Columns configuration array
   *
   * @return array
   */
  public function get_columns() {

    $columns = array(
      'cb'           => '<input type="checkbox" />',
      'object_id'    => __( 'Issue', 'pr_logs' ),
      'log_date'     => __( 'Date', 'pr_logs' ),
      'log_author'   => __( 'Author', 'pr_logs' ),
      'log_ip'       => __( 'IP', 'pr_logs' ),
      'log_action'   => __( 'Action', 'pr_logs' ),
      'log_type'     => __( 'Package type', 'pr_logs' ),
      'log_detail'   => __( 'Detail', 'pr_logs' ),
    );

    return $columns;
  }

  /**
   * Define default columns for table list
   * @param mixed $item
   * @param mixed $column_name
   * @return void
   */
  public function column_default( $item, $column_name ) {

    switch( $column_name ) {

      case 'log_ip':
        return $item->ip;
        break;
      case 'log_action':
        return $item->action;
        break;
      case 'log_type':
        return $item->type;
        break;
      default:
        return print_r( $item, true );
    }
  }

	/**
	 * Override cb column
	 *
	 * @access public
	 * @param array $item
	 * @return void
	 */
   public function column_cb( $item ) {

      //return sprintf( '<input type="checkbox" name="linked_post" value="%s" />', $item->p2p_id );
	}

    /**
    * single_row function.
    * @param array $item
    * @return void
    */
    public function single_row( $item ) {

      static $row_class = '';
      $row_class = !strlen($row_class) ? ' class="alternate"' : '';

      echo '<tr' . $row_class . ' id=' . $item->id .'">';
      $this->single_row_columns( $item );
      echo '</tr>';
    }

    /**
    * Override column issue title, adding link to post
    * @param  object $item
    * @echo
    */
    public function column_object_id( $item ) {

      $issue = get_post($item->object_id);
      echo '<a target="_blank" href="'.get_edit_post_link($item->object_id).'">' . $issue->post_title . '</a>';
    }

    public function column_log_date( $item ) {
      $date = date( 'Y-m-d H:i:s', $item->log_date );
      echo $date;
    }

    public function column_log_detail( $item ) {

      echo '<a href="#TB_inline?width=100%&height=550&inlineId=log-id-'.$item->id.'" class="thickbox"><i class="press-eye"></i></a>';
      echo '<div style="display:none" class="log-detail" id="log-id-'.$item->id.'">'.$item->detail.'</div>';
    }

    public function column_log_author( $item ) {
      global $wp_roles;

      if ( ! empty( $item->author ) && 0 !== (int) $item->author ) {
        $user = get_user_by( 'id', $item->author );
        if ( $user instanceof WP_User && 0 !== $user->ID ) {
          //$user->display_name
          return sprintf(
            '<a href="%s">%s <br/><span class="aal-author-name">%s</span></a><br /><small>%s</small>',
            get_edit_user_link( $user->ID ),
            get_avatar( $user->ID, 40 ),
            $user->display_name,
            isset( $user->roles[0] ) && isset( $wp_roles->role_names[ $user->roles[0] ] ) ? $wp_roles->role_names[ $user->roles[0] ] : __( 'Unknown', 'aryo-aal' )
          );
        }
      }
      return sprintf(
        '<span class="aal-author-name">%s</span>',
        __( 'Guest', 'aryo-aal' )
      );
    }

   /**
	 * Override default display table adding custom bulk action and pagination
	 * @param mixed $which
	 * @echo
	 */
   public function display_tablenav( $which ) {

      echo '<div class="tablenav ' . esc_attr( $which ) . '">';

      $this->extra_tablenav( $which );
      $this->pagination( $which );

      echo '<br class="clear" />
      </div>';
   }

	/**
	 * Override default pagination function to add number items to display
	 * @param mixed $which
	 * @echo
	 */
   public function pagination( $which ) {

      if ( empty( $this->_pagination_args ) ) {
         return;
      }

      $items_to_display = array( 10, 30, 45, 90 );
      $total_items = $this->_pagination_args['total_items'];
      $total_pages = $this->_pagination_args['total_pages'];

		$output = '<span class="displaying-num">' . __( 'Number of items to display', 'edition' ) . '</span>
      <select class="number_element_input">';
		foreach ( $items_to_display as $option ) {
         $output .= '<option ' . ( $this->_per_page == $option ? 'selected' : '' ) . '>' . $option . '</option>';
		}
      $output .= '</select>';
		$output .= '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';
		$current = $this->get_pagenum();
		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );
		$page_links = array();
		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
         'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
         'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which ) {
			$html_current_page = $current;
      }
		else {
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				$current,
				strlen( $total_pages )
			);
    }

    $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';
    $page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if ( !empty( $infinite_scroll ) ) {
         $pagination_links_class = ' hide-if-js';
      }
		$output .= "\n<span class=\"$pagination_links_class\">" . join( "\n", $page_links ) . "</span>";

    if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
    }
		else {
			$page_class = ' no-pages';
    }

      $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	/**
	 * Override default extra_tablenav
	 * @param mixed $which
	 * @return void
	 */
	public function extra_tablenav( $which ) {
      global $wp_meta_boxes;
      $views = $this->get_views();
      if ( empty( $views ) ) {
         return;
      }
      $this->views();
   }

	/**
	 * Override default display function
	 *
	 * @echo
	 */
   public function display() {

      wp_nonce_field( 'ajax-presslogs-nonce', '_ajax_presslist_nonce' );

      echo '<input id="presslist_paged" type="hidden" name="current_page" value="' . $this->get_pagenum() . '" />';
      echo '<input id="presslist_screen_per_page" type="hidden" name="scree_per_page" value="' . $this->_per_page . '" />';
      parent::display();
	}



   /**
    * Ajax fetch callback
    *
    * @return void
    */
   public function ajax_fetch_logs_callback() {

      check_ajax_referer( 'ajax-presslogs-nonce', '_ajax_presslist_nonce' );
      $presslogs = new Pressroom_Logs_Table();
      $presslogs->_ajax_response_callback();
      exit;
   }

	/**
	 * Update post status related with current edition
	 * @access public
	 * @return void
	 */
	public function presslist_ajax_callback() {

    $value = p2p_get_meta( $_POST['id'], 'status', true );

    if ( p2p_update_meta( $_POST['id'], 'status', !$value ) ) {
      wp_send_json( $value );
		}

    exit;
	}


   /**
    * Add scripts required by table list
    *
    * @void
    */
  public static function add_presslist_scripts() {

    //wp_register_script( 'presslist-ajax', PR_ASSETS_URI . 'js/presslist_ajax.js', array( 'jquery' ), '1.0', true );
    wp_register_script( 'presslist-drag-drop', PR_ASSETS_URI . 'js/presslist_drag_drop.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script( 'presslist-ajax' );
    wp_enqueue_script( 'presslist-drag-drop' );
  }

   /**
    * Manage posts via ajax
    *
    * @return json array
    */
   protected function _ajax_response_callback() {

      check_ajax_referer( 'ajax-presslogs-nonce', '_ajax_presslist_nonce' );
      $this->prepare_items();

      $total_items = $this->_pagination_args['total_items'];
      $total_pages = $this->_pagination_args['total_pages'];
      ob_start();
      if ( !empty( $_REQUEST['no_placeholder'] ) )
           $this->display_rows();
      else
           $this->display_rows_or_placeholder();

      $rows = ob_get_clean();

      ob_start();
      $this->print_column_headers();
      $headers = ob_get_clean();

      ob_start();
      $this->pagination( 'top' );
      $pagination_top = ob_get_clean();

      ob_start();
      $this->pagination( 'bottom' );
      $pagination_bottom = ob_get_clean();

      $response = array( 'rows' => $rows );
      $response['pagination']['top'] = $pagination_top;
      $response['pagination']['bottom'] = $pagination_bottom;
      $response['column_headers'] = $headers;

      if ( isset( $total_items ) )
           $response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

      if ( isset( $total_pages ) ) {
           $response['total_pages'] = $total_pages;
           $response['total_pages_i18n'] = number_format_i18n( $total_pages );
      }

      echo json_encode( $response );
      exit;
   }

   /**
    *
    * Select all posts in p2p connection with the current edition
    *
    * @return array
    */
  public function get_logs() {

    $data = array();

    return $data;
  }

  public function init_thickbox() {
     add_thickbox();
  }

  public function add_custom_styles() {
    wp_register_style( 'pr_logs', PR_ASSETS_URI . 'css/pr.logs.css' );
    wp_enqueue_style( 'pr_logs' );
  }
}

$pressroom_logs_table = new Pressroom_Logs_Table();
