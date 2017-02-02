Frontend.App.Websocket = Class.extend({
    _socket: null,
    _eventCallbacks: {},
    _isConnected: false,
    _host: null,
    _port: null,
    _reconnectCounter: 0,
    init: function(config) {
        this._host = config.host;
        this._port = config.port;
        this._path = config.path;
        this._usePort = config.usePort;
    },
    setup: function() {
        if (this._isConnected) {
            return;
        }

        try {
            this._socket = new WebSocket(this._buildUrl());
            this._socket.onopen = function (e) {
                this._isConnected = true;
                this.onOpened(e)
                return;
            }.bind(this);
            this._socket.onclose = function (e) {
                this._isConnected = false;
                this.onClosed(e)
                this._triggerReconnect()
                return;
            }.bind(this);
        } catch (e) {
            this._isConnected = false;
            this._triggerReconnect()
        }
    },
    onOpened: function(e) {
        this._socket.onmessage = function(event) {
            var data = JSON.parse(event.data);
            if (this._eventCallbacks[data.eventName] !== undefined) {
                this._eventCallbacks[data.eventName](data.payload);
            }
        }.bind(this);
    },
    onClosed: function(e) {
    },
    onEvent: function(action, callback) {
        this._eventCallbacks[action] = callback;
    },
    _triggerReconnect: function() {
        if (this._isConnected || this._reconnectCounter > 10) {
            return;
        }
        this._reconnectCounter++;
        setTimeout(function() {
            this.setup();
        }.bind(this), 2000);
    },
    _buildUrl: function() {
        var url = this._host;
        if (this._usePort) {
            url += ':' + this._port;
        }
        url += this._path;
        return url;
    }
});
window.App.Websocket = new Frontend.App.Websocket(App.Main.appData.jsonData.websocketFrontendConfig);
$(document).ready(function() {
    window.App.Websocket.setup();
});
