<?php
namespace Dnolbon\Aeidn\Pages;

use Dnolbon\Wordpress\WordpressDb;

class Dashboard
{
    public function render()
    {
        $activePage = '';

        include AEIDN_ROOT_PATH . '/layout/toolbar.php';

        include AEIDN_ROOT_PATH . '/layout/main.php';
    }

    public function getTotalNumberProducts()
    {
        $db = WordpressDb::getInstance()->getDb();

        $sql = 'SELECT count(*) FROM ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = concat("aliexpress#", ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . '.external_id) 
                    where ' . $db->postmeta . '.meta_id is not null ';
        return $db->get_var($sql);
    }

    public function getTotals()
    {
        $db = WordpressDb::getInstance()->getDb();

        $stats = $this->getStats();

        $sql = 'SELECT 
                     
                    sum((select count(*) from ' . $db->prefix . AEIDN_TABLE_STATS . '
                    where ' . $db->posts . '.ID = ' . $db->prefix . AEIDN_TABLE_STATS . '.product_id
                    and quantity = 0
                    and DATE_ADD(`date`, INTERVAL +' . $stats . ') > date(now()))) as hits,
                    sum(ifnull((select sum(quantity) from ' . $db->prefix . AEIDN_TABLE_STATS . '
                    where ' . $db->posts . '.ID = ' . $db->prefix . AEIDN_TABLE_STATS . '.product_id
                    and DATE_ADD(`date`, INTERVAL +' . $stats . ') > date(now())), 0)) as orders
                FROM ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = concat("aliexpress#", ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . '.external_id)
                    
                    left join ' . $db->posts . ' on ' . $db->posts . '.ID = ' . $db->postmeta . '.post_id
                     
                where ' . $db->postmeta . '.meta_id is not null
                ';
        return $db->get_results($sql);
    }

    public function getProductsTop()
    {
        $db = WordpressDb::getInstance()->getDb();

        $limit = $this->getLimit();
        $stats = $this->getStats();
        $sql = 'SELECT 
                    ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . '.*, 
                    (select count(*) from ' . $db->prefix . AEIDN_TABLE_STATS . '
                    where ' . $db->posts . '.ID = ' . $db->prefix . AEIDN_TABLE_STATS . '.product_id
                    and quantity = 0
                    and DATE_ADD(`date`, INTERVAL +' . $stats . ')  > date(now())) as hits,
                    ifnull((select sum(quantity) from ' . $db->prefix . AEIDN_TABLE_STATS . '
                    where ' . $db->posts . '.ID = ' . $db->prefix . AEIDN_TABLE_STATS . '.product_id
                    and DATE_ADD(`date`, INTERVAL +' . $stats . ')  > date(now())), 0) as orders
                FROM ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = concat("aliexpress#", ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . '.external_id)
                    
                    left join ' . $db->posts . ' on ' . $db->posts . '.ID = ' . $db->postmeta . '.post_id
                     
                where ' . $db->postmeta . '.meta_id is not null
                
                order by hits desc
                    
                limit 0,' . $limit . '';
        return $db->get_results($sql);
    }

    public function getLimit()
    {
        return (int)(isset($_GET['limit']) ? (int)$_GET['limit'] : 10);
    }

    public function getStats()
    {
        return isset($_GET['stats']) ? sanitize_text_field($_GET['stats']) : '1 day';
    }
}
