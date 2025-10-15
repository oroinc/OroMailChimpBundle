import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import __ from 'orotranslation/js/translator';

export default function(options) {
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
