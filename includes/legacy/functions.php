<?php 
/**
 * Legacy functions
 */
 

// get all unred orders by meta query
// $user_type: vendor or customer
function wooconvo_get_unread_orders_legacy($user_type, $user_id=null){
    
    $order_statuses = array_keys(wooconvo_get_all_order_statuses());
    $args = array(
        'post_type' => 'shop_order',
        'post_status'  => $order_statuses,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => "wooconvo_unread_{$user_type}",
                'compare' => '>=',
                'value' => 1
            ),
        )
    );
    
    if($user_id){
        $args['meta_query'][] = ['key'=>'_customer_user','value'=>intval($user_id),'compare'=>'='];
    }
    
    $orders  = new WP_Query( $args );
    $orders = $orders->get_posts();
	
	return $orders;
}

// get all wooconvo orders by meta query
function wooconvo_get_orders_legacy($user_id=null){
    
    $order_statuses = array_keys(wooconvo_get_all_order_statuses());
    $args = array(
        'post_type' => 'shop_order',
        'post_status'  => $order_statuses,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'wooconvo_thread',
                'compare' => 'EXISTS'
            ),
        )
    );
    
    if($user_id){
        $args['meta_query'][] = ['key'=>'_customer_user','value'=>intval($user_id),'compare'=>'='];
    }
    
    $orders  = new WP_Query( $args );
    $orders = $orders->get_posts();
	
	$orders = apply_filters('wooconvo_get_orders_query', $orders, $user_id);
	
	return $orders;
}