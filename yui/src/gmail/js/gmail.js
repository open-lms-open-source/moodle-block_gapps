/**
 * Gmail Inbox Feed
 *
 * @module moodle-block_gapps-gmail
 */

/**
 * Displays the user's latest unread emails from Gmail.
 *
 * @constructor
 * @namespace M.block_gapps
 * @class Gmail
 * @extends Y.Base
 */
var GMAIL = function() {
    GMAIL.superclass.constructor.apply(this, arguments);
};

Y.extend(GMAIL, Y.Base,
    {
        /**
         * Check authorization and setup event handlers.
         * @method initializer
         */
        initializer: function() {
            if (this.get('popup')) {
                Y.one('body').delegate('click', this.popup_handler, '.block_gapps .gapps a, .block_gapps .unreadmessages a');
            }
            this.check_auth();
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
        },

        /**
         * Promise rejection handler
         * @method handle_reason
         * @param reason
         */
        handle_reason: function(reason) {
            if (reason instanceof Error) {
                Y.log(reason.message, 'warn', GMAIL.NAME);
            } else {
                Y.log('Request failed: [' + reason.status + ' ' + reason.statusText + '] ' +
                    reason.result.error.message, 'error', GMAIL.NAME);
            }
        },

        /**
         * Check authorization
         * @method check_auth
         */
        check_auth: function() {
            Y.log('Checking authorization', 'info', GMAIL.NAME);

            var that = this;
            this.get('gapi').auth.authorize({
                client_id: this.get('clientId'),
                scope: this.get('scopes'),
                immediate: true
            }, function(authResult) {
                that.handle_auth_result(authResult);
            });
        },

        /**
         * If we authorize, then make our API calls, otherwise,
         * show a link to the user to allow access.
         * @method handle_auth_result
         * @param authResult The result from authorization check
         */
        handle_auth_result: function(authResult) {
            var authorize = Y.all('.block_gapps .authorize');
            if (authResult && !authResult.error) {
                Y.log('Sucessfully authorized', 'info', GMAIL.NAME);
                authorize.setStyle('display', 'none');
                this.fetch_messages(this.render_messages);
            } else {
                Y.log('Not authorized or failed to authorize', 'info', GMAIL.NAME);
                if (authResult.error) {
                    Y.log('Authorization error: ' + authResult.error, 'warn', GMAIL.NAME);
                }
                authorize.on('click', this.handle_auth_click, this);
                authorize.setStyle('display', 'inline');
            }
        },

        /**
         * User has clicked to authorize access, do that now.
         * @method handle_auth_click
         * @param e
         */
        handle_auth_click: function(e) {
            e.preventDefault();

            Y.log('User requested authorization', 'info', GMAIL.NAME);

            var that = this;
            this.get('gapi').auth.authorize({
                client_id: this.get('clientId'),
                scope: this.get('scopes'),
                immediate: false
            }, function(authResult) {
                that.handle_auth_result(authResult);
            });
        },

        /**
         * Handles the process of loading the Gmail API,
         * getting unread email messages and inbox unread
         * count.  Once done, call the passed callback.
         * @method fetch_messages
         * @param {Function} callback
         */
        fetch_messages: function(callback) {
            var that = this,
                messages = [];

            this.get('gapi').client.load('gmail', 'v1').then(function() {
                Y.log('Gmail v1 client library loaded', 'info', GMAIL.NAME);
                return that.fetch_unread_messages();
            }).then(function(resp) {
                Y.log('Request for messages list sucessful', 'info', GMAIL.NAME);
                if (resp.result.messages.length === 0) {
                    // No unread messages, bail.
                    callback.call(that, 0, []);
                    throw new Error('No unread messages returned');
                }
                messages = resp.result.messages;
                return that.fetch_message_details_and_inbox(messages);
            }).then(function(resp) {
                Y.log('Batch request successful', 'info', GMAIL.NAME);
                var data = that.process_message_details_and_inbox_responses(resp, messages);
                callback.call(that, data.unreadMessagesCount, data.messages);
            }).then(undefined, function(reason) {
                that.handle_reason(reason);
            });
        },

        /**
         * Fetch unread Gmail messages
         * @method fetch_unread_messages
         * @returns {*}
         */
        fetch_unread_messages: function() {
            return this.get('gapi').client.gmail.users.messages.list({
                userId: 'me',
                q: 'is:unread',
                maxResults: this.get('numberOfMessages')
            });
        },

        /**
         * Build a batch request to get email message contents and inbox unread count
         * @method fetch_message_details_and_inbox
         * @param {Array} messages List of message IDs
         * @returns {*}
         */
        fetch_message_details_and_inbox: function(messages) {
            // We use batch to send all our requests at once.
            var httpBatch = this.get('gapi').client.newHttpBatch();

            // Get message details for each.
            for (var i = 0; i < messages.length; i++) {
                var httpRequest = this.get('gapi').client.gmail.users.messages.get({
                    userId: 'me',
                    id: messages[i].id,
                    fields: 'id,payload(headers),snippet'
                });

                httpBatch.add(httpRequest, {id: messages[i].id});
            }

            // Get inbox unread count.
            var inboxRequest = this.get('gapi').client.gmail.users.labels.get({
                userId: 'me',
                id: 'INBOX'
            });
            httpBatch.add(inboxRequest, {id: 'INBOX'});

            return httpBatch;
        },

        /**
         * Process all responses from the batch request
         * @method process_message_details_and_inbox_responses
         * @param resp Responses from the batch request
         * @param {Array} messages Message ID list
         * @returns {{unreadMessagesCount: Integer, messages: Array}}
         */
        process_message_details_and_inbox_responses: function(resp, messages) {
            var messagesList = [];

            // Process all of our email message responses.
            // Keep correct sorting by looping through message IDs.
            for (var i = 0; i < messages.length; i++) {
                if (resp.result[messages[i].id] === undefined) {
                    Y.log('Failed to find message with ID ' + messages[i].id, 'warn', GMAIL.NAME);
                    continue;
                }
                var message = resp.result[messages[i].id];
                var from = this.get_from_names(
                    this.find_header_value('From', message.result.payload.headers)
                );

                messagesList.push({
                    id: message.result.id,
                    subject: this.find_header_value('Subject', message.result.payload.headers),
                    fromFirstName: from.firstName,
                    fromLastName: from.lastName,
                    snippet: message.result.snippet
                });
            }

            // Process inbox response.
            var unreadCount = (resp.result.INBOX !== undefined) ? resp.result.INBOX.result.messagesUnread : 0;

            return {
                unreadMessagesCount: unreadCount,
                messages: messagesList
            };
        },

        /**
         * Render the email messages onto the page
         * @method render_messages
         * @param {Integer} unreadMessagesCount Number of unread messages in the users inbox
         * @param {Array} messages Unread email messages
         */
        render_messages: function(unreadMessagesCount, messages) {
            Y.log('Rendering email messages', 'info', GMAIL.NAME);

            var source = Y.one('#block_gapps-unread-messages-template').getHTML(),
                template = Y.Handlebars.compile(source),
                html;

            // Render the template to HTML using the specified data.
            html = template({
                unreadCount: unreadMessagesCount,
                messages: messages
            });

            // Append the rendered template to the page.
            Y.all('.block_gapps .unreadmessages').setHTML(html);
        },

        /**
         * Extract the first name and last name from the from header
         * @method get_from_names
         * @param {String} from The from header value, EG: "John Doe <foo@bar.com>"
         * @returns {{firstName: (string), lastName: string}}
         */
        get_from_names: function(from) {
            var name  = from.replace(/<.*>$/, '').trim();
            var parts = name.split(' ');

            var lastname = '';
            if (parts.length > 1) {
                lastname = parts.pop();
            }
            var firstname = parts.join(' ');

            return {
                firstName: firstname,
                lastName: lastname
            };
        },

        /**
         * Find a header value
         * @method find_header_value
         * @param {String} name The header name to find
         * @param {Array} headers The headers to search through
         * @returns String
         */
        find_header_value: function(name, headers) {
            for (var i = 0; i < headers.length; i++) {
                if (name.toLowerCase() === headers[i].name.toLowerCase()) {
                    return headers[i].value;
                }
            }
            return '';
        }
    },
    {
        NAME: NAME,
        ATTRS: {
            /**
             * Google Rest API
             *
             * @attribute gapi
             * @type Object
             * @default undefined
             */
            gapi: { value: undefined },
            /**
             * Client ID defined in the Google Developers Console
             *
             * @attribute clientId
             * @type String
             */
            clientId: { validator: Y.Lang.isString },
            /**
             * Scopes
             *
             * @attribute scopes
             * @type String
             * @default 'https://www.googleapis.com/auth/gmail.readonly'
             */
            scopes: { value: 'https://www.googleapis.com/auth/gmail.readonly', readOnly: true },
            /**
             * Max number of messages to display
             *
             * @attribute numberOfMessages
             * @type Integer
             * @default 10
             */
            numberOfMessages: { value: 10, validator: Y.Lang.isNumber },
            /**
             * Max number of messages to display
             *
             * @attribute numberOfMessages
             * @type Boolean
             * @default false
             */
            popup: { value: false, validator: Y.Lang.isBoolean }
        }
    }
);

M.block_gapps = M.block_gapps || {};
M.block_gapps.Gmail = GMAIL;
