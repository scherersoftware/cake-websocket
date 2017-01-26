App.Components.WebsocketComponent = Frontend.Component.extend({
    _socket: null,
    _eventCallbacks: {},
    startup: function() {
    },
    setup: function() {
        if (this.Controller.getVar('isAjax')) {
            return;
        }
        var config = this.Controller.getVar('websocketFrontendConfig');
        var host   = config.host + ':' + config.port;
        try {
            this._socket = new WebSocket(host);
            this._socket.onopen = function (e) {
                this.onOpened(e)
                return;
            }.bind(this);
            this._socket.onclose = function (e) {
                this.onClosed(e)
                return;
            }.bind(this);
        } catch (e) {
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
    }
});
