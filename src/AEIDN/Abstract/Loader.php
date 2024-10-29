<?php
/**
 *
 */
if (!class_exists('AEIDN_AbstractLoader')) {

    /**
     * Class AEIDN_AbstractLoader
     */
    abstract class AEIDN_AbstractLoader
    {

        /**
         * @var AEIDN_AbstractAccount
         */
        public $account;

        /**
         * @var AEIDN_AbstractConfigurator $api
         */
        public $api;

        /**
         * AEIDN_AbstractLoader constructor.
         * @param AEIDN_AbstractConfigurator $api
         */
        public function __construct($api)
        {
            $this->api = $api;
            $this->account = aeidn_get_account($api->getType());
        }

        public function prepareFilter($filter)
        {
            return $filter;
        }

        public function loadListProc($filter, $page = 1)
        {
            $result = $this->loadList($filter, $page);
            /**
             * @var AEIDN_Goods $item
             */
            foreach ($result['items'] as $key => $item) {
                // update user price by formula
                $formulas = AEIDN_PriceFormula::getGoodsFormula($item);
                if ($formulas) {
                    $item->user_price = AEIDN_PriceFormula::applyFormula($item->user_price, $formulas[0]);
                    $item->saveField('user_price', sprintf('%01.2f', $item->user_price));

                    $item = AEIDN_PriceFormula::calcRegularPrice($item, $formulas[0]);
                    $item->save('API');
                    $item->saveField('user_regular_price', sprintf('%01.2f', $item->user_regular_price));
                }
            }

            // apply some filters for goods list
            $result['items'] = apply_filters('aeidn_load_list_item_proc', $result['items'], $filter);

            return $result;
        }

        /**
         * @param AEIDN_Goods $goods
         * @param array $params
         * @return array
         */
        public function loadDetailProc(&$goods, $params = array())
        {
            $result = $this->loadDetail($goods, $params);
            if ($result['state'] === 'ok') {
                /** @noinspection ReferenceMismatchInspection */
                $result['goods'] = apply_filters('aeidn_get_detail_proc', $goods, $params);
                /**
                 * @var AEIDN_Goods $resultGoods
                 */
                $resultGoods = $result['goods'];
                $goods = $resultGoods;
            }
            return $result;
        }

        public function getDetailProc($productId, $params = array())
        {

            $result = $this->getDetail($productId, $params);

            if ($result['state'] === 'ok') {
                $goods = $result['goods'];

                // get category id
                if (isset($params['wc_product_id']) && $params['wc_product_id']) {
                    $cats = wp_get_object_terms($params['wc_product_id'], 'product_cat');
                    if ($cats && !is_wp_error($cats)) {
                        $goods->link_category_id = $cats[0]->term_id;
                    }
                }

                // update user price by formula
                $formulas = AEIDN_PriceFormula::getGoodsFormula($goods);
                if ($formulas) {
                    $goods->user_price = AEIDN_PriceFormula::applyFormula($goods->user_price, $formulas[0]);
                    $goods = AEIDN_PriceFormula::calcRegularPrice($goods, $formulas[0]);
                }

                $result['goods'] = apply_filters('aeidn_get_detail_proc', $goods, $params);
            }
            return $result;
        }

        abstract public function loadList($filter, $page = 1);

        /**
         * @param AEIDN_Goods $goods
         * @param array $params
         * @return mixed
         */
        abstract public function loadDetail(&$goods, $params = array());

        abstract public function getDetail($productId, $params = array());

        abstract public function checkAvailability($goods);

        public function hasAccount()
        {
            return ($this->account !== null && $this->account->isLoad());
        }
    }
}
