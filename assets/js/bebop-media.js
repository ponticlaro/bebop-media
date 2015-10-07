;(function(window, document, undefined, $, _, Backbone) {

    var Ponticlaro = window.Ponticlaro = {};

    var Bebop = window.Ponticlaro.Bebop = window.Ponticlaro.Bebop || {};

    var BebopMedia = Ponticlaro.Bebop.Media = (function() {

        return {

        };
    })();

    var AttachmentEditor = BebopMedia.AttachmentEditor = (function() {

        var self, $body, api_url, templates, $currentTarget;

        return {

            init: function() {

                self = this;

                if (!$body)
                    $body = $('body');

                if (!api_url)
                    api_url = '/'+ $body.find('#bebop-media-config').attr('bebop-media-api-url');

                if (!templates) {
                    templates = {
                        listItem: $body.find('[bebop-media-edit-attachment-template="list-item"]').html()
                    };
                }

                // Listen to all click events on the editor module
                $body.on('click', '[bebop-media-manage-attachment--action]', function(event) {

                    $currentTarget = $(event.target);

                    if ($currentTarget.attr('bebop-media-manage-attachment--action') == 'generate:size') {
                        self.generateSize();
                    }

                    else {
                        self.cleanState();
                    }
                });
            },

            generateSize: function() {

                var self         = this,
                    $item        = $currentTarget.parents('[bebop-media-manage-attachment--list-item]').parent(),
                    id           = $currentTarget.attr('bebop-media-param--id'),
                    size         = $currentTarget.attr('bebop-media-param--size'),
                    originalText = $currentTarget.text();

                // Modify button text
                $currentTarget.text('Generating...');

                $.ajax({
                    url: api_url + id +'/generate/'+ size,
                    type: 'POST',
                    dataType: 'json',
                    success: function(data) {

                        // Render template
                        data.id = id;
                        $item.html(_.template(templates.listItem, data));

                        // Provide feedback to user
                        var newText = $currentTarget.text();
                        $currentTarget.text('Done!').delay(5000).text(newText);
                    },
                    error: function(xhr) {

                        // Provide feedback to user
                        $currentTarget.text('Error!').delay(5000).text(originalText);
                    },
                    complete: function() {
                        self.cleanState();
                    }
                });
            },

            cleanState: function() {
                $currentTarget = null;
            }
        };
    })();

    // On DOM ready
    $(function() {

        AttachmentEditor.init();
    });

})(window, document, undefined, jQuery, _, Backbone);
