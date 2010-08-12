<?php
/**
 * View renderer
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 */

require_once('../../config.php');

// By including mr/bootstrap.php, all mr
// classes become available to us.  So now
// we can call mr_controller without explicitly
// including its class file.
// Use mr_bootstrap::shutdown(); to turn off
// class autoloading (basically the opposite of
// mr_bootstrap::startup();)
require($CFG->dirroot.'/local/mr/bootstrap.php');

mr_controller::render('blocks/gapps', 'blockname', 'block_gapps');

//mr_controller::render('blocks/helloworld', 'blockname', 'block_helloworld');