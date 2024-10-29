<?php
/**
 *
 */
if (!class_exists('AEIDN_AliexpressLoader')) {

    class AEIDN_AliexpressLoader extends AEIDN_AbstractLoader
    {

        public function loadList($filter, $page = 1)
        {
            global $wpdb;

            $per_page = get_option('aeidn_ali_per_page', 20);
            $result = array('total' => 0, 'per_page' => $per_page, 'items' => array(), 'error' => '');

            $link_category_id = (isset($filter['link_category_id']) && (int)$filter['link_category_id']) ? (int)$filter['link_category_id'] : 0;

            if ($link_category_id && ((isset($filter['aeidn_productId']) && !empty($filter['aeidn_productId'])) || (isset($filter['aeidn_query']) && !empty($filter['aeidn_query'])) || (isset($filter['category_id']) && (int)$filter['category_id'] !== 0))) {
                $single_product_id = (isset($filter['aeidn_productId']) && $filter['aeidn_productId']) ? $filter['aeidn_productId'] : '';

                $query = isset($filter['aeidn_query']) ? urlencode(utf8_encode($filter['aeidn_query'])) : '';
                $category_id = (isset($filter['category_id']) && $filter['category_id']) ? $filter['category_id'] : '';
                $link_category_id = (isset($filter['link_category_id']) && (int)$filter['link_category_id']) ? (int)$filter['link_category_id'] : 0;

                $priceFrom = (isset($filter['aeidn_min_price']) && !empty($filter['aeidn_min_price']) && (float)$filter['aeidn_min_price'] > 0.009) ? "&originalPriceFrom={$filter['aeidn_min_price']}" : '';
                $priceTo = (isset($filter['aeidn_max_price']) && !empty($filter['aeidn_max_price']) && (float)$filter['aeidn_max_price'] > 0.009) ? "&originalPriceTo={$filter['aeidn_max_price']}" : '';

                $commissionRateFrom = (isset($filter['commission_rate_from']) && !empty($filter['commission_rate_from']) && (float)$filter['commission_rate_from'] > 0.009) ? "&commissionRateFrom={$filter['commission_rate_from']}" : '';
                $commissionRateTo = (isset($filter['commission_rate_to']) && !empty($filter['commission_rate_to']) && (float)$filter['commission_rate_to'] > 0.009) ? "&commissionRateTo={$filter['commission_rate_to']}" : '';

                $volumeFrom = (isset($filter['volume_from']) && !empty($filter['volume_from']) && (int)$filter['volume_from'] > 0) ? "&volumeFrom={$filter['volume_from']}" : '';
                $volumeTo = (isset($filter['volume_to']) && !empty($filter['volume_to']) && (int)$filter['volume_to'] > 0) ? "&volumeTo={$filter['volume_to']}" : '';

                $highQualityItems = isset($filter['high_quality_items']) ? '&highQualityItems=true' : '';

                $feedback_min = (isset($filter['min_feedback']) && (int)$filter['min_feedback'] > 0) ? (int)$filter['min_feedback'] : 0;
                $feedback_max = (isset($filter['max_feedback']) && (int)$filter['max_feedback'] > 0) ? (int)$filter['max_feedback'] : 0;
                if ($feedback_max < $feedback_min) {
                    $feedback_max = 0;
                }

                $startCredit = $feedback_min ? "&startCreditScore={$feedback_min}" : '';
                $endCredit = $feedback_max ? "&endCreditScore={$feedback_max}" : '';

                $localCurrency = strtoupper(get_option('aeidn_ali_local_currency', ''));
                if ($localCurrency) {
                    $localCurrencyReq = "&localCurrency=$localCurrency";
                    $currency_conversion_factor = 1;
                } else {
                    $localCurrencyReq = '';
                    $currency_conversion_factor = (float)get_option('aeidn_currency_conversion_factor', 1);
                }


                // NOT USED in AliExpress
                // $available_to = (isset($filter['available_to']) && $filter['available_to']) ? $filter['available_to'] : "";

                $request_sort = '';
                if (isset($filter['orderby'])) {
                    $request_sort = '&sort=';
                    switch ($filter['orderby']) {
                        case 'price':
                            if ($filter['order'] === 'asc') {
                                $request_sort .= 'orignalPriceUp';
                            } elseif ($filter['order'] === 'desc') {
                                $request_sort .= 'orignalPriceDown';
                            }

                            break;
                        case 'validTime':
                            if ($filter['order'] === 'asc') {
                                $request_sort .= 'validTimeUp';
                            } elseif ($filter['order'] === 'desc') {
                                $request_sort .= 'validTimeDown';
                            }
                            break;
                        default:
                            $request_sort = '';
                    }
                }
                // <---------------------------

                if ($single_product_id) {
                    // search by product id
                    $request_url = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.getPromotionProductDetail/{$this->account->appKey}";
                    $request_param = '?fields=productId,productTitle,productUrl,imageUrl,originalPrice,salePrice,discount,evaluateScore,commission,commissionRate,30daysCommission,volume,packageType,lotNum,validTime,storeName,storeUrl,localPrice';
                    $request_param .= "&productId=$single_product_id";
                    $request_param .= $localCurrencyReq;
                    $request_sort = '';
                } else {
                    // search by query and params
                    $request_url = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.listPromotionProduct/{$this->account->appKey}";
                    $request_param = '?fields=totalResults,productId,productTitle,productUrl,imageUrl,originalPrice,salePrice,discount,evaluateScore,commission,commissionRate,30daysCommission,volume,packageType,lotNum,validTime,localPrice';
                    $request_param .= "&categoryId={$category_id}&pageNo={$page}&keywords={$query}&pageSize={$per_page}";
                    $request_param .= '&language='.get_option('aeidn_tr_aliexpress_language', 'en');
                    $request_param .= $localCurrencyReq . $commissionRateFrom . $commissionRateTo . $volumeFrom . $volumeTo . $priceFrom . $priceTo . $startCredit . $endCredit . $highQualityItems;
                }

                $full_request_url = apply_filters('aeidn_get_localized_url', $request_url . $request_param . $request_sort,
                    array('type' => 'aliexpress_request'));

                $request = aeidn_remote_get($full_request_url);
                //echo $full_request_url."<br/>";
                //echo "<pre>";print_r($request);echo "</pre>";
                //$result["call"] = $request_url . $request_param . $request_sort;

                $error_code = '';

                $items = [];
                if (is_wp_error($request)) {
                    $result['error'] = 'alibaba.com not response!';
                } else {
                    $items = json_decode($request['body'], true);
                    $error_code = isset($items['errorCode']) ? $items['errorCode'] : '';
                    //echo "<pre>";print_r($request);echo "</pre>";
                }

                if ($single_product_id && isset($items['result']) && $items['result']) {
                    $items['result'] = array('products' => array($items['result']));
                }

                //echo "<pre>";print_r($items);echo "</pre>";

                if ($error_code === 20010000 && isset($items['result']['products']) && !empty($items['result']['products'])) {
                    $data = $items['result']['products'];

                    foreach ($data as $item) {
                        //echo "<pre>";print_r($item);echo "</pre>";

                        $count = $wpdb->get_var("SELECT count(*) FROM " . $wpdb->prefix . AEIDN_TABLE_BLACKLIST . " 
                        WHERE external_id='" . $item['productId'] . "'");
                        if ($count > 0) {
                            continue;
                        }

                        $goods = new AEIDN_Goods();
                        $goods->type = 'aliexpress';
                        $goods->external_id = $item['productId'];
                        $goods->load();

                        $goods->link_category_id = $link_category_id;

                        $goods->image = isset($item['imageUrl']) ? $item['imageUrl'] : AEIDN_NO_IMAGE_URL;
                        $goods->detail_url = $item['productUrl'];
                        $goods->additional_meta['detail_url'] = $item['productUrl'];

                        $goods->title = strip_tags($item['productTitle']);
                        $goods->subtitle = '#notuse#';
                        $goods->additional_meta['validTime'] = $item['validTime'];
                        $goods->category_id = 0;


                        if (trim($goods->category_name) === '') {
                            $goods->category_name = '#needload#';
                        }

                        if (trim($goods->keywords) === '') {
                            $goods->keywords = '#needload#';
                        }

                        if (trim($goods->description) === '') {
                            $goods->description = '#needload#';
                        }

                        if (trim($goods->photos) === '') {
                            $goods->photos = '#needload#';
                        }

                        $goods->additional_meta['discount'] = $item['discount'];

                        //	$goods->additional_meta['condition'] = "New";

                        $local_price = $localCurrency ? AEIDN_Goods::getNormalizePrice($item['localPrice']) : AEIDN_Goods::getNormalizePrice($item['salePrice']);
                        $sale_price = AEIDN_Goods::getNormalizePrice($item['salePrice']);
                        $usd_course = round($local_price / $sale_price, 2);

                        $goods->price = round($local_price, 2);

                        $originalPrice = AEIDN_Goods::getNormalizePrice($item['originalPrice']);
                        $goods->regular_price = round($originalPrice * $usd_course, 2);

                        $goods->additional_meta['original_discount'] = 100 - round($sale_price * 100 / $originalPrice);

                        //course
                        //$goods->additional_meta['ship'] = '8%';
                        $commission_rate = 8;
                        $goods->additional_meta['commission'] = round($local_price * ($commission_rate / 100), 2);

                        $goods->additional_meta['volume'] = $item['volume'];
                        $goods->additional_meta['rating'] = $item['evaluateScore'];

                        /* this is for one addon -----> */
                        $goods->additional_meta['regular_price'] = round(AEIDN_Goods::getNormalizePrice($item['originalPrice']) * $currency_conversion_factor, 2);
                        $goods->additional_meta['sale_price'] = round(AEIDN_Goods::getNormalizePrice($item['salePrice']) * $currency_conversion_factor, 2);
                        /* <--------------------------- */
                        if ($localCurrency) {
                            $goods->curr = strtoupper($localCurrency);
                        } else {
                            $goods->curr = $currency_conversion_factor > 1 ? "CUSTOM (*$currency_conversion_factor)" : 'USD (Default)';
                        }

                        $goods->save('API');

                        if (trim((string)$goods->user_price) === '') {
                            $goods->user_price = round($goods->price * $currency_conversion_factor, 2);
                            $goods->saveField('user_price', sprintf('%01.2f', $goods->user_price));

                            $goods->user_regular_price = round($goods->regular_price * $currency_conversion_factor, 2);
                            $goods->saveField('user_regular_price', sprintf('%01.2f', $goods->user_regular_price));
                        }

                        if (trim((string)$goods->user_image) === '') {
                            $goods->saveField('user_image', $goods->image);
                        }
                        $result['items'][] = apply_filters('aeidn_modify_goods_data', $goods, $item, 'aliexpress_load_list');
                    }

                    //if (get_option('aeidn_default_type', 'simple')=="external") {
                    $result['items'] = $this->getAffiliateGoods($result['items']);
                    //}

                    if (isset($items['result']['totalResults'])) {
                        $result['total'] = (int)$items['result']['totalResults'] > 10240 ? 10240 : $items['result']['totalResults'];
                    }
                }
                if ((int)$error_code === 20010000 && empty($items['result']['products'])) {
                    $result['error'] = 'There is no product to display!';
                } elseif ((int)$error_code === 400) {
                    $result['error'] = $items['error_message'];
                } elseif ((int)$error_code === 20030000) {
                    $result['error'] = 'Required parameters';
                } elseif ((int)$error_code === 20030010) {
                    $result['error'] = 'Keyword input parameter error';
                } elseif ((int)$error_code === 20030020) {
                    $result['error'] = 'Category ID input parameter error or formatting errors';
                } elseif ((int)$error_code === 20030030) {
                    $result['error'] = 'Commission rate input parameter error or formatting errors';
                } elseif ((int)$error_code === 20030040) {
                    $result['error'] = 'Unit input parameter error or formatting errors';
                } elseif ((int)$error_code === 20030050) {
                    $result['error'] = '30 days promotion amount input parameter error or formatting errors';
                } elseif ((int)$error_code === 20030060) {
                    $result['error'] = 'Tracking ID input parameter error or limited length';
                } elseif ((int)$error_code === 20030070) {
                    $result['error'] = 'Unauthorized transfer request';
                } elseif ((int)$error_code === 20020000) {
                    $result['error'] = 'System Error';
                } elseif ((int)$error_code === 20030100) {
                    $result['error'] = 'Error! Input parameter Product ID';
                }
            } else {
                if ((isset($filter['aeidn_productId']) && !empty($filter['aeidn_productId'])) || (isset($filter['aeidn_query']) && !empty($filter['aeidn_query'])) || (isset($filter['category_id']) && (int)$filter['category_id'] !== 0)) {
                    $result["error"] = 'Please set "Link to category" field before searching';
                } else {
                    $result["error"] = 'Please enter keywords, product ID, or select an item category from the list.';
                }
            }


            return $result;
        }

        /**
         * @param AEIDN_Goods $goods
         * @param array $params
         * @return array
         */
        public function loadDetail(&$goods, $params = array())
        {
            $tmp_res = $this->getDetail($goods->external_id);

            if ($tmp_res['state'] !== 'ok') {
                return array('state' => 'error', 'message' => $tmp_res['message']);
            }

            $tmp_goods = $tmp_res['goods'];

            /** @noinspection ReferenceMismatchInspection */
            $data = $this->tmpAliProductInfo($goods);
            if ($data['state'] !== 'ok') {
                return array('state' => 'error', 'message' => $data['message']);
            }

            $goods->image = $tmp_goods->image;
            $goods->description = $data['description'];

            $goods->keywords = $data['keywords'];

            $goods->category_id = 0;
            $goods->category_name = "";

            if (is_array($data["attribute"])) {
                $data["attribute"] = apply_filters('aeidn_get_localized_attributes', $data["attribute"], 'aliexpress');
                /*
                foreach ($data["attribute"] as $attr_key => $attr_val){
                    $data["attribute"][$attr_key] = apply_filters('aeidn_get_localized_text',                                   $attr_val, 'aliexpress');
                }*/

            }
            $goods->additional_meta['attribute'] = $data["attribute"];

            $goods->seller_url = $tmp_goods->seller_url;

            //$goods->photos = implode(",", $data['images']);
            try {
                $images = $this->getImages($tmp_goods);
                $goods->photos = implode(",", $images);
            } catch (Exception $e) {

            }

            $goods->save("API");

            return array("state" => "ok", "message" => "", "goods" => $goods);
        }

        public function getDetail($productId, $params = array())
        {
            $localCurrency = strtoupper(get_option('aeidn_ali_local_currency', ''));
            if ($localCurrency) {
                $currency_conversion_factor = 1;
            } else {
                $currency_conversion_factor = (float)get_option('aeidn_currency_conversion_factor', 1);
            }

            $request_url = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.getPromotionProductDetail/{$this->account->appKey}";
            $request_url .= "?fields=productId,productTitle,productUrl,imageUrl,originalPrice,salePrice,discount,evaluateScore,commission,commissionRate,30daysCommission,volume,packageType,lotNum,validTime,storeName,storeUrl,localPrice";
            $request_url .= "&productId=$productId";
            $request_url .= '&language='.get_option('aeidn_tr_aliexpress_language', 'en');
            if ($localCurrency) {
                $request_url .= "&localCurrency=$localCurrency";
            }

            $full_request_url = apply_filters('aeidn_get_localized_url', $request_url,
                array('type' => 'aliexpress_request'));

            $request = aeidn_remote_get($full_request_url);

            if (is_wp_error($request)) {
                return array('state' => 'error', 'message' => 'alibaba.com not response!');
            }
            //DEBUG

            $data = json_decode($request['body'], true);

            //$data['result']['description'] = "#debug_hide#";
            //echo $full_request_url."<br/>";
            //echo "<pre>";print_r($data);echo "</pre>";

            if (isset($data['errorCode']) && (int)$data['errorCode'] === 20010000) {
                if (isset($data['result']['productId']) && (int)$data['result']['productId'] === (int)$productId) {
                    $goods = new AEIDN_Goods("aliexpress#" . $productId);

                    $local_price = $localCurrency ? AEIDN_Goods::getNormalizePrice($data['result']['localPrice']) : AEIDN_Goods::getNormalizePrice($data['result']['salePrice']);
                    $sale_price = AEIDN_Goods::getNormalizePrice($data['result']['salePrice']);
                    $usd_course = round($local_price / $sale_price, 2);

                    $goods->price = round($local_price, 2);

                    $originalPrice = AEIDN_Goods::getNormalizePrice($data['result']['originalPrice']);
                    $goods->regular_price = round($originalPrice * $usd_course, 2);

                    $goods->user_price = round($goods->price * $currency_conversion_factor, 2);
                    $goods->user_regular_price = round($goods->regular_price * $currency_conversion_factor, 2);

                    $commission_rate = 8;
                    $goods->additional_meta['commission'] = round($local_price * ($commission_rate / 100), 2);

                    $goods->additional_meta['regular_price'] = round(AEIDN_Goods::getNormalizePrice($data['result']['originalPrice']) * $currency_conversion_factor, 2);
                    $goods->additional_meta['sale_price'] = round(AEIDN_Goods::getNormalizePrice($data['result']['salePrice']) * $currency_conversion_factor, 2);

                    $goods->detail_url = $data['result']["productUrl"];
                    $goods->additional_meta['detail_url'] = $data['result']["productUrl"];

                    $goods->seller_url = isset($data['result']["storeUrl"]) ? $data['result']["storeUrl"] : "";

                    $goods->image = $data['result']['imageUrl'];

                    return array("state" => "ok", "message" => "", "goods" => apply_filters('aeidn_modify_goods_data', $goods, $data, "aliexpress_get_detail"));
                } else {
                    $goods = new AEIDN_Goods();
                    $goods->availability = false;
                    return array("state" => "ok", "message" => "", "goods" => $goods);
                }
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20010000 && isset($data['result']['productId'])) {
                return array('state' => 'error', 'message' => 'System Error');
            } elseif (isset($data['errorCode']) && ((int)$data['errorCode'] === 20130000 || (int)$data['errorCode'] === 20030100)) {
                return array('state' => 'error', 'message' => 'Input parameter Product ID is error');
            } elseif (isset($data['error_code']) && (int)$data['error_code'] === 400) {
                return array('state' => 'error', 'message' => "{$data['error_message']}");
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030000) {
                return array('state' => 'error', 'message' => 'Required parameters');
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030010) {
                return array('state' => 'error', 'message' => 'Keyword input parameter error');
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030020) {
                return array('state' => 'error', 'message' => 'Category ID input parameter error or formatting errors');
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030030) {
                return array('state' => 'error', 'message' => 'Commission rate input parameter error or formatting errors');
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030040) {
                return array('state' => 'error', 'message' => 'Unit input parameter error or formatting errors');
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030050) {
                return array('state' => 'error', 'message' => '30 days promotion amount input parameter error or formatting errors');
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030060) {
                return array('state' => 'error', 'message' => 'Tracking ID input parameter error or limited length');
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030070) {
                return array('state' => 'error', 'message' => 'Unauthorized transfer request');
            } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20020000) {
                return array('state' => 'error', 'message' => 'System Error');
            } else {
                return array('state' => 'error', 'message' => 'Unknown Error');
            }
        }

        private function getAffiliateGoods($goods_list)
        {
            $result = $goods_list;
            $urls = "";
            foreach ($result as $goods) {
                $urls .= ($urls ? "," : "") . $goods->detail_url;
            }

            $request_url = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.getPromotionLinks/{$this->account->appKey}?fields=&trackingId={$this->account->trackingId}&urls={$urls}";

            $request = aeidn_remote_get($request_url);
            if (!is_wp_error($request)) {
                $data = json_decode($request['body'], true);
                if (isset($data['errorCode']) && (int)$data['errorCode'] === 20010000) {
                    foreach ($result as $key => $goods) {
                        $new_promo_url = "";
                        foreach ($data['result']['promotionUrls'] as $pu) {
                            if ($pu['url'] === $result[$key]->detail_url) {
                                $new_promo_url = $pu['promotionUrl'];
                                break;
                            }
                        }
                        if ($new_promo_url) {
                            /**
                             * @var AEIDN_Goods $good
                             */
                            $good =& $result[$key];
                            $good->detail_url = $new_promo_url;
                            $good->save("API");
                        }
                    }
                }
            }

            return $result;
        }

        public function checkAvailability(/* @var $goods AEIDN_Goods */
            $goods)
        {
            $request_url = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.getPromotionProductDetail/{$this->account->appKey}";
            $request_url .= "?fields=productId,productTitle,productUrl,imageUrl,originalPrice,salePrice,discount,evaluateScore,commission,commissionRate,30daysCommission,volume,packageType,lotNum,validTime,storeName,storeUrl";
            $request_url .= "&productId=$goods->external_id";

            /* $request_url = "http://gw.api.alibaba.com/openapi/param2/1/portals.open/";
              $request_api = "api.getPromotionProductDetail/{$this->account->appKey}?trackingId={$this->account->trackingId}";
              $request_param = "&productId=$goods->external_id"; */

            $full_request_url = apply_filters('aeidn_get_localized_url', $request_url,
                array('type' => 'aliexpress_request'));

            $request = aeidn_remote_get($full_request_url);

            if (is_wp_error($request)) {
                return array('state' => 'error', 'message' => 'alibaba.com not response!');
            }

            $data = json_decode($request['body'], true);
            //echo $request_url . $request_api . $request_param."<br/>";
            //$data['result']['description'] = "#debug_hide#";
            //echo "<pre>";print_r($data);echo "</pre>";

            if ((int)$data['errorCode'] === 20010000 && (int)$data['result']['productId'] === (int)$goods->external_id && isset($data['result']['availability'])) {
                return $data['result']['availability'] ? true : false;
            } else if ((int)$data['errorCode'] === 20010000 && !isset($data['result']['productId'])) {
                return false;
            }

            return true;
        }

        private function linksToAffiliate($content)
        {
            $hrefs = array();
            $dom = new DOMDocument();
            @$dom->loadHTML($content);
            $dom->formatOutput = true;
            $tags = $dom->getElementsByTagName('a');
            /**
             * @var DOMElement $tag
             */
            foreach ($tags as $tag) {
                $hrefs[] = $tag->getAttribute('href');
            }

            $request_url = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.getPromotionLinks/{$this->account->appKey}?trackingId={$this->account->trackingId}&fields=promotionUrl";
            $request_url .= "&urls=" . implode(',', $hrefs);

            $request = wp_remote_get($request_url);
            if (!is_wp_error($request)) {
                $body = json_decode($request['body'], true);
                if ($body !== '' && isset($body['result'])) {
                    foreach ($body['result']['promotionUrls'] as $link) {
                        $content = str_replace($link['url'], $link['promotionUrl'], $content);
                    }
                }
            }
            return $content;
        }

        public function tmpAliProductInfo($goods)
        {
            //debugbreak();
            $result = array("state" => "ok");
            if ($goods->external_id) {
                $result["description"] = "";
                $result["description_images"] = array();
                $result["m_description"] = "";
                $result["images"] = array();
                $result["attribute"] = array();
                $result["keywords"] = "#empty#";
                $result["quantity"] = "";

                $request_url = "http://desc.aliexpress.com/getDescModuleAjax.htm?productId=" . $goods->external_id . "&t=";
                $desc_url = apply_filters('aeidn_get_localized_url', $request_url,
                    array('type' => 'aliexpress_desc', 'external_id' => $goods->external_id));

                $desc_content = wp_remote_get($desc_url);

                //$desc_content = wp_remote_get( "http://en.aliexpress.com/getSubsiteDescModuleAjax.htm?productId=" . $this->id );
                if (!is_wp_error($desc_content)) {
                    $desc_content = str_replace(array("window.productDescription='", "';"), '', $desc_content['body']);
                    if (function_exists('mb_convert_encoding')) {
                        $desc_content = trim(mb_convert_encoding($desc_content, 'HTML-ENTITIES', 'UTF-8'));
                    } else {
                        $desc_content = htmlspecialchars_decode(utf8_decode(htmlentities($desc_content, ENT_COMPAT, 'UTF-8', false)));
                    }

                    $dom = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $dom->loadHTML($desc_content);
                    libxml_use_internal_errors(false);

                    $finder = new DOMXPath($dom);

                    $items = $finder->query('//div[@style="max-width: 650.0px;overflow: hidden;font-size: 0;clear: both;"]');
                    foreach ($items as $item) {
                        $item->parentNode->removeChild($item);
                    }

                    $html = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $dom->saveHTML());

                    $html = AEIDN_Utils::removeTags($html);
                    if (get_option('aeidn_ali_links_to_affiliate', false)) {
                        $html = $this->linksToAffiliate($html);
                    }


                    $html = $this->clearHtml($html);

                    $dom->preserveWhiteSpace = false;
                    $images = $dom->getElementsByTagName('img');

                    $images_url = array();

                    /**
                     * @var DOMElement $image
                     */
                    foreach ($images as $image) {
                        $img = $image->getAttribute('src');
                        $pos = strpos($img, '?');
                        $img = $pos === true ? substr($img, 0, $pos) : $img;

                        if (strrpos($img, '.jpg')) {
                            //$d = getimagesize($img);
                            //if( $d[0] > 300 && $d[1] > 300 )
                            $images_url[] = $img;
                        }
                    }

                    $result["description"] = $html;
                    $result["description_images"] = $images_url;
                }


                $response = wp_remote_get('http://m.aliexpress.com/item-desc/' . $goods->external_id . '.html?site=en');
                if (!is_wp_error($response)) {
                    $html_str = $response['body'];
                    if (function_exists('mb_convert_encoding')) {
                        $html_str = trim(mb_convert_encoding($html_str, 'HTML-ENTITIES', 'UTF-8'));
                    } else {
                        $html_str = htmlspecialchars_decode(utf8_decode(htmlentities($html_str, ENT_COMPAT, 'UTF-8', false)));
                    }

                    $dom = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $dom->loadHTML($html_str);
                    libxml_use_internal_errors(false);
                    $finder = new DOMXPath($dom);

                    //get attributes
                    $rows = $finder->query("//*[contains(@class, 'prop-table')]/tbody/tr");
                    foreach ($rows as $row) {
                        $key = $value = "";
                        /**
                         * @var DOMElement $td
                         */
                        foreach ($row->childNodes as $td) {
                            if (XML_ELEMENT_NODE === $td->nodeType) {
                                if ("key" === $td->getAttribute('class')) {
                                    $key = $td->nodeValue;
                                } else if ("value" === $td->getAttribute('class')) {
                                    $value = $td->nodeValue;
                                }
                            }
                        }
                        if ($value && $value !== "NA" && $value !== "None") {
                            $result["attribute"][] = array("name" => $key, "value" => $value);
                        }
                    }
                }
            } else {
                $result["state"] = "error";
                $result["message"] = "Product ID is empty";
            }
            return $result;
        }

        private function clearHtml($in_html)
        {
            if (!$in_html) {
                return '';
            }
            $html = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $in_html);
            $html = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $html);
            $html = preg_replace('/(<[^>]+) class=".*?"/i', '$1', $html);
            $html = preg_replace('/(<[^>]+) width=".*?"/i', '$1', $html);
            $html = preg_replace('/(<[^>]+) height=".*?"/i', '$1', $html);
            $html = preg_replace('/(<[^>]+) alt=".*?"/i', '$1', $html);
            $html = preg_replace('/^<!DOCTYPE.+?>/', '$1', str_replace(array('<html>', '</html>', '<body>', '</body>'), '', $html));
            $html = preg_replace("/<\/?div[^>]*\>/i", "", $html);

            $html = preg_replace('#(<a.*?>).*?(</a>)#', '$1$2', $html);
            $html = preg_replace('/<a[^>]*>(.*)<\/a>/iU', '', $html);
            $html = preg_replace("/<\/?h1[^>]*\>/i", "", $html);
            $html = preg_replace("/<\/?strong[^>]*\>/i", "", $html);
            $html = preg_replace("/<\/?span[^>]*\>/i", "", $html);

            $html = str_replace([' &nbsp; ', '&nbsp;'], ['', ' '], $html);

            $pattern = "/<[^\/>]*>([\s]?)*<\/[^>]*>/";
            $html = preg_replace($pattern, '', $html);

            $html = str_replace(array('<img', '<table'), array('<img class="img-responsive"', '<table class="table table-bordered'), $html);
            $html = force_balance_tags($html);

            return $html;
        }

        public function getImages($goods)
        {
            $images = array();

            $image_page = str_replace("/item/", "/item-img/", $goods->detail_url);
            $image_page = preg_replace("/\/\/[a-z]{2,3}\.aliexpress/i", "//www.aliexpress", $image_page);

            $response = wp_remote_get($image_page);
            if (!is_wp_error($response)) {
                $html_str = $response['body'];

                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($html_str);
                libxml_use_internal_errors(false);

                $finder = new DOMXPath($dom);
                $items = $finder->query("//*[contains(@class, 'image')]/ul/li/a/img");
                $ind = 0;

                /**
                 * @var DOMElement $item
                 */
                foreach ($items as $item) {
                    $ind++;
                    $url_info = parse_url($item->getAttribute("src"));
                    $path_info = pathinfo($url_info['path']);
                    $images[$path_info['dirname']] = $url_info['scheme'] . '://' . $url_info['host'] . $path_info['dirname'] . "/" . $ind . "." . $path_info['extension'];
                }
            }

            return $images;
        }

    }
}
