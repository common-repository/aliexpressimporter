<?php
/**
 *
 */
if (!class_exists('AEIDN_AliexpressConfigurator')) {

    /**
     * Class AEIDN_AliexpressConfigurator
     */
    class AEIDN_AliexpressConfigurator extends AEIDN_AbstractConfigurator
    {
        public function getConfig()
        {
            return array(
                'version' => '1.0.0',
                'instaled' => true,
                'type' => 'aliexpress',
                'menu_title' => 'Aliexpress',
                'dashboard_title' => 'Aliexpress',
                'account_class' => 'AEIDN_AliexpressAccount',
                'loader_class' => 'AEIDN_AliexpressLoader',
                'sort_columns' => ['price', 'user_price', 'validTime']
            );
        }

        public function saveSetting($data)
        {
            update_option('aeidn_ali_per_page', (int)$data['aeidn_ali_per_page'] > 40 ? 40 : $data['aeidn_ali_per_page']);
            update_option('aeidn_ali_links_to_affiliate', isset($data['aeidn_ali_links_to_affiliate']));
            update_option('aeidn_ali_local_currency', $data['aeidn_ali_local_currency']);
        }

        public function modifyColumns($columns)
        {
            $columns = array('cb' => '<input type="checkbox" />',
                'image' => '', 'info' => 'Information',
                'price' => 'Source Price',
                'user_price' => 'Posted Price',
                'commission' => 'Commission (8%)',
                'curr' => 'Currency',
                'volume' => 'Тotal orders (last 30 days)',
                'rating' => 'Rating',
                'validTime' => 'Valid time');


            return $columns;
        }


        public function modifyColumnData($data, /* @var $item AEIDN_Goods */
                                         $item, $column_name)
        {
            if ($column_name === 'validTime') {
                $data = $item->additional_meta['validTime'];
            }

            if ($column_name === 'commission') {
                $data = $item->additional_meta['commission'];
            }

            if ($column_name === 'volume') {
                $data = $item->additional_meta['volume'];
            }

            if ($column_name === 'rating') {
                $data = $item->additional_meta['rating'];
            }

            if ($column_name === 'info') {
                $data = "<div class='block_field'><label class='field_label'>External ID: </label><span class='field_text'>" . $item->external_id . '</span></div>' . $data;
            }
            return $data;
        }


        protected function configureFilters()
        {
            $this->addFilter('category_id', 'category_id', 21, ['type' => 'select',
                'label' => 'Category',
                'class' => 'category_list',
                'style' => 'width:25em;',
                'data_source' => [$this, 'getCategories']]);
            $this->addFilter('volume', ['volume_from', 'volume_to'], 32, [
                'type' => 'edit',
                'label' => 'Тotal orders (last 30 days)',
                'description' => 'from 1 to 100',
                'volume_from' => ['label' => 'from'],
                'volume_to' => ['label' => ' to']
            ]);

            $this->addFilter('feedback_score', ['min_feedback', 'max_feedback'], 33, ['type' => 'edit',
                'label' => 'Feedback score',
                'min_feedback' => ['label' => 'min', 'default' => '0'],
                'max_feedback' => ['label' => ' max', 'default' => '0']]);

            $this->addFilter('high_quality_items', 'high_quality_items', 34, ['type' => 'checkbox',
                'label' => 'High Quality items',
                'default' => 'yes']);
        }

        protected function getCategories()
        {
            $result = json_decode(file_get_contents(AEIDN_ROOT_PATH . '/data/aliexpress_categories.json'), true);
            $result = $result['categories'];
            array_unshift($result, ['id' => '', 'name' => ' - ', 'level' => 1]);
            return $result;
        }

        public function install()
        {
            add_option('aeidn_ali_per_page', 20, '', 'no');
            add_option('aeidn_ali_links_to_affiliate', true, '', 'no');
            add_option('aeidn_ali_local_currency', '', '', 'no');
        }

        public function uninstall()
        {
            delete_option('aeidn_ali_per_page');
            delete_option('aeidn_ali_links_to_affiliate');
            delete_option('aeidn_ali_local_currency');
        }

    }
}
new AEIDN_AliexpressConfigurator();
