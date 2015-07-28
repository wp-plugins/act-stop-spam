<?php
/**
 * Lists data from act_stop_spam table.
 * @package   ActStopSpam/Classes
 * @author    ActSupport <plugin@actsupport.com>
 * @license   GPL-2.0+
 * @link      http://www.actsupport.com
 * @copyright 2015 ActSupport.com
 * @version   1.0
 * class-act-table.php
 */
if( is_admin() &&! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Act_Stop_Spam_Table extends WP_List_Table {

		private $order;
        private $orderby;
        private $act_ips_perpage = 25;
		

    function __construct(){
    global $status, $page;

    parent::__construct( array(
        'singular'  => __( 'IP Address', 'mylisttable' ),     //singular name of the listed records
        'plural'    => __( 'IP Address', 'mylisttable' ),   //plural name of the listed records
        'ajax'      => false        //does this table support ajax?
    ) );
	$this->set_order();
    $this->set_orderby();
    add_action( 'admin_head', array( &$this, 'admin_header' ) );            
    }
	
	public function set_order()
    {
        $order = 'DESC';
        if (isset($_GET['order'])&&$_GET['order']!="")
        	$order = $_GET['order'];
        $this->order = esc_sql($order);
    }
		
	public function set_orderby()
    {
    	$orderby = 'latest_visit';
        if (isset($_GET['orderby'])&&$_GET['orderby']!="")
              $orderby = $_GET['orderby'];
        $this->orderby = esc_sql($orderby);
    }
	private function get_sql_results()
    {
    	global $wpdb;
        $args = array('act_id', 'ip_address', 'first_visit', 'latest_visit', 'occurrence');
        $sql_select = implode(', ', $args);
		$search_query="";
		if( isset($_POST['s'])&&$_POST['s']!="" ){
			$Search_string  = filter_input( INPUT_POST, 's', FILTER_SANITIZE_STRING );
			$search_query="where ip_address like '%$Search_string%'";
		}
        $sql_results = $wpdb->get_results("SELECT " . $sql_select . " FROM " . $wpdb->prefix . "act_stop_spam $search_query ORDER BY $this->orderby $this->order ",ARRAY_A);
        return $sql_results;
     }

	 public function admin_header() {
    	$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
    	if( 'act_stop_spam_list' != $page )
    	return;
    	echo '<style type="text/css">';
    	echo '.wp-list-table .column-act_id { width: 5%; }';
    	echo '.wp-list-table .column-ip_address { width: 30%; }';
    	echo '.wp-list-table .column-first_visit { width: 35%; }';
    	echo '.wp-list-table .column-latest_visit { width: 20%;}';
		echo '.wp-list-table .column-occurrence { width: 10%;}';
		echo '.act-shield{font-size:28px;padding-right:5px; color:#090;}';
    	echo '</style>';
  	}

  	public function no_items() {
    	_e( 'No Spam IP Address Visited.' );
  	}

  	public function column_default( $item, $column_name ) {
    switch( $column_name ) { 
        case 'ip_address':
		return '<a href="http://www.stopforumspam.com/ipcheck/'.$item[ $column_name ].'" target="_blank">'.$item[ $column_name ].'</a>';
		break;
		case 'occurrence':
        return $item[ $column_name ];
		break;
        case 'first_visit':
        case 'latest_visit':
		return date("d M Y H:i:s",strtotime($item[$column_name ]));
		break;
        default:
        return '-' ;
    }
  }

	public function get_sortable_columns() {
  		$sortable_columns = array(
    	'ip_address'  => array('ip_address',false),
    	'first_visit' => array('first_visit',false),
    	'latest_visit'   => array('latest_visit',false),
    	'occurrence'   => array('occurrence',false)
  		);
  	return $sortable_columns;
	}

	public function get_columns(){
    	$columns = array(
        'cb'        => '<input type="checkbox" />',
        'ip_address' => __( 'IP Address', 'mylisttable' ),
        'first_visit'    => __( 'First Visit', 'mylisttable' ),
        'latest_visit'      => __( 'Latest Visit', 'mylisttable' ),
        'occurrence'      => __( 'Occurrence', 'mylisttable' )
        );
        return $columns;
    }

	public function usort_reorder( $a, $b ) {
  		// If no sort, default to title
  		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ip_address';
  		// If no order, default to asc
  		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
  		// Determine sort order
  		$result = strcmp( $a[$orderby], $b[$orderby] );
  		// Send final sort direction to usort
  		return ( $order === 'asc' ) ? $result : -$result;
	}

	public function get_bulk_actions() {
  		$actions = array(
    		'delete'    => 'Delete'
  		);
  	return $actions;
	}
	public function process_bulk_action() {
		global $wpdb;
        // security check!
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
            $action = 'bulk-' . $this->_args['plural'];

	          if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );
        }

        $action = $this->current_action();

        switch ( $action ) {

            case 'delete':
			$actid=$_POST['act_id'];
			if(is_array($actid)&&count($actid)>0)
			{
				$deleted=0;
				foreach($actid as $key=>$val)
				{
					if($val>0){
					$wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "act_stop_spam WHERE act_id = %d",$val));
					$deleted+=1;
					}
				}
				if($deleted>0){
				echo '<div id="message" class="updated fade">
			<p>
				 '._x( 'Successfully deleted IP listings.','success' ).'
			</p>
			</div>';
				}else{
					echo '<div id="message" class="error fade">
			<p>
				 '._x( 'No IP listings Deleted.' ,'error').'
			</p>
			</div>';
				}
			}
               break;

            default:
                // do nothing or something else
                return;
                break;
        }

        return;
    }
	public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="act_id[]" value="%s" />', $item['act_id']
        );    
    }

	public function prepare_items() {
		$this->process_bulk_action();
  		$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array(
                $columns,
                $hidden,
                $sortable
           );

            // Getting Act Stop Spam Data List
            $act_data = $this->get_sql_results();

            empty($act_data) AND $act_data = array();

            # >>>> Pagination
            $per_page = $this->act_ips_perpage;
            $current_page = $this->get_pagenum();
            $total_items = count($act_data);
            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ));
            $last_ip = $current_page * $per_page;
            $first_ip = $last_ip - $per_page + 1;
            $last_ip > $total_items AND $last_ip = $total_items;

            // Setup the range of keys/indizes that contain 
            // Flip keys with values as the range outputs the range in the values.
            $range = array_flip(range($first_ip - 1, $last_ip - 1, 1));

            // Filter out the IP we're not displaying on the current page.
            $act_ip_array = array_intersect_key($act_data, $range);
            # <<<< Pagination
            $this->items = $act_ip_array;
	
	}
}