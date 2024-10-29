<?php
/**
 * WooConvo Orders Class
 * Singlton class to handle:
 * add_message(thread[])
 * get_new_message() return message[]
 * set_unread_count_by_user_type() // order meta key {wooconvo_unread_$type + 1}
 */

class WOOCONVO_Order {
    
    protected static $order;
    protected static $orderid;

    function __construct( $order_id = null ) {
        
        // Assuming $order_id is provided to the method or constructor where this code resides
        self::$orderid = $order_id;
        self::$order = wc_get_order($order_id);
        
        // Corrected: Use self::$order to check if the order is a refund
        if ( is_a( self::$order, 'WC_Order_Refund' ) ) {
            // If it's a refund, update self::$order to the parent order
            self::$order = wc_get_order( self::$order->get_parent_id() );
        }

        
        $this->thread          = $this->get_meta('wooconvo_thread', []);
        $this->is_starred      = (int)$this->get_meta('wooconvo_starred');
        $this->unread_vendor   = (int)$this->get_meta('wooconvo_unread_vendor');
        $this->unread_customer = (int)$this->get_meta('wooconvo_unread_customer');
        $this->revisions_limit = (int)$this->get_meta('wooconvo_revisions_limit');
        $this->first_name      = self::$order->get_billing_first_name();
        $this->last_name       = self::$order->get_billing_last_name();
        $this->status          = self::$order->get_status();
        $this->order_id        = $order_id;
        $this->order_number    = self::$order->get_order_number();
        $this->order_date      = self::$order->get_date_created()->date('c');
    }

    public function get() {
        return [
            'order'             => self::$order,
            'thread'            => $this->thread,
            'is_starred'        => $this->is_starred,
            'unread_vendor'     => $this->unread_vendor,
            'unread_customer'   => $this->unread_customer,
        ];
    }

    public function add_message($user_id, $message, $attachments, $context) {
        $user       = get_userdata($user_id);
        $user_type  = wooconvo_get_member_type_by_context($context);
        
        $display_name   = !$user->display_name ? $user->user_login : $user->display_name;
        $first_name     = !$user->first_name ? $user->user_login : $user->first_name;
        $last_name      = !$user->last_name ? $user->user_login : $user->last_name;
        
        $thread = [ 'user_id'   => $user->ID,
                    'user_name' => $display_name,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'user'      => ['data'=>[],'roles'=>$user->roles],
                    'message'   => sanitize_textarea_field($message),
                    'date'      => date_i18n('Y-m-d H:i:s'),
                    'attachments'=> $attachments,
                    'status'    => 'new',
                    'type'      => 'message',
                    'user_type' => $user_type,
                    'context'  => $context,
                  ];
        
        $this->add_thread($thread);
        $this->set_unread_count_by_user_type(1, $user_type);
        
        do_action('wooconvo_after_message_added', $user, $context, $message, $attachments, $thread, self::$orderid);
        
        return $this;
    }

    public function add_notice($message, $type) {
        $thread = [ 'message'   => sanitize_text_field($message),
                    'date'      => date_i18n('Y-m-d H:i:s'),
                    'status'    => 'new',
                    'type'      => $type,
                  ];
        // wooconvo_logger($thread);
        $this->add_thread($thread);
        return $this;
    }

    private function add_thread($thread) {
        // DEBUGGIN
        // delete_post_meta(self::$orderid , 'wooconvo_thread');
        
        // FILTER: wooconvo_new_message($thread, $order_id)
        $thread = apply_filters('wooconvo_new_message_thread', $thread, self::$orderid);
        // return wooconvo_logger($thread);
        // existing thread
        $existing_thread = $this->get_meta('wooconvo_thread');
        
        $wooconvo_thread = [];
        if( ! $existing_thread ){
            $wooconvo_thread = [$thread];
        }else{
            $wooconvo_thread = [...$existing_thread, $thread];
        }
        
        // wooconvo_logger(self::$orderid);  
        // wooconvo_logger($wooconvo_thread);  
        $this->thread = $wooconvo_thread;
        $this->update_meta('wooconvo_thread', $wooconvo_thread);
        return $this;
    }

    public function set_unread_count_by_user_type($count, $type) {
        // if sentby by type=customer then set undread for wooconvo_unread_vendor
        // and vice versa
        $type = $type == 'customer' ? 'vendor' : 'customer';
        $unread_key = "wooconvo_unread_{$type}";
        
        $unread = $type === 'vendor' ? intval($this->unread_vendor) : intval($this->unread_customer);
        
        if( ! $unread ){
            $unread = $count;
        }else{
            $unread += $count;
        }
        
        if( $type === 'vendor' ){
            $this->unread_vendor = $unread;
        }else{
            $this->unread_customer = $unread;
        }
        
        $this->update_meta($unread_key, $unread);
        return new WOOCONVO_Order(self::$orderid);
    }

    function set_read($user_type) {
        $unread = 0;
        if( $type === 'vendor' ){
            $this->unread_vendor = $unread;
        }else{
            $this->unread_customer = $unread;
        }
        
        $unread_key = "wooconvo_unread_{$user_type}";
        $this->update_meta($unread_key, $unread);
        $order_id = self::$order->get_id();
        return new WOOCONVO_Order($order_id);
    }

    function set_unread($user_type) {
        $unread = 1;
        if( $type === 'vendor' ){
            $this->unread_vendor = $unread;
        }else{
            $this->unread_customer = $unread;
        }
        
        $unread_key = "wooconvo_unread_{$user_type}";
        $this->update_meta($unread_key, $unread);
        $order_id = self::$order->get_id();
        return new WOOCONVO_Order($order_id);
    }

    public function set_starred() {
        $this->update_meta("wooconvo_starred", 1);
        $this->is_starred = 1;
    }

    public function set_unstarred() {
        $this->update_meta("wooconvo_starred", 0);
        $this->is_starred = 0;
    }

    private function get_meta($key, $default = null) {
        // wooconvo_logger(self::$order);
        return self::$order->get_meta($key, true, 'edit') ?: $default;
    }

    private function update_meta($key, $value) {
        self::$order->update_meta_data($key, $value);
        self::$order->save();
    }
}