<?php
/*
  Plugin Name: AliExpressImporter
  Description: This plugin allows you to import the products directly from AliExpress in your Wordpress WooCommerce store and earn a commission!
  Version: 1.0.1
  Author: CR1000Team
  License: GPLv2+
  Author URI: http://cr1000team.com
 */
use Dnolbon\Aeidn\Pages\BackupRestore;
use Dnolbon\Aeidn\Pages\Dashboard;
use Dnolbon\Aeidn\Pages\Shedule;
use Dnolbon\Aeidn\Pages\Stats;
use Dnolbon\Aeidn\Pages\Status;
use Dnolbon\Aeidn\Pages\Support;
use Dnolbon\Wordpress\Frontend;
use Dnolbon\Wordpress\WordpressMenuFactory;
use Dnolbon\Wordpress\WordpressStats;
use Dnolbon\Wordpress\WordpressTranslates;

if (!defined('AEIDN_PLUGIN_NAME')) {
    define('AEIDN_PLUGIN_NAME', plugin_basename(__FILE__));
}

if (!defined('AEIDN_ROOT_URL')) {
    define('AEIDN_ROOT_URL', plugin_dir_url(__FILE__));
}
if (!defined('AEIDN_ROOT_PATH')) {
    define('AEIDN_ROOT_PATH', plugin_dir_path(__FILE__));
}

if (!defined('AEIDN_FILE_FULLNAME')) {
    define('AEIDN_FILE_FULLNAME', __FILE__);
}
if (!defined('AEIDN_ROOT_MENU_ID')) {
    define('AEIDN_ROOT_MENU_ID', 'aeidn-dashboard');
}

include_once __DIR__ . '/autoload.php';
include_once __DIR__ . '/include.php';
include_once __DIR__ . '/schedule.php';
include_once __DIR__ . '/install.php';
include_once __DIR__ . '/screenoptions.php';

if (!class_exists('AliExpressImporter')) {

    class AliExpressImporter
    {
        /**
         * @var WordpressTranslates $wordpressTranslates
         */
        private $wordpressTranslates;

        /**
         * @var WordpressStats $wordpressStats
         */
        private $wordpressStats;

        public function __construct()
        {
            register_activation_hook(__FILE__, [$this, 'install']);
            register_deactivation_hook(__FILE__, [$this, 'uninstall']);

            if (is_plugin_active(AEIDN_PLUGIN_NAME)) {
                if (!is_plugin_active('woocommerce/woocommerce.php')) {

                    add_action('admin_notices', [$this, 'woocomerceCheckError']);

                    if (AEIDN_DEACTIVATE_IF_WOOCOMERCE_NOT_FOUND) {
                        deactivate_plugins(AEIDN_PLUGIN_NAME);
                        if (isset($_GET['activate'])) {
                            unset($_GET['activate']);
                        }
                    }
                }

                add_action('admin_menu', [$this, 'registerMenu']);
                add_action('admin_enqueue_scripts', [$this, 'registerAssets']);

                add_filter('plugin_action_links_' . AEIDN_PLUGIN_NAME, [$this, 'registerActionLinks']);

                aeidn_check_db_update();

                $this->registerActions();

                $this->wordpressTranslates = new WordpressTranslates();
                $this->wordpressStats = new WordpressStats();


                add_action('admin_init', [$this, 'aeidnActivateRedirect']);
            } else {
                register_activation_hook(__FILE__, [$this, 'aeidnActivateInstall']);
            }
        }

        public function aeidnActivateRedirect()
        {
            if (get_option('aeidn_activate_redirect', false)) {
                delete_option('aeidn_activate_redirect');
                wp_redirect("admin.php?page=aeidn-settings#aliexpress");
                //wp_redirect() does not exit automatically and should almost always be followed by exit.
                exit;
            }
        }

        public function aeidnActivateInstall()
        {
            add_option('aeidn_activate_redirect', true);
        }

        public function registerActions()
        {
            $frontEnd = new Frontend();
            $frontEnd->init();
        }

        /**
         *
         */
        public function woocomerceCheckError()
        {
            $class = 'notice notice-error';
            $message = __(
                'AliExpressImporter notice! Please install the Woocommerce plugin first.',
                'sample-text-domain'
            );
            printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
        }

        /**
         *
         */
        public function registerAssets()
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $plugin_data = get_plugin_data(__FILE__);

            wp_enqueue_style('aeidn-style', plugins_url('assets/css/dnolbon.css', __FILE__), array(), $plugin_data['Version']);

            wp_enqueue_style('aeidn-style', plugins_url('assets/css/style.css', __FILE__), array(), $plugin_data['Version']);
            wp_enqueue_style('aeidn-font-style', plugins_url('assets/css/font-awesome.min.css', __FILE__), array(), $plugin_data['Version']);
            wp_enqueue_style('aeidn-dtp-style', plugins_url('assets/js/datetimepicker/jquery.datetimepicker.css', __FILE__), array(), $plugin_data['Version']);
            wp_enqueue_style('aeidn-lighttabs-style', plugins_url('assets/js/lighttabs/lighttabs.css', __FILE__), array(), $plugin_data['Version']);

            wp_enqueue_script('aeidn-script', plugins_url('assets/js/script.js', __FILE__), array(), $plugin_data['Version']);
            wp_enqueue_script('aeidn-dtp-script', plugins_url('assets/js/datetimepicker/jquery.datetimepicker.js', __FILE__), array('jquery'), $plugin_data['Version']);
            wp_enqueue_script('aeidn-lighttabs-script', plugins_url('assets/js/lighttabs/lighttabs.js', __FILE__), array('jquery'), $plugin_data['Version']);

            wp_enqueue_script(
                'aeidn-columns-script',
                plugins_url('assets/js/DnolbonColumns.js', __FILE__),
                [],
                $plugin_data['Version']
            );

            wp_localize_script('aeidn-script', 'WPURLS', array('siteurl' => site_url()));
        }

        /**
         *
         */
        public function registerMenu()
        {
            new AEIDN_Goods();
            $api_list = aeidn_get_api_list();

            $menu = WordpressMenuFactory::addMenu(
                AEIDN_NAME,
                'manage_options',
                'aeidn',
                [
                    'icon' => 'small_logo.png',
                    'function' => [new Dashboard(), 'render']
                ]
            );
            /**
             * @var AEIDN_AbstractConfigurator $api
             */
            foreach ($api_list as $api) {
                if ($api->isInstaled()) {
                    if ($api->getConfigValues('menu_title')) {
                        $title = $api->getConfigValues('menu_title');
                    } else {
                        $title = $api->getType();
                    }

                    $menu->addChild(
                        WordpressMenuFactory::addMenu(
                            $title,
                            'manage_options',
                            'add',
                            ['function' => [new AEIDN_DashboardPage($api->getType()), 'render']]
                        )
                    );
                }
            }

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Shedule',
                    'manage_options',
                    'schedule',
                    ['function' => [new Shedule(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Statistics',
                    'manage_options',
                    'stats',
                    ['function' => [new Stats(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Settings',
                    'manage_options',
                    'settings',
                    ['function' => [new AEIDN_SettingsPage(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Backup / Restore',
                    'manage_options',
                    'backup',
                    ['function' => [new BackupRestore(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Status',
                    'manage_options',
                    'status',
                    ['function' => [new Status(), 'render']]
                )
            );

            $menu->addChild(
                WordpressMenuFactory::addMenu(
                    'Support',
                    'manage_options',
                    'support',
                    ['function' => [new Support(), 'render']]
                )
            );

            $menu->show();

            do_action('aeidn_admin_menu');
        }

        /**
         * @param $links
         * @return array
         */
        public function registerActionLinks($links)
        {
            return array_merge(array('<a href="' . admin_url('admin.php?page=aeidn-settings') . '">' . 'Settings' . '</a>'), $links);
        }

        /**
         *
         */
        public function install()
        {
            aeidn_install();
        }

        /**
         *
         */
        public function uninstall()
        {
            aeidn_uninstall();
        }
    }

}

new AliExpressImporter();
