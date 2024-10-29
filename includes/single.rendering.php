<?php
/**
 * WOOCONVO_RenderSingleConvo render's the Convo against each order
 * in frontend and admin side
 * since version 9.0 the convo panel will also be rendered insid each orders
 * Date Created: July 11, 2023
 * Created By: Najeeb Ahmad
 * */
 
class WOOCONVO_RenderSingleConvo {
    
    private static $ins = null;
	
	public static $settings;
	
	public static function __instance()
	{
		// create a new object if it doesn't exist.
		is_null(self::$ins) && self::$ins = new self;
		return self::$ins;
	}
	
	public function __construct() {
		
        // Order Admin
        add_action( 'admin_init', [$this, 'render_convos_in_orders'] );
        
        // Order meta box in WC wc-orders page
        add_action( 'add_meta_boxes_woocommerce_page_wc-orders', [$this, 'render_convos_in_orders_pages'], 99, 1);
        
        // Order Frontend
        add_action("woocommerce_order_details_before_order_table", 
                    function($order){
					    echo $this->render_orderconvo_order($order);
				    }, 
				    10, 1);
		
	}
	
	public function load_scripts($wooconvo_data){
    	
    	$wooconvo_data = apply_filters('wooconvo_react_data', $wooconvo_data);
		
        echo '<script>window.WOOCONVO_Data=\''.addslashes(json_encode($wooconvo_data)).'\';</script>';
        
        $react_js  = WOOCONVO8_URL.'/assets/react/front/static/js/'.WOOCONVO8_REACT_SINGLE_JS;
        $react_css = WOOCONVO8_URL.'/assets/react/front/static/css/'.WOOCONVO8_REACT_SINGLE_CSS;
        
        wp_enqueue_style('orderconvo-react-css', $react_css);
        wp_enqueue_script('orderconvo-react-js', $react_js, [], WOOCONVO8_VERSION, true );
        
    }
	
	/*
	 * rendering meta box in orders for convos
	*/
	function render_convos_in_orders() {

		add_meta_box( 'orders_convo', 'Order Processing (OrderConvo)',
				function($order){
					echo $this->render_orderconvo_order($order);
				},
				'shop_order', 'normal', 'default');
				
		// Booking plugin support
		add_meta_box( 'orders_convo', 'Order Processing (OrderConvo)',
                function($order){
					echo $this->render_orderconvo_order($order);
				},
        array( 'wc_booking' ), 'normal', 'default');
	}
	
	function render_convos_in_orders_pages($order){
		
		add_meta_box( 'orders_convo', 'Order Processing (OrderConvo)',
		function($order){
			
			$user_id = get_current_user_id();
			$wooconvo_data = [
    	                'user_id'		=> $user_id,
    	                'order_id'		=> $order->get_id(),
						'api_url'		=> get_rest_url(null, 'wooconvo/v1'),
						'context'		=> "wp_admin",
						'settings'      => wooconvo_get_settings(),
						];
						
			echo $this->render_orderconvo_order($order, $wooconvo_data);
		},
		'woocommerce_page_wc-orders', 'normal', 'default');
	}
	
	function render_orderconvo_order($order, $override_data=null){
		
		$user_id = get_current_user_id();
		
		$context = is_admin() ? 'wp_admin' : 'myaccount';
    	
    	$wooconvo_data = [
    	                'user_id'		=> $user_id,
    	                'order_id'		=> $order->ID,
						'api_url'		=> get_rest_url(null, 'wooconvo/v1'),
						'context'		=> $context,
						'settings'      => wooconvo_get_settings(),
						];
						
						
		// if override data provided override it
		$wooconvo_data = $override_data ? $override_data : $wooconvo_data;
		
		$this->load_scripts($wooconvo_data);
		
		$html = '<div class="wooconvo-wp-admin-wrapper">';
	    $html .= apply_filters('wooconvo_front_root', '<div id="wooconvo_front_root"></div>');
	    $html .= '</div>';
	    
	    // this filter generated duplicate contents on checkout/thanks page
		// into Astra and StoreFront theme, therefore it is being hiding
	    // return apply_filters('the_content', $html);
		return $html;
	}
	
}

function init_single_rendering(){
	return WOOCONVO_RenderSingleConvo::__instance();
}