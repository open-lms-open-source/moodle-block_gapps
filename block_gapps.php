<?php // $Id$
/**
 * helloworld block class definition
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package block_helloworld
 */

class block_gapps extends block_base {
    /**
     * Init
     */
    function init() {
        $this->title   = get_string('blockname', 'block_gapps');
        $this->version = 2010022400;
    }

    /**
     * Link to view the block
     */
    function get_content() {
        global $CFG, $USER, $COURSE, $OUTPUT, $PAGE;

        if ($this->content !== NULL) {
            return $this->content;
        }


        $this->content = new stdClass;

        // <body class="yui-skin-sam"> // this could be placed in the body tag but possible to restyle for just in teh block

        $PAGE->requires->css('/blocks/gapps/fonts-min.css');
        $PAGE->requires->css('/blocks/gapps/tabview.css');

        // for now deprecated require_js
        require_js($CFG->wwwroot.'/lib/yui/2.8.1/build/yahoo-dom-event/yahoo-dom-event.js');
        require_js($CFG->wwwroot.'/lib/yui/2.8.1/build/element/element.js');
        require_js($CFG->wwwroot.'/lib/yui/2.8.1/build/tabview/tabview.js'); //gapps.js
        require_js($CFG->wwwroot.'/blocks/gapps/gapps.js');

     /*  //The structure of module array is described at http://developer.yahoo.com/yui/3/yui/
         $module = array(
            'name'=>'form_filemanager',
            'fullpath'=>'/lib/form/filemanager.js',
            'requires' => array('core_filepicker', 'base', 'io', 'node', 'json', 'yui2-button', 'yui2-container', 'yui2-layout', 'yui2-menu', 'yui2-treeview'),
            'strings' => array(array('loading', 'repository'), array('nomorefiles', 'repository'), array('confirmdeletefile', 'repository'),
                 array('add', 'repository'), array('accessiblefilepicker', 'repository'), array('move', 'moodle'),
                 array('cancel', 'moodle'), array('download', 'moodle'), array('ok', 'moodle'),
                 array('emptylist', 'repository'), array('nofilesattached', 'repository'), array('entername', 'repository'), array('enternewname', 'repository'),
                 array('zip', 'editor'), array('unzip', 'moodle'), array('rename', 'moodle'), array('delete', 'moodle'),
                 array('cannotdeletefile', 'error'), array('confirmdeletefile', 'repository'),
                 array('nopathselected', 'repository'), array('popupblockeddownload', 'repository'),
                 array('draftareanofiles', 'repository'), array('path', 'moodle'), array('setmainfile', 'repository')
            )
        );
        $PAGE->requires->js_module($module);
        */

        $gapps_initjs = "gapps_testbuild();";
        $PAGE->requires->js_init_code($gapps_initjs);

        $gapps_tab_title = 'Gapps'; // could include alert icons
        $gmail_tab_title = 'Gmail';
        $gsync_tab_title = 'Gsync';


        
        // try {  
        $gapps = $this->gapps_get_content(); // Gapps Generate Content

        // Gmail Genereate Content
        $gmail = 'Gmail content';
        //$this->gmail_get_content();


        // Gsync Genereate Content
        $gsync = 'Gsync content';

        $gsync = $this->gsync_get_content();

        // } catch () {
        // 
        // }
        // We need to control tabs based on capabilities
        // we could make classes for each service gmail/gsync/gapps and evaluate their cap function
        // then add or don't add the tab as we build the block content (which should be its own function)
        $this->content->text = '<div id="block_gapps_tabs" class="yui-skin-sam">
                                   <div id="demo" class="yui-navset">
                                   <ul class="yui-nav">
                                         
                                     <li><a href="#tab1"><em><span style="font-size:0.8em;">'.$gapps_tab_title.'</span></em></a></li>
                                     <li class="selected"><a href="#tab2"><em><span style="font-size:0.8em;">'.$gmail_tab_title.'</span></em></a></li>
                                     <li><a href="#tab3"><em><span style="font-size:0.8em;">'.$gsync_tab_title.'</span></em></a></li>
                                   </ul>
                                   <div class="yui-content">

                                      <div id="tab1"><p>'.$gapps.'</p></div>

                                      <div id="tab2"><p>'.$gmail.'</p></div>

                                      <div id="tab3"><p>'.$gsync.'</p></div>

                                    </div>
                                    </div>
                                </div>';

        

        $this->content->footer = '';

        // $this->content->items = array();
//        $this->content->icons = array();
//        $title = get_string('view', 'block_gapps');
//        $this->content->items[] = html_writer::tag('a', $title, array('title' => $title, 'href' => new moodle_url("/blocks/gapps/view.php?courseid=$COURSE->id")));
//        $this->content->icons[] = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/admin'), 'alt' => $title));

        return $this->content;
    }




    /**
     * Borrowed from Block_list so I can control the block content but still pass in the list params
     *
     * Render the contents of a block_list.
     * @param array $icons the icon for each item.
     * @param array $items the content of each item.
     * @return string HTML
     */
    public function list_block_contents($icons, $items) {
        $row = 0;
        $lis = array();
        foreach ($items as $key => $string) {
            $item = html_writer::start_tag('li', array('class' => 'r' . $row));
            if (!empty($icons[$key])) { //test if the content has an assigned icon
                $item .= html_writer::tag('div', $icons[$key], array('class' => 'icon column c0'));
            }
            $item .= html_writer::tag('div', $string, array('class' => 'column c1'));
            $item .= html_writer::end_tag('li');
            $lis[] = $item;
            $row = 1 - $row; // Flip even/odd.
        }
        return html_writer::tag('ul', implode("\n", $lis), array('class' => 'list'));
    }


    function gapps_get_content() {
        global $CFG;
        
        $icons = array();
        $items = array();

        $domain = get_config('auth/gsaml','domainname');
        if( empty($domain)) {
        	$this->content->items[] = get_string('nodomainyet','block_gaccess');//"No DOMAIN configured yet";
    		return $this->content;
    	}


        // USE the icons from this page
        // https://www.google.com/a/cpanel/mroomsdev.com/Dashboard
        $google_services = array(

        	array(
        	        'service'   => 'Gmail',
        			'relayurl'  => 'http://mail.google.com/a/'.$domain,
        			'icon_name' => 'gmail.png'
        	),

        	array(
        	        'service'   => 'Calendar',
        			'relayurl'  => 'http://www.google.com/calendar/a/'.$domain,
        			'icon_name' => 'calendar.png'
        	),

        	array(
        	        'service'   => 'Docs',
        			'relayurl'  => 'http://docs.google.com/a/'.$domain,
        			'icon_name' => 'gdocs.png'
        	)
        );

        $newwinlnk = get_config('blocks/gaccess','newwinlink');
        if ($newwinlnk) {
            $target = 'target=\"_new\"';
        } else {
            $target = '';
        }

        foreach( $google_services as $gs ) { // $gs['']
            $items[] = "<a ".$target.". title=\"".$gs['service']."\"  href=\"".$gs['relayurl']."\">".$gs['service']."</a>";

            if ( !empty($gs['icon_name']) ) {
        		$icons[] = "<img src=\"$CFG->wwwroot/blocks/gapps/imgs/".$gs['icon_name']."\" alt=\"".$gs['service']."\" />";
	        } else {
	        	// Default to a check graphic
	        	$icons[] = "<img src=\"$CFG->pixpath/i/tick_green_small.gif\" alt=\"$service\" />";
	        }
        }


        return $this->list_block_contents($icons, $items);
    }

    function gsync_get_content() {
        global $CFG, $USER, $COURSE;

        $items = array();
        $icons = array();


//        if (empty($this->instance) or !self::has_capability()) {
//            return $this->content;
//        }

        $title = get_string('settings', 'block_gapps');
        $items[] = "<a title=\"$title\" href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=blocksettinggdata\">$title</a>";
        $icons[] = "<img src=\"$CFG->pixpath/i/settings.gif\" alt=\"$title\" />";

        $title = get_string('status', 'block_gapps');
        $items[] = "<a title=\"$title\" href=\"$CFG->wwwroot/blocks/gapps/gdata/index.php?hook=status\">$title</a>";
        $icons[] = "<img src=\"$CFG->pixpath/i/tick_green_small.gif\" alt=\"$title\" />";

        $title = get_string('userssynced', 'block_gapps');
        $items[] = "<a title=\"$title\" href=\"$CFG->wwwroot/blocks/gapps/gdata/index.php?hook=users\">$title</a>";
        $icons[] = "<img src=\"$CFG->pixpath/i/users.gif\" alt=\"$title\" />";

        $title = get_string('addusers', 'block_gapps');
        $items[] = "<a title=\"$title\" href=\"$CFG->wwwroot/blocks/gapps/gdata/index.php?hook=addusers\">$title</a>";
        $icons[] = "<img src=\"$CFG->pixpath/i/users.gif\" alt=\"$title\" />";

        return $this->list_block_contents($icons, $items);
    }

    function gmail_get_content() {
        $items = array();
        $icons = array();
        
        return $this->list_block_contents($icons, $items);
    }

}

?>