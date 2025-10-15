import WidgetComponent from 'oroui/js/app/components/widget-component';
import mediator from 'oroui/js/mediator';

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

export default ConnectButtonComponent;
