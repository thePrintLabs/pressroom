<?php
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-includes/pluggable.php';

if ( !class_exists( 'WP_List_Table' ) ) {
   require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if( !class_exists( 'WP_Screen' ) ) {
   require_once ABSPATH . 'wp-admin/includes/screen.php';
}

/**
 * Pressroom_List_Table class.
 *
 * @extends WP_List_Table
 */
class Pressroom_List_Table extends WP_List_Table
{
  protected $_edition_id;
  protected $_per_page;

  public function __construct() {

    if ( !is_admin() ) {
       return;
    }

    parent::__construct( array(
       'singular'  => __( 'post', 'edition' ),
       'plural'    => __( 'posts', 'edition' ),
       'screen'	   => 'pr_edition',
       'ajax'      => true,
    ) );

    add_action( 'wp_ajax_presslist', array( $this, 'presslist_ajax_callback' ) );
    add_action( 'wp_ajax_register_template', array( $this, 'ajax_register_template_callback' ) );
    add_action( 'wp_ajax__ajax_fetch_presslist', array( $this, 'ajax_fetch_presslist_callback' ) );
    add_action( 'wp_ajax_bulk_presslist', array( $this, 'ajax_bulk_callback' ) );
    add_action( 'wp_ajax_update-custom-post-order', array( $this, 'ajax_update_post_order' ) );
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
    $data = $this->get_linked_posts();
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
       'post_title'   => __( 'Title', 'press_listtable' ),
       'post_author'  => __( 'Author', 'press_listtable' ),
       'post_date'    => __( 'Date', 'press_listtable' ),
       'post_type'    => __( 'Post type', 'press_listtable' ),
       'status'        => __( 'Status', 'press_listtable' ),
       'template'     => __( 'Layout', 'press_listtable' ),
    );

    return $columns;
  }

  /**
   * Override default bulk actions
   *
   * @return array
   */
  public function get_bulk_actions() {
    $actions = array(
       'include'    => 'include',
       'exclude'    => 'exclude',
    );

    return $actions;
  }

  /**
   * Define default columns for table list
   * @param mixed $item
   * @param mixed $column_name
   * @return void
   */
  public function column_default( $item, $column_name ) {

    switch( $column_name ) {

      case 'post_title':
  		case 'post_author':
  		case 'post_date':
        return $item->$column_name;
  		default:
        return print_r( $item, true );
    }
  }

  /**
   * get_sortable_columns function.
   * Define sortable columns
   *
   * @return array
   */
  public function get_sortable_columns() {

    $sortable_columns = array(
      'post_title'  => array( 'post_title', false ),
			'post_author' => array( 'post_author', false ),
			'post_date'   => array( 'post_date', false )
		);

    return $sortable_columns;
	}

	/**
	 * usort_reorder function.
	 *
	 * @access public
	 * @param mixed $a
	 * @param mixed $b
	 * @return void
	 */
  public function usort_reorder( $a, $b ) {
	  // If no sort, default to title
		$orderby = !empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'title';
		// If no order, default to asc
		$order = !empty($_GET['order'] ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp( $a->$orderby, $b->$orderby );
		// Send final sort direction to usort
    return ( $order === 'asc' ) ? $result : -$result;
  }

	/**
	 * Override cb column
	 *
	 * @access public
	 * @param array $item
	 * @return void
	 */
   public function column_cb( $item ) {

      return sprintf( '<input type="checkbox" name="linked_post" value="%s" />', $item->p2p_id );
	}

	/**
	 * Define post status columns
	 * @param object $item
	 * @return string
	 */
   public function column_status( $item ) {

    $status = p2p_get_meta( $item->p2p_id, 'status', true );
		if ( $status ) {
         $status_label = '<i class="press-eye"></i>';
		}
		else {
			$status_label = '<i class="press-eye-off"></i>';
		}

      return '<a id="r_' . $item->p2p_id . '" class="presslist-status" data-index="' . $item->p2p_id . '" href="#">' . __( $status_label, 'edition' ).'</a>';
	}

	/**
	 * Themes column
	 * @param object $item
	 * @return string
	 */
   public function column_template( $item ) {

    $template = p2p_get_meta( $item->p2p_id, 'template', true );
		$themes = PR_Theme::get_themes();
    $current_theme = get_post_meta( $this->_edition_id, '_pr_theme_select', true );

    if ( $current_theme ) {
      if ( array_key_exists( $current_theme, $themes ) ) {
        if ( $themes[$current_theme]['active'] ) {
          $html = '<select class="presslist-template">';
          $pages = $themes[$current_theme]['layouts'];
          $html .= '<option data-index="' . $item->p2p_id . '"> --- </option>';
          foreach ( $pages as $page ) {
            if ( $page['rule'] == 'content' || $page['rule'] == 'cover' ) {
              $page_path = $themes[$current_theme]['path'] . DS . $page['path'];
              $html.= '<option ' . ( $template == $page_path  ? 'selected="selected"' : '' ) . ' id="t_' . $item->p2p_id . '" data-index="' . $item->p2p_id . '" value="' . $page_path . '" >' . $page['name'] . '</option>';
            }
          }
          $html .= '</select>';

          if( has_action( "pr_presslist_{$item->post_type}" ) ) {
            do_action_ref_array( "pr_presslist_{$item->post_type}", array( $item, &$html ) );
          }

        }
        else {
          $html = '<i>' . __('Theme is disabled. Activate it or change to another theme.', 'edition') . '</i>';
        }
      }
      else {
        $html = '<i>' . __('The theme was not found. Update the issue to fix.', 'edition') . '</i>';
      }
    }
    else {
      $html = '<i>' . __('Please assign a theme.', 'edition') . '</i>';
    }

    return $html;
   }

   /**
    * Override column post title, adding link to post
    * @param  object $item
    * @echo
    */
   public function column_post_title( $item ) {

      echo '<a target="_blank" href="'.get_edit_post_link($item->ID).'">' . $item->post_title . '</a>';
   }

   /**
    * Post type column
    * @param  object $item
    * @echo
    */
   public function column_post_type( $item ) {

      $post_type = get_post_type_object($item->post_type);
      echo $post_type->labels->singular_name;
   }

	/**
	 * single_row function.
	 * @param array $item
	 * @return void
	 */
   public function single_row( $item ) {

      static $row_class = '';
      $order_id = p2p_get_meta( $item->p2p_id, 'order', true );
      $row_class = !strlen($row_class) ? ' class="alternate"' : '';

      echo '<tr' . $row_class . ' id=' . $item->p2p_id . ' data-index="' . $order_id . '">';
      $this->single_row_columns( $item );
      echo '</tr>';
   }

   /**
	 * Override default display table adding custom bulk action and pagination
	 * @param mixed $which
	 * @echo
	 */
   public function display_tablenav( $which ) {

      echo '<div class="tablenav ' . esc_attr( $which ) . '">
      <div class="alignleft actions">
      <select name="actiontwo">
      <option selected="selected" value="-1">' . __( 'Change status', 'edition' ) . '</option>
      <option value="include">include</option>
      <option value="exclude">exclude</option>
      </select>
      <input type="submit" value="' . __( 'Save', 'edition' ) . '" class="button action" id="doaction" />
      </div>';

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

      wp_nonce_field( 'ajax-presslist-nonce', '_ajax_presslist_nonce' );
      echo '<input id="presslist_edition_id" type="hidden" name="edition_id" value="' . get_the_ID() . '" />';
      echo '<input id="presslist_paged" type="hidden" name="current_page" value="' . $this->get_pagenum() . '" />';
      echo '<input id="presslist_screen_per_page" type="hidden" name="scree_per_page" value="' . $this->_per_page . '" />';
      parent::display();
	}

   /**
    * Update post order via ajax
    *
    * @return void
    */
	public function ajax_update_post_order() {

      if ( $_POST['event'] == 'sort-posts' ) {
         $posts = explode(',', $_POST['order']);
         $paged = (int)$_POST['currentPage'];
         $post_per_page = (int)$_POST['postPerPage'];
         foreach ( $posts as $k => $post ) {

            $position = $post_per_page * ( $paged - 1 ) + $k + 1;
            p2p_update_meta( $post, 'order', $position );
         }
      }
      exit;
   }

   /**
    * Update p2p meta via ajax callback
    *
    * @return void
    */
   public function ajax_bulk_callback() {

      if ( !empty( $_POST['connected_posts'] ) ) {
        foreach ( $_POST['connected_posts'] as $post_id ) {

          if ( $_POST['action_to_do'] === 'include' ) {
             p2p_update_meta( $post_id, 'status', 1 );
             $return = 1;
          }
          else {
             p2p_update_meta( $post_id, 'status', 0 );
             $return = 0;
          }
        }
        wp_send_json( $return );
      }
      exit;
   }

   /**
    * Ajax fetch callback
    *
    * @return void
    */
   public function ajax_fetch_presslist_callback() {

      check_ajax_referer( 'ajax-presslist-nonce', '_ajax_presslist_nonce' );
      $presslist = new Pressroom_List_Table();
      $presslist->_ajax_response_callback();
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

	public function ajax_register_template_callback() {

      if ( isset( $_POST['template'] ) ) {
        if ( p2p_update_meta( $_POST['id'], 'template', ( $_POST['template'] ? $_POST['template'] : '' ) ) ) {
           echo 'updated';
        }
      }
      else {
        p2p_update_meta( $_POST['id'], 'template', null );
      }
		exit;
   }

   /**
    * Add scripts required by table list
    *
    * @void
    */
  public static function add_presslist_scripts() {

    wp_register_script( 'presslist-ajax', PR_ASSETS_URI . 'js/presslist_ajax.js', array( 'jquery' ), '1.0', true );
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

      check_ajax_referer( 'ajax-presslist-nonce', '_ajax_presslist_nonce' );
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
  public function get_linked_posts() {

    global $post;
    if ( isset( $_GET['edition_id'] ) ) {
      $this->_edition_id = (int)$_GET['edition_id'];
    }
    else {
      $this->_edition_id = $post->ID;
    }

    $data = array();
    $query_post = PR_Edition::get_linked_posts( $this->_edition_id );
    foreach ( $query_post->posts as $related ) {

      if ( $related->post_author ) {
        $related->post_author = get_the_author_meta( 'display_name', $related->post_author );
      }
      array_push( $data, $related );
    }

    return $data;
  }
}

$pressroom_list_table = new Pressroom_List_Table();
