;(function(window, document, undefined, $, _) {

    var Ponticlaro = window.Ponticlaro = window.Ponticlaro || {};
    var BebopMedia = Ponticlaro.BebopMedia = Ponticlaro.BebopMedia || {};

    var RegenerateButton = BebopMedia.RegenerateButton = (function() {

        var default_config = {
            'api_base_url': '/'+ $('#bebop-media-config').attr(),
            'id'          : null,
            'layout'      : {
                'message'     : false,
                'orientation' : 'horizontal',
                'size'        : 'default'
            },
            'messages'    : {
                'standby' : 'Regenerate',
                'success' : 'Done!',
                'failure' : 'Failed!',
                'loading' : 'Working...'
            }
        };

        var self, 
            $currentTarget;

        return {

            init: function() {

                self = this;

                // Listen to all click events on the editor module
                $('body').on('click', '[bebop-media-regenerate-button]', function(event) {

                    $currentTarget = $(event.target);

                    self.generate($currentTarget);

                    event.preventDefault();
                });
            },

            generate: function($target) {

                console.log($target);

                var self   = this,
                    config = $target.attr('bebop-media-regenerate-button');

                console.log(config);

                // Modify button text
                // $currentTarget.text('Generating...');

                // $.ajax({
                //     url: api_url + id +'/generate-all',
                //     type: 'POST',
                //     dataType: 'json',
                //     success: function(data) {

                //         // Render template
                //         data.id = id;
                //         $item.html(_.template(templates.listItem, data));

                //         // Provide feedback to user
                //         var newText = $currentTarget.text();
                //         $currentTarget.text('Done!').delay(5000).text(newText);
                //     },
                //     error: function(xhr) {

                //         // Provide feedback to user
                //         $currentTarget.text('Error!').delay(5000).text(originalText);
                //     },
                //     complete: function() {
                //         self.cleanState();
                //     }
                // });
            },

            cleanState: function() {
                $currentTarget = null;
            }
        };
    })();

})(window, document, undefined, jQuery, _);
