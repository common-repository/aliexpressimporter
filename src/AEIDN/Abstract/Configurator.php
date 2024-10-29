<?php
/**
 *
 */
if (!class_exists('AEIDN_AbstractConfigurator')) {
    /**
     * Class AEIDN_AbstractConfigurator
     */
    abstract class AEIDN_AbstractConfigurator
    {

        private $filter_config = array();

        public function __construct()
        {
            aeidn_add_api($this);
            $this->checkApiConfigure();

            add_action('aeidn_print_api_setting_page', [$this, 'printApiAccountSettingPage'], 10, 1);

            add_action('aeidn_print_api_setting_page', [$this, 'printApiSettingPage'], 11, 1);
        }

        public final function init()
        {
            add_filter('aeidn_get_dashboard_columns', [$this, 'modifyColumns'], 10, 2);
            add_filter('aeidn_get_dashboard_sortable_columns', [$this, 'modifySortableColumns'], 10, 1);
            add_filter('aeidn_dashboard_column_default', [$this, 'modifyColumnData'], 10, 3);

            add_action('aeidn_befor_dashboard_render', [$this, 'printPageHeader'], 10, 1);


            add_action('aeidn_dashboard_render', [$this, 'printPage'], 10, 1);

            add_action('aeidn_after_dashboard_render', [$this, 'printPageFooter'], 10, 1);

            add_action('aeidn_print_api_setting_page', [$this, 'printApiSettingPage'], 10, 1);

            $this->initModule();

            $this->initFilters();

            $this->configureFilters();

            do_action('aeidn_init_custom_filter', $this);
        }

        // should return config array!!!
        abstract public function getConfig();

        /**
         * @return string
         */
        public final function getType()
        {
            return $this->getConfigValues('type');
        }

        /**
         * @param $key
         * @return string
         */
        public final function getConfigValues($key)
        {
            $config = $this->getConfig();
            return isset($config[$key]) ? $config[$key] : '';
        }

        public function isInstaled()
        {
            $config = $this->getConfig();
            return (is_array($config) && count($config) && isset($config['instaled']) && $config['instaled']);
        }

        public function printPage($dashboard)
        {
            $dashboard_view = aeidn_get_api_path($this) . 'layout/dashboard.php';
            if (file_exists($dashboard_view)) {
                include_once $dashboard_view;
            } else {
                include_once AEIDN_ROOT_PATH . '/layout/dashboard.php';
            }
        }

        /**
         * @param AEIDN_AbstractConfigurator $api
         */
        public function printApiAccountSettingPage($api)
        {
            if ($api->getType() === $this->getType()) {
                $api_account = aeidn_get_account($api->getType());
                if ($api_account) {
                    $api_account->printForm();
                }
            }
        }

        /**
         * @param AEIDN_AbstractConfigurator $api
         */
        public function printApiSettingPage($api)
        {
            if ($api->getType() === $this->getType()) {
                $setting_view = aeidn_get_api_path($this) . 'layout/settings.php';
                if (file_exists($setting_view)) {
                    include_once $setting_view;
                }
            }
        }

        public function printPageHeader()
        {

        }

        public function printPageFooter()
        {
//            echo '<div class="aeidn_tr_module_version">Module version: ' . $this->getConfigValues('version') . '</div>';
        }

        public function install()
        {

        }

        public function uninstall()
        {

        }

        // configure common filters
        private final function initFilters()
        {
            $this->addFilter('aeidn_productId', 'aeidn_productId', 10, [
                'type' => 'edit',
                'label' => 'ProductId',
                'dop_row' => 'OR configure search filter',
                'placeholder' => 'Please enter your productId'
            ]);
            $this->addFilter('aeidn_query', 'aeidn_query', 20, [
                'type' => 'edit',
                'label' => 'Keywords',
                'placeholder' => 'Please enter your Keywords'
            ]);
            $this->addFilter('price', ['aeidn_min_price', 'aeidn_max_price'], 30, [
                'type' => 'edit',
                'label' => 'Price',
                'aeidn_min_price' => ['label' => "from $", 'default' => '0.00'],
                'aeidn_max_price' => ['label' => " to $", 'default' => '0.00']
            ]);
        }

        // configure custom api filters
        protected function configureFilters()
        {

        }

        public final function addFilter($id, $name, $order = 1000, $config = array())
        {
            $this->filter_config[$id] = array('id' => $id, 'name' => $name, 'config' => $config, 'order' => $order);
        }

        public final function removeFilter($id)
        {
            unset($this->filter_config[$id]);
        }

        public final function getFilters()
        {
            $result = array();
            foreach ($this->filter_config as $id => $filter) {
                $result[$id] = $filter;
                if (isset($filter['config']['data_source']) && $filter['config']['data_source']) {
                    if (is_array($filter['config']['data_source'])) {
                        $result[$id]['config']['data_source'] = $filter['config']['data_source'][0]->{$filter['config']['data_source'][1]}();
                    } else {
                        $result[$id]['config']['data_source'] = ${$filter['config']['data_source']}();
                    }
                }
            }
            if (!function_exists('AEIDN_AbstractConfigurator_cmp')) {

                function AEIDN_AbstractConfigurator_cmp($a, $b)
                {
                    if ($a['order'] === $b['order']) {
                        return 0;
                    }
                    return ($a['order'] < $b['order']) ? -1 : 1;
                }

            }
            uasort($result, 'AEIDN_AbstractConfigurator_cmp');

            return $result;
        }

        // configure custom api initialization
        protected function initModule()
        {

        }

        public function modifyColumns($columns)
        {
            return $columns;
        }

        public function modifySortableColumns($columns)
        {
            $sortable_columns = $columns;
            if (is_array($this->getConfigValues('sort_columns'))) {
                foreach ($this->getConfigValues('sort_columns') as $sc) {
                    $sortable_columns[$sc] = array($sc, false);
                }
            }
            return $sortable_columns;
        }

        abstract public function modifyColumnData($data, $item, $column_name);

        abstract public function saveSetting($data);

        private final function checkApiConfigure()
        {
            $config = $this->getConfig();
            if (!is_array($config)) {
                throw new Exception('AEIDN Error: ' . get_class($this) . ' uncorect API configure! get_config() must return array');
            } else if (!isset($config['type']) || !$config['type']) {
                throw new Exception('AEIDN Error: ' . get_class($this) . ' uncorect API configure! Config array must have not empty "type"');
            } else if ($this->isInstaled()) {
                if (!isset($config['account_class'])) {
                    throw new Exception('AEIDN Error: ' . get_class($this) . ' uncorect API configure! Config array must have correct "account_class"');
                } else if (!isset($config['loader_class'])) {
                    throw new Exception('AEIDN Error: ' . get_class($this) . ' uncorect API configure! Config array must have correct "loader_class"');
                }
            }
        }

    }
}
