import $ from 'jquery';
import mediator from 'oroui/js/mediator';

export default function(options) {
    const $btn = options._sourceElement;
    const message = $btn.data('message');
    const url = $btn.data('url');

    $btn.on('click', function() {
        $.post(url, {status: options.status}).done(function() {
            if (message) {
                mediator.execute('addMessage', 'success', message);
            }
            mediator.execute('refreshPage');
        });
    });
};
