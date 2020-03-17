define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    return function(options) {
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
});
