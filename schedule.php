<?php

if (!function_exists('aeidn_more_reccurences')) {

    function aeidn_more_reccurences($schedules)
    {
        $schedules['aeidn_5_mins'] = array('interval' => 5 * 60, 'display' => __('Every 5 Minutes'));
        $schedules['aeidn_15_mins'] = array('interval' => 15 * 60, 'display' => __('Every 15 Minutes'));
        return $schedules;
    }

}
add_filter('cron_schedules', 'aeidn_more_reccurences');

if (!function_exists('aeidn_schedule_proc')) {

    function aeidn_schedule_proc($show_trace = true)
    {
        $show_trace = false;
        set_error_handler('aeidn_error_handler');
        if ($show_trace) {
            echo '<br/>TRACE (' . date('Y-m-d H:i:s', time()) . '): posted schedule products<br/>';
        }
        $list = AEIDN_Goods::loadGoodsList(
            1,
            100,
            " AND NULLIF(NULLIF(user_schedule_time, '0000-00-00 00:00:00'), '') IS NOT NULL 
              AND user_schedule_time < now() "
        );

        if ($list['items']) {
            /**
             * @var AEIDN_Goods $goods
             */
            foreach ($list['items'] as $goods) {
                try {
                    if ($show_trace) {
                        echo 'TRACE (' . date('Y-m-d H:i:s', time()) . "): check date {$goods->user_schedule_time}<br/>";
                    }

                    if ($show_trace) {
                        echo 'TRACE (' . date('Y-m-d H:i:s', time()) . '): posted...<br/>';
                    }

                    /**
                     * @var AEIDN_AbstractLoader $loader
                     */
                    $loader = aeidn_get_loader($goods->type);

                    if ($loader) {
                        if ($goods->needLoadMoreDetail()) {
                            $loader->loadDetailProc($goods);
                        }

                        if (!$goods->post_id && class_exists('AEIDN_WooCommerce')) {
                            var_dump(AEIDN_WooCommerce::addPost($goods));
                        }

                        $goods->saveField('user_schedule_time', null);

                        if ($show_trace) {
                            echo 'TRACE (' . date('Y-m-d H:i:s', time()) . '): ok<br/>';
                        }
                    } else {
                        if ($show_trace) {
                            echo 'TRACE (' . date('Y-m-d H:i:s', time()) . '): loader not found<br/>';
                        }
                    }
                } catch (Exception $e) {
                    echo $e->getMessage() . '<br/>';
                }
            }
        } else {
            if ($show_trace) {
                echo 'TRACE (' . date('Y-m-d H:i:s', time()) . '): products to post not found<br/>';
            }
        }
//exit();
        restore_error_handler();
    }
//    add_action('init', 'aeidn_schedule_proc');

}
add_action('aeidn_schedule_post_event', 'aeidn_schedule_proc');


if (!function_exists('aeidn_update_price_proc')) {

    function aeidn_update_price_proc($productId = false, $show_trace = true)
    {

        $result = array('state' => 'ok', 'message' => '');

        if (!get_option('aeidn_price_auto_update', false)) {
            return false;
        }
        $update_price = get_option('aeidn_regular_price_auto_update', false);


        set_error_handler('aeidn_error_handler');
        try {
            if ($show_trace) {
                echo 'TRACE (' . date('Y-m-d H:i:s', time()) . '): update stock availability<br/>';
            }

            if ($productId) {
                $posts_by_time = array($productId);
            } else {
                $cnt = get_option('aeidn_update_per_schedule', 20);
                echo "products in work: $cnt<br/>";
                $posts_by_time = aeidn_get_sorted_products_ids('price_last_update', get_option('aeidn_update_per_schedule', 20));
            }


            $cur_aeidn_not_available_product_status = get_option('aeidn_not_available_product_status', 'trash');

            foreach ($posts_by_time as $post_id) {
                $external_id = get_post_meta($post_id, 'external_id', true);
                if ($external_id) {
                    $goods = new AEIDN_Goods($external_id);
                    /* @var $loader AEIDN_AbstractLoader */
                    $loader = aeidn_get_loader($goods->type);

                    if ($loader) {
                        $filters = get_post_meta($post_id, '_aeidn_filters', true);
                        $result = $loader->getDetailProc($goods->external_id, array_merge(array('wc_product_id' => $post_id), is_array($filters) ? $filters : array()));
                        if ($result['state'] === 'ok') {
                            $goods = $result['goods'];

                            // check availability
                            if (!$goods->availability) {
                                if ($show_trace) {
                                    echo 'TRACE (' . date('Y-m-d H:i:s', time()) . "):move to trash {$post_id}<br>";
                                }
                                if ($cur_aeidn_not_available_product_status === 'trash') {
                                    wp_trash_post($post_id);
                                } else if ($cur_aeidn_not_available_product_status === 'outofstock') {
                                    update_post_meta($post_id, '_manage_stock', 'yes');
                                    update_post_meta($post_id, '_stock_status', 'outofstock');
                                    update_post_meta($post_id, '_stock', 0);
                                }
                            } else {
                                wp_untrash_post($post_id);

                                if (isset($goods->additional_meta['quantity'])) {
                                    update_post_meta($post_id, '_manage_stock', 'yes');
                                    update_post_meta($post_id, '_visibility', 'visible');
                                    update_post_meta($post_id, '_stock', (int)$goods->additional_meta['quantity']);
                                } else {
                                    $min_q = (int)get_option('aeidn_min_product_quantity', 5);
                                    $max_q = (int)get_option('aeidn_max_product_quantity', 10);
                                    $min_q = $min_q ? $min_q : 1;
                                    $max_q = $max_q ? $max_q : $min_q;
                                    $quantity = mt_rand($min_q, $max_q);

                                    update_post_meta($post_id, '_stock', $quantity);
                                    update_post_meta($post_id, '_manage_stock', 'yes');
                                    update_post_meta($post_id, '_stock_status', 'instock');
                                }


                                if ($show_trace) {
                                    echo 'TRACE (' . date('Y-m-d H:i:s', time()) . "): product {$post_id} OK<br>";
                                }

                                if ($update_price) {
                                    if ($post_id && class_exists('AEIDN_WooCommerce')) {
                                        AEIDN_WooCommerce::updatePrice($post_id, $goods);
                                    } else {
                                        echo 'TRACE (' . date('Y-m-d H:i:s', time()) . "): product {$post_id} Update price error!<br>";
                                    }


                                    if ($show_trace) {
                                        echo 'TRACE (' . date('Y-m-d H:i:s', time()) . "): update regular price for {$post_id}: {$goods->user_price}<br>";
                                    }
                                }
                            }
                            $result = apply_filters('aeidn_woocommerce_update_price', $result, $post_id, $goods);
                        } else {
                            if ($show_trace) {
                                echo 'TRACE (' . date('Y-m-d H:i:s', time()) . "): error while update price for {$post_id}: {$result['message']}<br>";
                            }
                        }

                        update_post_meta($post_id, 'price_last_update', time());
                    }
                }
            }
        } catch (Exception $e) {
            $result = array('state' => 'error', 'message' => $e->getMessage());
            if ($show_trace) {
                echo $e->getMessage() . '<br/>';
            }
        }

        restore_error_handler();

        return $result;
    }

}
add_action('aeidn_update_price_event', 'aeidn_update_price_proc');
