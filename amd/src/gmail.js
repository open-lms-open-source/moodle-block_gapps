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
 * TODO describe module gmail
 *
 * @module     block_gapps/gmail
 * @copyright  2024 Copyright (c) 2024 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Gmail Inbox Feed
 *
 * @module block_gapps/gmail
 */
define(['core/log'], function(log) {
    /**
     * Gmail class for handling Gmail integration
     */
    class Gmail {
        constructor(clientId, numberOfMessages) {
            this.clientId = clientId;
            this.numberOfMessages = numberOfMessages;
            this.scopes = 'https://www.googleapis.com/auth/gmail.readonly';
            this.gapi = null;
        }

        /**
         * Initialize the Gmail module
         */
        init() {
            this.loadGapiClient().then(() => {
                this.checkAuth();
            }).catch((error) => {
                this.handleReason(error);
            });
        }

        /**
         * Load the GAPI client
         */
        loadGapiClient() {
            return new Promise((resolve, reject) => {
                if (typeof gapi === 'undefined') {
                    reject(new Error('Google API client not loaded'));
                    return;
                }

                this.gapi = window.gapi;
                this.gapi.load('client:auth2', {
                    callback: resolve,
                    onerror: reject
                });
            });
        }

        /**
         * Promise rejection handler
         * @param {Error|Object} reason
         */
        handleReason(reason) {
            if (reason instanceof Error) {
                log.warn(reason.message);
            } else {
                log.error(`Request failed: [${reason.status} ${reason.statusText}] ${reason.result.error.message}`);
            }
        }

        /**
         * Check authorization
         */
        checkAuth() {
            log.info('Checking authorization');
            if (this.gapi && this.gapi.auth2) {
                this.gapi.auth2.authorize({
                    client_id: this.clientId,
                    scope: this.scopes,
                    immediate: true
                }, (authResult) => {
                    this.handleAuthResult(authResult);
                });
            } else {
                log.error('Google API client or auth2 not loaded');
                this.handleAuthResult({error: 'Google API client or auth2 not loaded'});
            }
        }

        /**
         * Handle authorization result
         * @param {Object} authResult
         */
        handleAuthResult(authResult) {
            const authorizeElement = document.querySelector('.block_gapps .authorize');
            if (authResult && !authResult.error) {
                log.info('Successfully authorized');
                authorizeElement.style.display = 'none';
                this.fetchMessages(this.renderMessages.bind(this));
            } else {
                log.info('Not authorized or failed to authorize');
                if (authResult.error) {
                    log.warn('Authorization error: ' + authResult.error);
                }
                authorizeElement.addEventListener('click', this.handleAuthClick.bind(this));
                authorizeElement.style.display = 'inline';
            }
        }

        /**
         * Handle authorization click
         * @param {Event} e
         */
        handleAuthClick(e) {
            e.preventDefault();
            log.info('User requested authorization');
            this.gapi.auth2.authorize({
                client_id: this.clientId,
                scope: this.scopes,
                immediate: false
            }, (authResult) => {
                this.handleAuthResult(authResult);
            });
        }

        /**
         * Fetch messages
         * @param {Function} callback
         */
        fetchMessages(callback) {
            let messages = [];
            this.gapi.client.load('gmail', 'v1')
                .then(() => {
                    log.info('Gmail v1 client library loaded');
                    return this.fetchUnreadMessages();
                })
                .then((resp) => {
                    log.info('Request for messages list successful');
                    if (resp.result.messages.length === 0) {
                        callback.call(this, 0, []);
                        throw new Error('No unread messages returned');
                    }
                    messages = resp.result.messages;
                    return this.fetchMessageDetailsAndInbox(messages);
                })
                .then((resp) => {
                    log.info('Batch request successful');
                    const data = this.processMessageDetailsAndInboxResponses(resp, messages);
                    callback.call(this, data.unreadMessagesCount, data.messages);
                })
                .catch((reason) => {
                    this.handleReason(reason);
                });
        }

        /**
         * Fetch unread Gmail messages
         * @returns {Promise}
         */
        fetchUnreadMessages() {
            return this.gapi.client.gmail.users.messages.list({
                userId: 'me',
                q: 'is:unread',
                maxResults: this.numberOfMessages
            });
        }

        /**
         * Fetch message details and inbox
         * @param {Array} messages
         * @returns {Promise}
         */
        fetchMessageDetailsAndInbox(messages) {
            const httpBatch = this.gapi.client.newHttpBatch();
            messages.forEach((message) => {
                const httpRequest = this.gapi.client.gmail.users.messages.get({
                    userId: 'me',
                    id: message.id,
                    fields: 'id,payload(headers),snippet'
                });
                httpBatch.add(httpRequest, {id: message.id});
            });
            const inboxRequest = this.gapi.client.gmail.users.labels.get({
                userId: 'me',
                id: 'INBOX'
            });
            httpBatch.add(inboxRequest, {id: 'INBOX'});
            return httpBatch;
        }

        /**
         * Process message details and inbox responses
         * @param {Object} resp
         * @param {Array} messages
         * @returns {{unreadMessagesCount: number, messages: Array}}
         */
        processMessageDetailsAndInboxResponses(resp, messages) {
            const messagesList = messages.reduce((acc, message) => {
                if (resp.result[message.id]) {
                    const messageData = resp.result[message.id].result;
                    const from = this.getFromNames(
                        this.findHeaderValue('From', messageData.payload.headers)
                    );
                    acc.push({
                        id: messageData.id,
                        subject: this.findHeaderValue('Subject', messageData.payload.headers),
                        fromFirstName: from.firstName,
                        fromLastName: from.lastName,
                        snippet: messageData.snippet
                    });
                } else {
                    log.warn(`Failed to find message with ID ${message.id}`);
                }
                return acc;
            }, []);

            const unreadCount = resp.result.INBOX ? resp.result.INBOX.result.messagesUnread : 0;

            return {
                unreadMessagesCount: unreadCount,
                messages: messagesList
            };
        }

        /**
         * Render the email messages onto the page
         * @param {number} unreadMessagesCount Number of unread messages in the user's inbox
         * @param {Array} messages Unread email messages
         */
        renderMessages(unreadMessagesCount, messages) {
            log.info('Rendering email messages');

            const container = document.querySelector('.block_gapps .unreadmessages');
            if (!container) {
                log.error('Container element not found');
                return;
            }

            const unreadInfo = document.createElement('small');
            unreadInfo.className = 'unreadinfo';
            unreadInfo.textContent = `Unread messages: ${unreadMessagesCount}`;

            const composeLink = document.createElement('small');
            composeLink.innerHTML = '<a href="https://mail.google.com/mail/u/0/#inbox?compose=new">Compose</a>';

            const messagesList = document.createElement('ul');
            messagesList.className = 'messages unstyled';

            messages.forEach(message => {
                const li = document.createElement('li');
                const nameSpan = document.createElement('span');
                nameSpan.textContent = `${message.fromFirstName} ${message.fromLastName}`;
                li.appendChild(nameSpan);
                li.appendChild(document.createElement('br'));

                const link = document.createElement('a');
                link.href = `https://mail.google.com/mail/u/0/#inbox/${message.id}`;
                link.title = message.snippet;
                link.textContent = message.subject || 'No subject';
                li.appendChild(link);

                li.appendChild(document.createElement('hr'));
                messagesList.appendChild(li);
            });

            container.innerHTML = '';
            container.appendChild(unreadInfo);
            container.appendChild(document.createElement('br'));
            container.appendChild(composeLink);
            container.appendChild(messagesList);
        }

        /**
         * Get first name and last name from the 'from' header
         * @param {string} from
         * @returns {{firstName: string, lastName: string}}
         */
        getFromNames(from) {
            const name = from.replace(/<.*>$/, '').trim();
            const parts = name.split(' ');
            const lastName = parts.length > 1 ? parts.pop() : '';
            const firstName = parts.join(' ');
            return {firstName, lastName};
        }

        /**
         * Find header value
         * @param {string} name
         * @param {Array} headers
         * @returns {string}
         */
        findHeaderValue(name, headers) {
            const header = headers.find(h => h.name.toLowerCase() === name.toLowerCase());
            return header ? header.value : '';
        }
    }

    return {
        init: function(clientId, numberOfMessages) {
            const gmail = new Gmail(clientId, numberOfMessages);
            gmail.init();
        }
    };
});
