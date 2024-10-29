<?php
if (!class_exists('AEIDN_WooCommerce_OrderList')) {

    class AEIDN_WooCommerce_OrderList
    {

        public function __construct()
        {
            if (is_admin()) {
                add_action('admin_enqueue_scripts', [$this, 'assets']);
                add_action('manage_shop_order_posts_custom_column', [$this, 'columnsData'], 100);
            }
        }

        public function assets()
        {

            $plugin_data = get_plugin_data(AEIDN_FILE_FULLNAME);
            wp_enqueue_style('aeidn-wc-ol-style', plugins_url('assets/css/wc_ol_style.css', AEIDN_FILE_FULLNAME), array(), $plugin_data['Version']);
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script('aeidn-wc-ol-script', plugins_url('assets/js/wc_ol_script.js', AEIDN_FILE_FULLNAME), array(), $plugin_data['Version']);
        }

        public function columnsData($column)
        {
            global $post;

            $actions = array();

            if ($column === 'order_title') {
                $actions = array_merge($actions, array(
                    'aeidn_product_info' => sprintf('<a class="aeidn-order-info" id="aeidn-%1$d" href="/">%2$s</a>', $post->ID, 'AliExpressImporter Info')
                ));

            }

            $actions = apply_filters('aeidn_wcol_row_actions', $actions, $column);

            if (count($actions) > 0) {
                echo implode($actions, ' | ');
            }

        }
    }
}
new AEIDN_WooCommerce_OrderList();