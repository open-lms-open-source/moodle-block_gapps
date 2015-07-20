YUI.add('moodle-block_gapps-popup', function (Y, NAME) {

/**
 * Opens links in new window
 *
 * @module moodle-block_gapps-popup
 */

/**
 * Opens links in new window
 *
 * @constructor
 * @namespace M.block_gapps
 * @class Popup
 * @extends Y.Base
 */
var POPUP = function() {
    POPUP.superclass.constructor.apply(this, arguments);
};

Y.extend(POPUP, Y.Base,
    {
        /**
         * Bind events
         * @method initializer
         */
        initializer: function() {
            Y.one('body').delegate('click', this.popup_handler, '.block_gapps .gapps a, .block_gapps .unreadmessages a');
        },

        /**
         * Open link in popup
         * @method popup_handler
         * @param e
         */
        popup_handler: function(e) {
            openpopup(e, {
                url: e.currentTarget.get('href'),
                name: "block_gapps",
                options: "height=800,width=1000,top=0,left=0,menubar=0,location=0,scrollbars,resizable,toolbar,status,directories=0,fullscreen=0,dependent"
            });
        }
    },
    {
        NAME: NAME
    }
);

M.block_gapps = M.block_gapps || {};
M.block_gapps.Popup = POPUP;
M.block_gapps.init_popup = function(config) {
    return new POPUP(config);
};


}, '@VERSION@', {"requires": ["base", "event"]});
