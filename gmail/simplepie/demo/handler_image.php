<?php
// This should be modifed as your own use warrants.

require_once('../simplepie.inc');
blocks_gapps_simplepie_misc::display_cached_file($_GET['i'], './cache', 'spi');
?>
