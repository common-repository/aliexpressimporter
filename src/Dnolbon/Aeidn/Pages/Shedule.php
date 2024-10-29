<?php
namespace Dnolbon\Aeidn\Pages;

use Dnolbon\Aeidn\Tables\SheduleTable;
use Dnolbon\Wordpress\WpListTable;

class Shedule
{
    /**
     * @var WpListTable $table
     */
    private $table;

    public function render()
    {
        $activePage = 'schedule';
        include AEIDN_ROOT_PATH . '/layout/toolbar.php';

        $this->getTable()->prepareItems();
        include AEIDN_ROOT_PATH . '/layout/shedule.php';
    }

    /**
     * @return WpListTable
     */
    public function getTable()
    {
        if ($this->table === null) {
            $this->table = new SheduleTable();
        }
        return $this->table;
    }
}
