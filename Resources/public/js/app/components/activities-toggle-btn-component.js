define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const __ = require('orotranslation/js/translator');

    return function(options) {
        options._sourceElement.on('click', function(e) {
            const url = $(e.target).data('url');
            e.preventDefault();

            mediator.execute('showLoading');
            $.post({
                url: url,
                errorHandlerMessage: __('oro.mailchimp.request.error')
            }).done(function(response) {
                mediator.once('page:update', function() {
                    mediator.execute('showFlashMessage', 'success', response.message);
                });
                mediator.execute('refreshPage');
            }).always(function() {
                mediator.execute('hideLoading');
            });
        });
    };
});
