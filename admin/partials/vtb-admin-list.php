<?php
require_once ( VTB_DIR . '/admin/class-vtb-list-table.php' );
$listTable = new VTB_List_Table();
echo '<div class="wrap"><h2>Tutorials</h2>';
$listTable->prepare_items();
$listTable->display();
echo '</div>';