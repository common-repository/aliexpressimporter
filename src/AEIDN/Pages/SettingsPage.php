<?php

if (!class_exists('AEIDN_SettingsPage')) {

    class AEIDN_SettingsPage
    {
        public function render()
        {
            $activePage = 'settings';
            include AEIDN_ROOT_PATH . '/layout/toolbar.php';

            do_action('aeidn_before_settings_page');

            if (isset($_POST['setting_form'])) {
                $current_api_module = (isset($_POST['module']) && $_POST['module']) ? sanitize_text_field($_POST['module']) : '';

                if ($current_api_module === 'common') {
                    update_option('aeidn_currency_conversion_factor', isset($_POST['aeidn_currency_conversion_factor']) ? (float)$_POST['aeidn_currency_conversion_factor'] : 1);
                    update_option('aeidn_per_page', isset($_POST['aeidn_per_page']) ? $_POST['aeidn_per_page'] : 1);
                    update_option('aeidn_default_type', isset($_POST['aeidn_default_type']) ? (int)$_POST['aeidn_default_type'] : 1);
                    update_option('aeidn_import_attributes', isset($_POST['aeidn_import_attributes']));

                    update_option('aeidn_remove_link_from_desc', isset($_POST['aeidn_remove_link_from_desc']));
                    update_option('aeidn_remove_img_from_desc', isset($_POST['aeidn_remove_img_from_desc']));

                    update_option('aeidn_import_product_images_limit', isset($_POST['aeidn_import_product_images_limit']) ? sanitize_text_field($_POST['aeidn_import_product_images_limit']) : '');

                    update_option('aeidn_min_product_quantity', isset($_POST['aeidn_min_product_quantity']) ? (int)$_POST['aeidn_min_product_quantity'] : 5);
                    update_option('aeidn_max_product_quantity', isset($_POST['aeidn_max_product_quantity']) ? (int)$_POST['aeidn_max_product_quantity'] : 10);

                    update_option('aeidn_use_proxy', isset($_POST['aeidn_use_proxy']));
                    update_option('aeidn_proxies_list', isset($_POST['aeidn_proxies_list']) ? sanitize_text_field($_POST['aeidn_proxies_list']) : '');

                    if (isset($_POST['aeidn_default_status'])) {
                        update_option('aeidn_default_status', (int)$_POST['aeidn_default_status']);
                    }


                    do_action('aeidn_save_common_settings', $_POST);
                } else {
                    $api_account = aeidn_get_account($current_api_module);
                    if ($api_account) {
                        $api_account->save(filter_input_array(INPUT_POST));
                    }
                    $api = aeidn_get_api($current_api_module);
                    if ($api) {
                        $api->saveSetting(filter_input_array(INPUT_POST));
                        do_action('aeidn_save_module_settings', $api, filter_input_array(INPUT_POST));
                    }
                }
            } else if (isset($_POST['shedule_settings'])) {
                $postData = filter_input_array(INPUT_POST);
                if (array_key_exists('shedule_settings', $postData)) {
                    update_option('aeidn_price_auto_update', isset($postData['aeidn_price_auto_update']));
                }
                update_option('aeidn_regular_price_auto_update', isset($postData['aeidn_regular_price_auto_update']));

                if (isset($postData['aeidn_not_available_product_status'])) {
                    update_option('aeidn_not_available_product_status', sanitize_text_field($postData['aeidn_not_available_product_status']));
                } else {
                    update_option('aeidn_not_available_product_status', 'trash');
                }

                if (isset($postData['aeidn_price_auto_update_period'])) {
                    update_option('aeidn_price_auto_update_period', sanitize_text_field($postData['aeidn_price_auto_update_period']));
                }

                if (isset($postData['aeidn_update_per_schedule'])) {
                    update_option('aeidn_update_per_schedule', (int)$postData['aeidn_update_per_schedule']);
                } else {
                    update_option('aeidn_update_per_schedule', 20);
                }

                $price_auto_update = get_option('aeidn_price_auto_update', false);
                if ($price_auto_update) {
                    wp_schedule_event(
                        time(),
                        get_option('aeidn_price_auto_update_period', 'daily'),
                        'aeidn_update_price_event'
                    );
                } else {
                    wp_clear_scheduled_hook('aeidn_update_price_event');
                }
                do_action('aeidn_save_common_settings', $_POST);
            } elseif (isset($_POST['language_settings'])) {
                update_option('aeidn_tr_aliexpress_language', sanitize_text_field($_POST['aeidn_tr_aliexpress_language']));

                update_option('aeidn_tr_aliexpress_bing_secret', sanitize_text_field($_POST['aeidn_tr_aliexpress_bing_secret']));

                update_option('aeidn_tr_aliexpress_bing_client_id', sanitize_text_field($_POST['aeidn_tr_aliexpress_bing_client_id']));
            }


            include AEIDN_ROOT_PATH . '/layout/settings.php';
        }
    }
}
