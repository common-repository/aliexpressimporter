<?php

use Dnolbon\Wordpress\WordpressDb;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if (!class_exists('AEIDN_DashboardPage')) {

    class AEIDN_DashboardPage extends WP_List_Table
    {

        public $type = "";
        /**
         * @var AEIDN_AbstractConfigurator
         */
        public $api;
        /**
         * @var AEIDN_AbstractLoader
         */
        public $loader;

        /**
         * @var array
         */
        public $filter = [];
        public $sites = [];
        public $show_dashboard = true;
        public $link_categories = [];

        public function __construct($type)
        {
            parent::__construct();
            $this->api = aeidn_get_api($type);

            if ($this->api && $this->api->isInstaled()) {
                $this->type = $this->api->getType();
                $this->loader = aeidn_get_loader($this->type);

                add_screen_option('layout_columns', ['default' => 2]);

                wp_enqueue_script('jquery');

                wp_enqueue_script('jquery-ui-datepicker');
                //wp_enqueue_style('jqueryui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css', false, null);

                wp_enqueue_script('jquery-form', array('jquery'), false, true);
            }
        }

        /**
         * @override
         */
        public function render()
        {
            // Initialize api module (hooks, filters and other)
            $this->api->init();

            if (is_plugin_active('woocommerce/woocommerce.php')) {
                do_action('aeidn_befor_dashboard_render', $this->api);

                aeidn_api_enqueue_style($this->api);

                $_SERVER['REQUEST_URI'] = remove_query_arg(array('reset'), $_SERVER['REQUEST_URI']);

                $this->filter = array();
                if (is_array($_GET) && $_GET) {
                    $this->filter = array_merge($this->filter, $_GET);
                    unset($this->filter['page']);
                }

                $this->filter = $this->loader->prepareFilter($this->filter);

                $this->link_categories = AEIDN_Utils::getCategoriesTree();

                $activePage = 'add';
                include AEIDN_ROOT_PATH . '/layout/toolbar.php';

                do_action('aeidn_dashboard_render', $this);

                do_action('aeidn_after_dashboard_render', $this);
            }
        }

        /**
         * @override
         * @return mixed|void
         */
        public function get_columns()
        {
            $columns = [
                'cb' => '<input type="checkbox" />',
                'image' => '',
                'info' => 'Information',
                'ship_to_locations' => 'Ship to',
                'condition' => 'Condition',
                'price' => 'Source Price',
                'user_price' => 'Posted Price',
                'ship' => 'Shipment Charges',
                'curr' => 'Currency'
            ];
            return apply_filters('aeidn_get_dashboard_columns', $columns, $this->api);
        }

        /**
         * @override
         * @return mixed|void
         */
        public function get_sortable_columns()
        {
            $sortableColumns = [];
            return apply_filters('aeidn_get_dashboard_sortable_columns', $sortableColumns);
        }

        /**
         * @param object $item
         * @return string
         * @override
         */
        public function column_cb($item)
        {
            return sprintf('<input type="checkbox" class="gi_ckb" name="gi[]" value="%s" ' . ($item->post_id ? 'disabled="disabled"' : '') . '/>', $item->getId('#'));
        }

        /**
         * @param AEIDN_Goods $item
         * @param string $column_name
         * @return mixed|void
         * @override
         */
        public function column_default($item, $column_name)
        {
            switch ($column_name) {
                case 'image':
                    $result_data = AEIDN_DashboardPage::putImageEdit($item);
                    break;
                case 'info':
                    $actions = array();
                    $actions['id'] = '<a href="' . $item->detail_url . '" target="_blank" class="link_to_source product_url">Product page</a>' . "<span class='seller_url_block' " . ($item->seller_url ? "" : "style='display:none'") . "> | <a href='" . $item->seller_url . "' target='_blank' class='seller_url'>Seller page</a></span>";
                    $actions['load_more_detail'] = $item->needLoadMoreDetail() ? '<a href="#moredetails" class="moredetails">Load more details</a>' : '<i>Details loaded</i>';
                    $actions['import'] = $item->post_id ? '<i>Posted</i>' : '<a href="#import_" class="post_import">Post to Woocommerce</a>';
                    if (!$item->post_id) {
                        $actions['schedule_import'] = $item->user_schedule_time ? ("<i>Will be post on " . date("m/d/Y H:i", strtotime($item->user_schedule_time))) . "</i>" : '<input type="text" class="schedule_post_date" style="visibility:hidden;width:0px;padding:0;margin:0;"/><a href="#scheduleimport" class="schedule_post_import">Schedule Post</a>';
                    }

                    $cat_name = "";
                    foreach ($this->link_categories as $c) {
                        if ($c['term_id'] === $item->link_category_id) {
                            $cat_name = $c['name'];
                            break;
                        }
                    }

                    $result_data = AEIDN_DashboardPage::putField($item, "title", true, "edit", "Title", "") .
                        AEIDN_DashboardPage::putField($item, 'subtitle', true, "edit", "Subtitle", "subtitle-block") .
                        AEIDN_DashboardPage::putField($item, 'keywords', true, "edit", "Keywords", "subtitle-block") .
                        AEIDN_DashboardPage::putDescriptionEdit($item) .
                        ($cat_name ? "<div>Link to category: $cat_name</div>" : "") .
                        $this->row_actions($actions);
                    break;
                case 'condition':
                    $result_data = isset($item->additional_meta['condition']) ? AEIDN_Goods::normalized($item->additional_meta['condition']) : "";
                    break;
                case 'ship_to_locations':
                    $result_data = isset($item->additional_meta['ship_to_locations']) ? AEIDN_Goods::normalized($item->additional_meta['ship_to_locations']) : "";
                    break;
                case 'ship':
                    $result_data = (isset($item->additional_meta['ship']) && $item->additional_meta['ship']) ? AEIDN_Goods::getNormalizePrice($item->additional_meta['ship']) : "";
                    break;
                default:
                    $result_data = AEIDN_DashboardPage::putField($item, $column_name, false);
                    break;
            }

            return apply_filters('aeidn_dashboard_column_default', $result_data, $item, $column_name);
        }

        /**
         * @override
         */
        public function no_items()
        {
            _e('Products no found.');
        }

        /**
         * @return array
         * @override
         */
        public function get_bulk_actions()
        {
            $actions = array(
                'import' => 'Post to Woocommerce (publish)',
                'import_draft' => 'Post to Woocommerce (draft)',
                'blacklist' => 'Blacklist'
            );
            return $actions;
        }

        /**
         * @param object $item
         * @override
         */
        public function single_row($item)
        {
            echo '<tr id="' . $item->getId() . '">';
            $this->single_row_columns($item);
            echo '</tr>';
        }

        private function processBulkAction()
        {
            $result_cnt = 0;
            set_error_handler("aeidn_error_handler");
            if ((
                    (isset($_GET['action']) && $_GET['action'] === "import_draft") ||
                    (isset($_GET['action2']) && $_GET['action2'] === "import_draft") ||
                    (isset($_GET['action']) && $_GET['action'] === "import") ||
                    (isset($_GET['action2']) && $_GET['action2'] === "import")
                ) &&
                isset($_GET['gi']) && is_array($_GET['gi'])
            ) {
                $safeGoods = filter_input(INPUT_GET, 'gi');
                foreach ($safeGoods as $gi) {
                    $goods = new AEIDN_Goods($gi);
                    if ($goods->load() && !$goods->post_id && class_exists('AEIDN_WooCommerce')) {

                        $importStatus = $_GET['action'] === 'import' ? 'publish' : 'draft';

                        $res = AEIDN_WooCommerce::addPost($goods, ['import_status' => $importStatus]);
                        if ($res["state"] !== "error") {
                            $result_cnt++;
                        }

                        if ($res["message"]) {
                            add_settings_error(
                                'aeidn_goods_posted',
                                esc_attr('settings_updated'),
                                $res["message"],
                                $res["state"] !== "ok" ? 'error' : 'updated'
                            );
                        }
                    }
                }
            }
            restore_error_handler();
            return $result_cnt;
        }

        /**
         * @override
         */
        public function prepare_items()
        {

            if ($this->loader) {

                if (!$this->loader->hasAccount()) {
                    add_settings_error('aeidn_dashboard_error', esc_attr('settings_updated'), 'Account not found. You need configure account on setting page', 'error');
                    $this->show_dashboard = false;
                } else if (!is_plugin_active('woocommerce/woocommerce.php')) {
                    add_settings_error('aeidn_dashboard_error', esc_attr('settings_updated'), 'Please install the Woocommerce plugin first.', 'error');
                    $this->show_dashboard = false;
                } else {
                    $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
                    $current_page = $this->get_pagenum();

                    $result_cnt = $this->processBulkAction();
                    if ($result_cnt) {
                        add_settings_error('aeidn_goods_posted', esc_attr('settings_updated'), "$result_cnt products have been loaded to WooCommerce", 'updated');
                    }
                    settings_errors('aeidn_goods_posted');

                    if (isset($this->filter['reset']) && $this->filter['reset']) {
                        AEIDN_Goods::clearList();
                    }

                    $data = $this->loader->loadListProc($this->filter, $current_page);

                    if ($data["error"]) {
                        add_settings_error('aeidn_goods_list', esc_attr('settings_updated'), $data['error'], 'error');
                    }

                    $this->set_pagination_args(array('total_items' => (int)$data['total'], 'per_page' => (int)$data['per_page']));
                    $this->items = $data["items"];

                    // process local sort by columns
                    if (isset($_GET['orderby']) && function_exists("aeidn_sort_by_" . $_GET['orderby'])) {
                        uasort($this->items, "aeidn_sort_by_" . $_GET['orderby']);
                        if (isset($_GET['order']) && $_GET['order'] === "desc") {
                            $this->items = array_reverse($this->items);
                        }
                    }

                    $db = WordpressDb::getInstance()->getDb();
                    $db->query('delete from ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . ' where external_id in (
                    select external_id from ' . $db->prefix . AEIDN_TABLE_GOODS . '
                    )');
                    $db->query('insert into ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . '
                    select * from ' . $db->prefix . AEIDN_TABLE_GOODS . '
                    ');
                }
            }
        }

        /**
         * @param AEIDN_Goods $item
         * @param $field
         * @param $edit
         * @param string $edit_text
         * @param string $lable_text
         * @param string $block_class
         * @return string
         */
        static public function putField($item, $field, $edit, $edit_text = "edit", $lable_text = "", $block_class = "")
        {
            $value = $item->getProp($field, $edit);

            $loaded = $value !== "#needload#";

            $out = '';
            if ($value !== "#notuse#") {
                $out .= '<div class="block_field ' . $block_class . ($edit ? ' edit' : '') . '">';
                $out .= '<input type="hidden" class="field_code" value="' . $field . '"/>';
                if ($lable_text) {
                    $out .= '<label class="field_label">' . $lable_text . ': </label>';
                }
                $out .= '<span class="field_text">' . ($loaded ? $value : '<font style="color:red;">Need to load more details</font>') . '</span>';
                if ($edit) {
                    $out .= '<input type="text" class="field_edit" value="" style="width:100%;display:none"/>';
                    $out .= '<input type="button" class="save_btn button" value="Save" style="display:none"/> ';
                    $out .= '<input type="button" class="cancel_btn button" value="Cancel" style="display:none"/>';
                    $out .= ' <a href="#edit" class="edit_btn" ' . ($loaded ? '' : 'style="display:none;"') . '>[' . $edit_text . ']</a>';
                }
                $out .= '</div>';
            }

            return $out;
        }

        /**
         * @param AEIDN_Goods $item
         * @param bool $content_only
         * @return string
         */
        static public function putImageEdit($item, $content_only = false)
        {
            $out = "";
            if (!$content_only) {
                $out .= sprintf('<a href="#TB_inline?width=320&height=450&inlineId=select-image-dlg-%1$s" class="thickbox select_image"><img src="%2$s"/></a>', $item->getId('-'), $item->getProp('image'));
                $out .= '<a href="#TB_inline?width=320&height=150&inlineId=upload_image_dlg" class="thickbox upload_image">[upload image]</a>';
                $out .= '<div id="select-image-dlg-' . $item->getId('-') . '" style="display:none;">';
            }
            if ($item->photos === "#needload#") {
                $out .= '<h3><font style="color:red;">Photos not load yet! Click "load more details"</font></h3>';
            }
            $out .= '<h3>Click on an image to select it</h3>';
            $out .= '<input type="hidden" class="item_id" value="' . $item->getId() . '"/>';
            $cur_image = $item->user_image;

            $photos = $item->getAllPhotos();
            foreach ($photos as $photo) {
                $out .= sprintf('<div class="aeidn_select_image"><img class="' . ($cur_image === $photo ? "sel" : "") . '" src="%1$s"/></div>', $photo);
            }

            if (!$content_only) {
                $out .= '</div>';
            }
            return $out;
        }

        static public function putDescriptionEdit($content_only = false)
        {
            $out = '';
            if (!$content_only) {
                $out .= 'Description: <a href="#TB_inline?width=800&height=600&inlineId=edit_desc_dlg" class="thickbox edit_desc_action">[edit description]</a>';
            }

            return $out;
        }

    }
}
