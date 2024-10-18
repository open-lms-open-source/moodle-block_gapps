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
 * Opens links in new window for block_gapps
 *
 * @module     block_gapps/popup
 * @copyright  2024 Copyright (c) 2024 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    /**
     * Initialize the popup functionality
     * @method init
     */
    function init() {
        document.body.addEventListener('click', popupHandler);
    }

    /**
     * Handle click events and open links in popup
     * @method popupHandler
     * @param {Event} e - The click event
     */
    function popupHandler(e) {
        const target = e.target.closest('.block_gapps .gapps a, .block_gapps .unreadmessages a');
        if (target) {
            e.preventDefault();
            window.openpopup(e, {
                url: target.href,
                name: "block_gapps",
                options: "height=800,width=1000,top=0,left=0,menubar=0," +
                    "location=0,scrollbars,resizable,toolbar,status," +
                    "directories=0,fullscreen=0,dependent"
            });
        }
    }

    return {
        init: init
    };
});
