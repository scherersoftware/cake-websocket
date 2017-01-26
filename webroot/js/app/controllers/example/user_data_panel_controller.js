App.Controllers.ExampleUserDataPanelController = Frontend.AppController.extend({
    exampleUser: null,
    startup: function() {
        this.exampleUser = this.getVar('exampleUser');
        App.Main.Websocket.onEvent('userDataUpdated', function(payload) {
            if (payload.editedUserId === this.exampleUser.id) {
                this.reloadPanel();
            }

        }.bind(this));
    },
    reloadPanel: function() {
        var url = {
            'plugin': 'websocket',
            'controller': 'example',
            'action': 'userDataPanel'
        };
        App.Main.loadJsonAction(url, {
            target: $(this._dom),
            replaceTarget: true
        });
    }
});
