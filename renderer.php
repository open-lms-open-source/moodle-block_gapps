<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin renderer
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_gapps\config_model;

/**
 * Plugin renderer
 *
 * @package   block_gapps
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_gapps_renderer extends plugin_renderer_base {
    /**
     * Links to Google Apps
     *
     * @param string $domain The Google Apps domain
     * @return array
     * @throws coding_exception
     */
    protected function get_google_apps_links($domain) {
        return [
            [
                'label' => get_string('gmail', 'block_gapps'),
                'url'   => 'https://mail.google.com/a/'.$domain,
                'icon'  => 'gmail',
            ],
            [
                'label' => get_string('calendar', 'block_gapps'),
                'url'   => 'https://www.google.com/calendar/hosted/'.$domain,
                'icon'  => 'calendar',
            ],
            [
                'label' => get_string('drive', 'block_gapps'),
                'url'   => 'https://drive.google.com/a/'.$domain,
                'icon'  => 'drive',
            ],
        ];
    }

    /**
     * Render links to Google Apps
     *
     * @param string $domain The Google Apps domain
     * @return string
     */
    public function google_apps($domain) {
        $items = [];
        foreach ($this->get_google_apps_links($domain) as $link) {
            $icon    = html_writer::span('', 'icon icon-'.$link['icon'], ['aria-hidden' => 'true']);
            $items[] = html_writer::link($link['url'], $icon.' '.$link['label']);
        }

        return html_writer::alist($items, ['class' => 'gapps unstyled']);
    }

    /**
     * HTML and JS for unread Gmail messages
     *
     * @param config_model $config
     * @return string
     */
    public function unread_messages_js(config_model $config) {
        $clientid = json_encode($config->clientid);
        $number   = (int) $config->get_number_of_messages_to_show();

        return <<<HTML

<div class="unreadmessages"></div>
<script type="text/javascript">
    function onGoogleApiLoad() {
        require(['block_gapps/gmail'], function(Gmail) {
            Gmail.init($clientid, $number);
        });
    }
</script>
<script src="https://apis.google.com/js/client.js?onload=onGoogleApiLoad"></script>
HTML;
    }
}
