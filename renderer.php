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

defined('MOODLE_INTERNAL') || die();

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
    function block_gapps_client_load() {
        Y.use("moodle-block_gapps-gmail", function() {
            new M.block_gapps.Gmail({
                gapi: gapi,
                clientId: $clientid,
                numberOfMessages: $number
            });
        });
    }
</script>
<script src="https://apis.google.com/js/client.js?onload=block_gapps_client_load"></script>
HTML;
    }

    /**
     * Template for unread messages
     *
     * @param config_model $config
     * @return string
     * @throws coding_exception
     */
    public function unread_messages_template(config_model $config) {
        $nosubject = get_string('nosubject', 'block_gapps');
        $unread    = get_string('unreadmessages', 'block_gapps', '{{unreadCount}}');
        $compose   = get_string('compose', 'block_gapps');

        $from = [];
        if ($config->showfirstname) {
            $from[] = '{{fromFirstName}}';
        }
        if ($config->showlastname) {
            $from[] = '{{fromLastName}}';
        }
        $from = implode(' ', $from);

        if (!empty($from)) {
            $from .= '<br />';
        }

        return <<<HTML
<script id="block_gapps-unread-messages-template" type="text/x-handlebars-template">
    <small class="unreadinfo">$unread</small><br />
    <small><a href="https://mail.google.com/mail/u/0/#inbox?compose=new">$compose</a></small>
    {{#if messages}}
    <ul class="messages unstyled">
        {{#each messages}}
        <li>
            $from
            <a title="{{snippet}}" href="https://mail.google.com/mail/u/0/#inbox/{{id}}">
                {{#if subject}}
                    {{subject}}
                {{else}}
                    $nosubject
                {{/if}}
            </a>
            <hr />
        </li>
        {{/each}}
    </ul>
    {{/if}}
</script>
HTML;
    }
}
