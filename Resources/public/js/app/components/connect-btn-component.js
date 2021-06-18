define(function(require) {
    'use strict';

    const WidgetComponent = require('oroui/js/app/components/widget-component');
    const mediator = require('oroui/js/mediator');

    const ConnectButtonComponent = WidgetComponent.extend({
        defaults: {
            type: 'dialog',
            options: {
                stateEnabled: false,
                incrementalPosition: false,
                loadingMaskEnabled: true,
                dialogOptions: {
                    modal: true,
                    resizable: false,
                    width: 510,
                    autoResize: true
                }
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function ConnectButtonComponent(...args) {
            ConnectButtonComponent.__super__.constructor.apply(this, args);
        },

        _bindEnvironmentEvent: function(widget) {
            const message = this.options.message;

            this.listenTo(widget, 'formSave', function() {
                widget.remove();
                if (message) {
                    mediator.execute('addMessage', 'success', message);
                }
                mediator.execute('refreshPage');
            });
        }
    });

    return ConnectButtonComponent;
});
