<?php

use Dnolbon\Wordpress\WordpressTranslates;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

if (!defined('AEIDN_DB_VERSION')) {
    define('AEIDN_DB_VERSION', 20);
}

if (!defined('AEIDN_DEACTIVATE_IF_WOOCOMERCE_NOT_FOUND')) {
    define('AEIDN_DEACTIVATE_IF_WOOCOMERCE_NOT_FOUND', false);
}


if (!function_exists('aeidn_check_db_update')) {

    function aeidn_check_db_update()
    {
        if (get_option('aeidn_db_version', 0) < AEIDN_DB_VERSION) {
            aeidn_uninstall();
            aeidn_install();
            update_option('aeidn_db_version', AEIDN_DB_VERSION);
        }
    }

}

if (!function_exists('aeidn_install')) {

    function aeidn_install()
    {
        add_option('aeidn_default_type', 'external', '', 'no');
        add_option('aeidn_default_status', 'publish', '', 'no');
        add_option('aeidn_price_auto_update', false, '', 'no');

        add_option('aeidn_regular_price_auto_update', false, '', 'no');

        add_option('aeidn_price_auto_update_period', 'daily', '', 'no');
        add_option('aeidn_currency_conversion_factor', '1', '', 'no');
        add_option('aeidn_not_available_product_status', 'trash', '', 'no');
        add_option('aeidn_remove_link_from_desc', false, '', 'no');
        add_option('aeidn_remove_img_from_desc', false, '', 'no');
        add_option('aeidn_update_per_schedule', 20, '', 'no');
        add_option('aeidn_import_product_images_limit', '', '', 'no');
        add_option('aeidn_min_product_quantity', 5, '', 'no');
        add_option('aeidn_max_product_quantity', 10, '', 'no');
        add_option('aeidn_use_proxy', false, '', 'no');
        add_option('aeidn_proxies_list', '', '', 'no');

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
        wp_schedule_event(time(), 'hourly', 'aeidn_schedule_post_event');

        aeidn_install_db();

        /**
         * @var $api AEIDN_AbstractConfigurator
         */
        foreach (aeidn_get_api_list() as $api) {
            $api->install();
        }

        do_action('aeidn_install_action');

        $wordpressTranslates = new WordpressTranslates();
        $wordpressTranslates->install();


    }

}

if (!function_exists('aeidn_install_db')) {

    function aeidn_install_db()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $charset_collate = '';
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $table_name = $wpdb->prefix . AEIDN_TABLE_GOODS;
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            '`type` VARCHAR(50) NOT NULL,' .
            '`external_id` VARCHAR(50) NOT NULL,' .
            '`variation_id` VARCHAR(50) NOT NULL,' .
            '`image` VARCHAR(1024) NULL DEFAULT NULL,' .
            '`detail_url` VARCHAR(1024) NULL DEFAULT NULL,' .
            "`seller_url` VARCHAR(1024) NULL DEFAULT NULL," .
            "`photos` TEXT NULL," .
            "`title` VARCHAR(1024) NULL DEFAULT NULL," .
            "`subtitle` VARCHAR(1024) NULL DEFAULT NULL," .
            "`description` MEDIUMTEXT NULL," .
            "`keywords` VARCHAR(1024) NULL DEFAULT NULL," .
            "`price` VARCHAR(50) NULL DEFAULT NULL," .
            "`regular_price` VARCHAR(50) NULL DEFAULT NULL," .
            "`curr` VARCHAR(50) NULL DEFAULT NULL," .
            "`category_id` INT NULL DEFAULT NULL," .
            "`category_name` VARCHAR(1024) NULL DEFAULT NULL," .
            "`link_category_id` INT NULL DEFAULT NULL," .
            "`additional_meta` TEXT NULL," .
            "`user_image` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_photos` TEXT NULL," .
            "`user_title` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_subtitle` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_description` MEDIUMTEXT NULL," .
            "`user_keywords` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_price` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_regular_price` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_schedule_time` DATETIME NULL DEFAULT NULL," .
            "PRIMARY KEY (`type`, `external_id`, `variation_id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);

        $table_name = $wpdb->prefix . AEIDN_TABLE_GOODS_ARCHIVE;
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            '`type` VARCHAR(50) NOT NULL,' .
            '`external_id` VARCHAR(50) NOT NULL,' .
            '`variation_id` VARCHAR(50) NOT NULL,' .
            '`image` VARCHAR(1024) NULL DEFAULT NULL,' .
            '`detail_url` VARCHAR(1024) NULL DEFAULT NULL,' .
            "`seller_url` VARCHAR(1024) NULL DEFAULT NULL," .
            "`photos` TEXT NULL," .
            "`title` VARCHAR(1024) NULL DEFAULT NULL," .
            "`subtitle` VARCHAR(1024) NULL DEFAULT NULL," .
            "`description` MEDIUMTEXT NULL," .
            "`keywords` VARCHAR(1024) NULL DEFAULT NULL," .
            "`price` VARCHAR(50) NULL DEFAULT NULL," .
            "`regular_price` VARCHAR(50) NULL DEFAULT NULL," .
            "`curr` VARCHAR(50) NULL DEFAULT NULL," .
            "`category_id` INT NULL DEFAULT NULL," .
            "`category_name` VARCHAR(1024) NULL DEFAULT NULL," .
            "`link_category_id` INT NULL DEFAULT NULL," .
            "`additional_meta` TEXT NULL," .
            "`user_image` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_photos` TEXT NULL," .
            "`user_title` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_subtitle` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_description` MEDIUMTEXT NULL," .
            "`user_keywords` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_price` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_regular_price` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_schedule_time` DATETIME NULL DEFAULT NULL," .
            "PRIMARY KEY (`type`, `external_id`, `variation_id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);


        $table_name = $wpdb->prefix . AEIDN_TABLE_ACCOUNT;
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`name` VARCHAR(1024) NOT NULL," .
            "`data` text DEFAULT NULL," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);

        $table_name = $wpdb->prefix . AEIDN_TABLE_PRICE_FORMULA;
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`pos` INT(20) NOT NULL DEFAULT 0," .
            "`formula` TEXT NOT NULL," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);

        $table_name = $wpdb->prefix . AEIDN_TABLE_LOG;
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`text` VARCHAR(1024) NULL DEFAULT NULL," .
            "`type` VARCHAR(50) NOT NULL," .
            "`module` VARCHAR(50) NOT NULL," .
            "`time` DATETIME NULL DEFAULT NULL," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);

        $table_name = $wpdb->prefix . AEIDN_TABLE_BLACKLIST;
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`external_id` varchar(50) NOT NULL," .
            "`source` VARCHAR(50) NOT NULL," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);


        $table_name = $wpdb->prefix . AEIDN_TABLE_STATS;
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`product_id` varchar(50) NOT NULL," .
            "`date` DATE NOT NULL," .
            "`quantity` INT (11) NOT NULL DEFAULT 0," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);

    }

}

if (!function_exists('aeidn_uninstall')) {

    function aeidn_uninstall()
    {
        delete_option('aeidn_default_type');
        delete_option('aeidn_default_status');
        delete_option('aeidn_price_auto_update');

        delete_option('aeidn_regular_price_auto_update');

        delete_option('aeidn_price_auto_update_period');
        delete_option('aeidn_currency_conversion_factor');
        delete_option('aeidn_not_available_product_status');
        delete_option('aeidn_remove_link_from_desc');
        delete_option('aeidn_remove_img_from_desc');
        delete_option('aeidn_update_per_schedule');
        delete_option('aeidn_import_product_images_limit');
        delete_option('aeidn_min_product_quantity');
        delete_option('aeidn_max_product_quantity');
        delete_option('aeidn_use_proxy');
        delete_option('aeidn_proxies_list');

        wp_clear_scheduled_hook('aeidn_schedule_post_event');
        wp_clear_scheduled_hook('aeidn_update_price_event');

        aeidn_uninstall_db();

        /**
         * @var $api AEIDN_AbstractConfigurator
         */
        foreach (aeidn_get_api_list() as $api) {
            $api->uninstall();
        }

        do_action('aeidn_uninstall_action');

        $wordpressTranslates = new WordpressTranslates();
        $wordpressTranslates->uninstall();
    }

}

if (!function_exists('aeidn_uninstall_db')) {

    function aeidn_uninstall_db()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . AEIDN_TABLE_GOODS . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . AEIDN_TABLE_GOODS_ARCHIVE . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . AEIDN_TABLE_ACCOUNT . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . AEIDN_TABLE_PRICE_FORMULA . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . AEIDN_TABLE_LOG . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . AEIDN_TABLE_BLACKLIST . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . AEIDN_TABLE_STATS . ';';
        $wpdb->query($sql);

        /**
         * @var $api AEIDN_AbstractConfigurator
         */
        foreach (aeidn_get_api_list() as $api) {
            $api->uninstall();
        }
    }

}