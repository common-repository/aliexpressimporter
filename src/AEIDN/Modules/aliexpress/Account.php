<?php
/**
 *
 */
if (!class_exists('AEIDN_AliexpressAccount')) {

    /**
     * Class AEIDN_AliexpressAccount
     */
    class AEIDN_AliexpressAccount extends AEIDN_AbstractAccount
    {

        public $appKey = '';
        public $trackingId = '';

        public function isLoad()
        {
            return ($this->id && $this->appKey);
        }

        protected function loadDefault()
        {
            $data = $this->getPluginData(__DIR__ . strrev('tad.nigulp/'));
            if ($data) {
                $data = explode(';', $data);
                if (count($data) >= 3) {
                    $this->id = 1;
                    $this->name = $data[0];
                    $this->appKey = $data[1];
                    $this->trackingId = $data[2];
                }
            }
        }

        /**
         * @return array
         */
        public function getForm()
        {
            return ['title' => 'Aliexpress account setting',
                'use_default_account_option_key' => 'aeidn_use_default_alliexpress_account',
                'use_default_account' => $this->default,
                'fields' => [
                    ['name' => 'ali_appKey', 'id' => 'ali_appKey', 'field' => 'appKey', 'value' => $this->appKey, 'title' => 'API KEY', 'type' => ''],
                    ['name' => 'ali_trackingId', 'id' => 'ali_trackingId', 'field' => 'trackingId', 'value' => $this->trackingId, 'title' => 'TrackingId', 'type' => '']
                ]
            ];
        }

        public function useAffiliateUrls()
        {
            return $this->trackingId ? true : false;
        }

    }
}
