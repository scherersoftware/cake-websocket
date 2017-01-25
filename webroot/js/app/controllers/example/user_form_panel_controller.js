App.Controllers.ExampleUserFormPanelController = Frontend.AppController.extend({
    startup: function() {
        var $form = this.$('form');
        $form.on('submit', function(e){
            e.preventDefault();
            var url = {
                'plugin': 'websocket',
                'controller': 'example',
                'action': 'userFormPanel'
            };
            App.Main.loadJsonAction(url, {
                data: $form.serialize(),
                onComplete: function() {
                    this.$('input[type="text"]').val('');
                }.bind(this)
            });
        }.bind(this));
    }
});
