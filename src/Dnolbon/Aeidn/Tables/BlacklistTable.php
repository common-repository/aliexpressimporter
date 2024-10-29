<?php
namespace Dnolbon\Aeidn\Tables;

use Dnolbon\Wordpress\WordpressDb;
use Dnolbon\Wordpress\WpListTable;

class BlacklistTable extends WpListTable
{
    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * @since 3.1.0
     * @access public
     *
     * @return array
     */
    public function getColumns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'image' => 'Thumb',
            'external_id' => 'Alibaba SKU',
            'title' => 'Title'
        ];
        return $columns;
    }

    /**
     * Prepares the list of items for displaying.
     * @uses WP_List_Table::set_pagination_args()
     *
     * @since 3.1.0
     * @access public
     */
    public function prepareItems()
    {
        $current_page = (int)$this->getPagenum();

        $db = WordpressDb::getInstance()->getDb();

        $sql = 'SELECT count(*) FROM ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . ' 
                    
                     inner join ' . $db->prefix . AEIDN_TABLE_BLACKLIST . ' on 
                     ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . '.external_id = ' . $db->prefix . AEIDN_TABLE_BLACKLIST . '.external_id
                    where 1 = 1 ';
        $total = $db->get_var($sql);

        $sql = 'SELECT 
                    ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . '.*
                FROM ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . ' 
                    inner join ' . $db->prefix . AEIDN_TABLE_BLACKLIST . ' on 
                     ' . $db->prefix . AEIDN_TABLE_GOODS_ARCHIVE . '.external_id = ' . $db->prefix . AEIDN_TABLE_BLACKLIST . '.external_id
                     
                where 1 = 1                
                order by %s
                    
                limit ' . (($current_page - 1) * 20) . ',20';
        $this->items = $db->get_results(
            $db->prepare(
                $sql,
                (isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) . ' ' . sanitize_text_field($_GET['order']) : 'title desc')
            )
        );

        $this->setPagination(['total_items' => $total, 'per_page' => 20]);

        $this->initTable();
    }

    public function columnCb($item)
    {
        return sprintf(
            '<input type="checkbox" class="gi_ckb" name="gi[]" value="%s"/>',
            $item->external_id
        );
    }

    /**
     * @return array
     * @override
     */
    public function getBulkActions()
    {
        $actions = [
            'unblacklist' => 'Remove from blacklist'
        ];
        return $actions;
    }

    public function getId($item)
    {
        return 'aliexpress#' . $item->external_id;
    }

    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * The second format will make the initial sorting order be descending
     *
     * @since 3.1.0
     * @access protected
     *
     * @return array
     */
    protected function getSortableColumns()
    {
        return [
            'external_id' => ['external_id', false],
            'title' => ['title', false]
        ];
    }

    protected function columnImage($item)
    {
        return '<img src="' . $item->image . '">';
    }
}
