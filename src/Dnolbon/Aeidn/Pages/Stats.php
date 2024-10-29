<?php
namespace Dnolbon\Aeidn\Pages;

use Dnolbon\Aeidn\Tables\StatsTable;
use Dnolbon\Wordpress\WpListTable;

class Stats
{
    /**
     * @var WpListTable $table
     */
    private $table;

    public function render()
    {
        $activePage = 'stats';
        include AEIDN_ROOT_PATH . '/layout/toolbar.php';

        $this->getTable()->prepareItems();
        include AEIDN_ROOT_PATH . '/layout/stats.php';
    }

    /**
     * @return WpListTable
     */
    public function getTable()
    {
        if ($this->table === null) {
            $this->table = new StatsTable();
        }
        return $this->table;
    }
}
