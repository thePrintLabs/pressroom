<?php

require_once(ABSPATH . 'wp-admin/includes/template.php' );

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
if( ! class_exists('WP_Screen') ) {
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
}
if (is_admin()) require_once(ABSPATH . 'wp-includes/pluggable.php');


/**
 * Pressroom_List_Table class.
 *
 * @extends WP_List_Table
 */

class Pressroom_List_Table extends WP_List_Table {

	protected $edition_post;
	protected $per_page;

   	public function __construct(){
   		global $page;
   		global $post;
	    parent::__construct( array(
            'singular'  => __( 'post', 'edition' ),     	//singular name of the listed records
            'plural'    => __( 'posts', 'edition' ),  		//plural name of the listed records
            'ajax'      => true,
            'screen'	=> 'tpl_edition'

	    ) );
	    add_action( 'wp_ajax_presslist', array($this, 'presslist_callback' ) );
	    add_action( 'wp_ajax_register_template', array($this, 'register_template_callback' ) );
	    add_action( 'admin_enqueue_scripts', array($this, 'presslist_scripts' ) );
	    add_action( 'wp_ajax__ajax_fetch_presslist', array($this, '_ajax_fetch_presslist_callback') );
	    add_action( 'wp_ajax_bulk_presslist', array($this, 'bulk_presslist_callback' ) );
	    add_action( 'admin_print_scripts', array($this, 'jsLibs') );
	    add_action( 'wp_ajax_update-custom-post-order', array($this, 'savePostOrder') );

    }

	/**
	 * no_items function.
	 * Function for display not posts found
	 * @access public
	 * @return void
	 */

	public function no_items() {
		_e( 'No posts found, dude.' );
	}


	public function get_bulk_actions() {
        $actions = array(
            'include'    => 'include',
            'exclude'    => 'exclude',
        );
        return $actions;
    }

    /**
     * bulk_presslist_callback function.
     * Update p2p meta via ajax callback
     * @access public
     * @return void
     */

    public function bulk_presslist_callback() {

	    foreach ($_POST['connected_posts'] as $single_post_id) {
		    if($_POST['action_to_do'] === 'include') {
			    p2p_update_meta( $single_post_id, 'state', 1 );
		    }
		    else {
			    p2p_update_meta( $single_post_id, 'state', 0 );
		    }
	    }
        die(); // this is required to return a proper result
    }

	/**
	 * column_default function.
	 * Define default columns for table list
	 * @access public
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
		return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * get_sortable_columns function.
	 * Define sortable columns
	 * @access public
	 * @return void
	 */

	public function get_sortable_columns() {
		$sortable_columns = array(
			'post_title'  => array('post_title',false),
			'post_author' => array('post_author',false),
			'post_date'   => array('post_date',false)
		);
		return $sortable_columns;
	}

	/**
	 * get_columns function.
	 *
	 * @access public
	 * @return mixed
	 */

	public function get_columns(){
		$columns = array(
			'cb'        	=> '<input type="checkbox" />',
			'post_title' 	=> __( 'Title', 'press_listtable' ),
			'post_author'   => __( 'Author', 'press_listtable' ),
			'post_date'     => __( 'Date', 'press_listtable' ),
			'state'     	=> __( 'State', 'press_listtable' ),
			'template'     	=> __( 'Template', 'press_listtable' ),
			);
		return $columns;
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
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'title';
		// If no order, default to asc
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp( $a->$orderby, $b->$orderby );
		// Send final sort direction to usort

		return ( $order === 'asc' ) ? $result : -$result;
	}

	/**
	 * column_cb function.
	 *
	 * @access public
	 * @param array $item
	 * @return void
	 */

	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="" value="%s" />', $item->p2p_id
		);
	}

	/**
	 * column_state function.
	 * Define post state columns
	 * @access public
	 * @param array $item
	 * @return string
	 */

	public function column_state($item) {
		$state = p2p_get_meta( $item->p2p_id, 'state', true );
		if($state) {
			$state_label = '<i class="icon-eye"></i>';
		}
		else {
			$state_label = '<i class="icon-eye-off"></i>';
		}
		return '<a id="r_'.$item->p2p_id.'" class="presslist-state" data-state="'.($state ? 1 : 0).'" data-index="'.$item->p2p_id.'" href="#" >'.__($state_label, 'edition').'</a>';
	}


	/**
	 * column_template function.
	 *
	 * @access public
	 * @param array $item
	 * @return string
	 */

	public function column_template($item) {
		$template = p2p_get_meta( $item->p2p_id, 'template', true );
		$themes = TPL_Themes::get_themes();
		$current_theme = strtolower(get_post_meta( $this->edition_post->ID, '_tpl_themes_select', true ));

		$html = '<select class="presslist-template">';
		$pages = $themes[$current_theme];

		foreach($pages as $page) {
			if($page['name']) {
				$html.= '<option '.($template == $page['filename'] ? 'selected="selected"' : '').'id="t_'.$item->p2p_id.'" data-index="'.$item->p2p_id.'" value="'.$page['filename'].'" >'.$page['name'].'</option>';
			}
		}
		$html .= '</select>';
		return $html;
	}

	/**
	 * single_row function.
	 *
	 * @access public
	 * @param array $item
	 * @return void
	 */

	public function single_row( $item ) {
		$order_id = p2p_get_meta( $item->p2p_id, 'order', true );
	    static $row_class = '';
	    $row_class = ( $row_class == '' ? ' class="alternate"' : '' );

	    echo '<tr' . $row_class . ' id='.$item->p2p_id.' data-index="'.$order_id.'">';
	    $this->single_row_columns( $item );
	    echo '</tr>';
	  }

	/**
	 * get_connected_data function.
	 * Select all posts in p2p connection with the current edition
	 * @access public
	 * @return array
	 */

	public function get_connected_data() {

		if (isset($_GET['edition_id'])) {
			$this->edition_post = get_post($_GET['edition_id']);
		}
		else {
			$this->edition_post = get_post();
		}

		$connected = new Wp_query( array(
		  'connected_type' 		    => 'edition_post',
		  'connected_items' 	    => $this->edition_post,
		  'nopaging' 			        => true,
		  'connected_orderby'     => 'order',
		  'connected_order' 	    => 'asc',
		  'connected_order_num'   => true,
		) );

		$data = array();
		foreach ($connected->posts as $related) {

			if($related->post_author){
				$related->post_author = get_the_author_meta( 'display_name', $related -> post_author );
			}
		$data[] = $related;
		}
		return $data;
	}


	/**
	 * display_tablenav function.
	 * Override default display table adding custom bulk action and pagination
	 * @access public
	 * @return void
	 */
	 public function display_tablenav( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
	        <?php //i have to recreate the bulk select manually to fix wordpress bug on action edit ?>
            <div class="alignleft actions">
                <select name="actiontwo">
				<option selected="selected" value="-1"><?= __( 'Bulk Action', 'edition' )  ?></option>
					<option value="include">include</option>
					<option value="exclude">exclude</option>
				</select>
				<input type="submit" value="Applica" class="button action" id="doaction" name="">
			</div>

            <?php
            $this->extra_tablenav( $which );
            $this->pagination( $which );
            ?>
            <br class="clear" />
        </div>
        <?php
    }

	/**
	 * pagination function.
	 * Override default pagination function to add number items to display
	 * @access public
	 * @param mixed $which
	 * @return void
	 */
	public function pagination($which) {
		if ( empty( $this->_pagination_args ) )
			return;
    //var_dump($this->_pagination_args);
		//extract( $this->_pagination_args, EXTR_SKIP ); // on wp 4.0 generate notice
		$total_items = $this->_pagination_args['total_items'];
    $total_pages = $this->_pagination_args['total_pages'];

		$options = array(5,10,30,45,90);
		$output= '<span class="displaying-num">Number of items to display</span><select class="number_element_input">';
		foreach($options as $option) {
			$output .= '<option '.($this->per_page == $option ? 'selected' : '').'>'.$option.'</option>';
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
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) )
			$pagination_links_class = ' hide-if-js';
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';


		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	/**
	 * extra_tablenav function.
	 * Override default extra_tablenav
	 * @access public
	 * @param mixed $which
	 * @return void
	 */
	public function extra_tablenav( $which )
    {
        global $wp_meta_boxes;
        $views = $this->get_views();
        if ( empty( $views ) )
            return;

        $this->views();
    }

	/**
	 * display function.
	 * Override default display function
	 * @access public
	 * @return void
	 */
	public function display() {

	    wp_nonce_field( 'ajax-presslist-nonce', '_ajax_presslist_nonce' );
	    //echo '<input id="presslist_order" type="hidden" name="order" value="' . $this->_pagination_args['order'] . '" />';
	    //echo '<input id="presslist_orderby" type="hidden" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';
	    echo '<input id="presslist_edition_id" type="hidden" name="edition_id" value="' . get_the_ID() . '" />';
	    echo '<input id="presslist_paged" type="hidden" name="current_page" value="' . $this->get_pagenum() . '" />';
	    echo '<input id="presslist_screen_per_page" type="hidden" name="scree_per_page" value="' . $this->per_page . '" />';
	    parent::display();
	}

	/**
	 * ajax_response function.
	 * Manage posts with ajax
	 * @access public
	 * @return json array
	 */
	public function ajax_response() {
	    check_ajax_referer( 'ajax-presslist-nonce', '_ajax_presslist_nonce' );
	    $this->prepare_items();
	    //extract( $this->_args );
	    //extract( $this->_pagination_args, EXTR_SKIP );
      $total_items = $this->_pagination_args['total_items'];
      $total_pages = $this->_pagination_args['total_pages'];
	    ob_start();
	    if ( ! empty( $_REQUEST['no_placeholder'] ) )
	        $this->display_rows();
	    else
	        $this->display_rows_or_placeholder();
	    $rows = ob_get_clean();

	    ob_start();
	    $this->print_column_headers();
	    $headers = ob_get_clean();

	    ob_start();
	    $this->pagination('top');
	    $pagination_top = ob_get_clean();
	    ob_start();
	    $this->pagination('bottom');
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
	    die( json_encode( $response ) );
	}

	/**
	 * _ajax_fetch_presslist_callback function.
	 *
	 * @access public
	 * @return void
	 */
	public function _ajax_fetch_presslist_callback() {
	 	check_ajax_referer( 'ajax-presslist-nonce', '_ajax_presslist_nonce' );
	 	$presslist = new Pressroom_List_Table();
	    $presslist->ajax_response();
	}

	/**
	 * prepare_items function.
	 * Override default prepare item setting pagination
	 * @access public
	 * @return void
	 */
	public function prepare_items() {
		$data = $this->get_connected_data();
		$columns  = $this->get_columns();
		$hidden   = array();
		//$sortable = $this->get_sortable_columns();
		$sortable = '';
		$this->_column_headers = array( $columns, $hidden, $sortable );
		if($data) {
			//usort( $data, array( &$this, 'usort_reorder' ) );
		}
		$per_page = ! empty( $_REQUEST['post_per_page'] ) ? $_REQUEST['post_per_page'] : 5;
		$this->per_page = $per_page;
		$current_page = $this->get_pagenum();
		$total_items = count( $data );
		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
	        'total_pages'   => ceil( $total_items / $per_page ),
			'orderby'   => ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'title',
	        'order'     => ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc',
		) );

	}

	public function savePostOrder() {
	    if ('sort-posts' == $_POST['event']) {
		    $posts = explode(',', $_POST['order']);
		    $paged = $_POST['currentPage'];
		    $post_per_page = $_POST['postPerPage'];
		    foreach( $posts as $k => $post ) {
			    $position = $post_per_page * ( $paged - 1 ) + $k + 1;
			     p2p_update_meta( $post, 'order', $position );
		    }
	    }
	    die();
	}

	/**
	 * presslist_scripts function.
	 * Add script for the table list
	 * @access public
	 * @return void
	 */
	public function presslist_scripts() {
		wp_register_script( 'presslist-ajax', TPL_PLUGIN_ASSETS . 'js/presslist_ajax.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'presslist-ajax' );
	}

	/**
	 * presslist_callback function.
	 * Update post state related with current edition
	 * @access public
	 * @return void
	 */
	public function presslist_callback() {

		if(p2p_update_meta( $_POST['id'], 'state', ($_POST['state'] ? 0 : 1) )) {
			echo 'updated';
		}

		die(); // this is required to return a proper result
	}

	public function register_template_callback() {

		if(p2p_update_meta( $_POST['id'], 'template', ($_POST['template'] ? $_POST['template'] : '') )) {
			echo 'updated';
		}
		die(); // this is required to return a proper result
	}

	function jsLibs() {
	    wp_enqueue_script('jquery-ui-core');
	    wp_enqueue_script('jquery-ui-sortable');

	    wp_register_script( 'presslist-drag-drop', TPL_PLUGIN_ASSETS . 'js/presslist_drag_drop.js', array( 'jquery' ), '1.0', true );
		  wp_enqueue_script( 'presslist-drag-drop' );
	}

}

$pressroom_list_table = new Pressroom_List_Table();
