import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import $ from 'jquery';

export default function(options) {
    const $source = options._sourceElement;
    const $apiKeyEl = $source.find('input');
    const $btn = $source.find('button');
    const $status = $source.find('.connection-status');
    const $pingHolder = $source.find('.ping-holder');

    const onError = function(message) {
        message = message || __('oro.mailchimp.integration_transport.api_key.check.message');
        $status.removeClass('alert-info')
            .addClass('alert-error')
            .html(message)
            .show();
    };

    const localCheckApiKey = function() {
        if ($apiKeyEl.val().length) {
            $pingHolder.show();
        } else {
            $pingHolder.hide();
        }
    };

    localCheckApiKey();
    $apiKeyEl.on('keyup', function() {
        localCheckApiKey();
    });

    $btn.on('click', function(e) {
        e.preventDefault();
        if ($apiKeyEl.valid()) {
            $.getJSON(options.pingUrl, {api_key: $apiKeyEl.val()})
                .then(function(response) {
                    if (_.isUndefined(response.error)) {
                        $status.removeClass('alert-error')
                            .addClass('alert-info')
                            .html(response.msg)
                            .show();
                    } else {
                        onError(response.error);
                    }
                })
                .catch(function(response) {
                    onError(response.responseJSON.error);
                });

            return;
        }
        onError();
    });
};
