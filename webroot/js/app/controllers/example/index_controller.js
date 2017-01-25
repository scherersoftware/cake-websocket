App.Controllers.ExampleIndexController = Frontend.AppController.extend({
    startup: function() {
        App.Main.loadJsonAction({
            'plugin': 'websocket',
            'controller': 'example',
            'action': 'userDataPanel'
        }, {
            target: this.$('.user-data-panel-target'),
            replaceTarget: true
        });

        App.Main.loadJsonAction({
            'plugin': 'websocket',
            'controller': 'example',
            'action': 'userFormPanel'
        }, {
            target: this.$('.user-form-panel-target'),
            replaceTarget: true
        });
    }
});
