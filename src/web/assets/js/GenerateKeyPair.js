(function ($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.GenerateKeyPair = Garnish.Base.extend({
        /**
         * the selector for the button that is clicked to generate a new keypair
         */
        $button: null,
        /**
         * the selector for the select input where the key pair is added to the list
         */
        $selectInput: null,
        /**
         * Optional
         * name of the plugin using this feature.
         */
        $plugin: null,

        $spinner: null,
        init: function ($button, $selectInput, $plugin) {
            this.$button = $button;
            this.$selectInput = $selectInput;
            this.$plugin = $plugin;
            this.$spinner = $('<div class="spinner hidden"/>').insertAfter(this.$selectInput.parent());
            this.addListener(this.$button, 'click', 'onClick');
        },
        onClick: function (e) {
            this.$spinner.removeClass('hidden');
            Craft.postActionRequest(
                'keychain/upsert/generate-openssl',
                {
                    plugin: this.$plugin
                },
                $.proxy(function (response, textStatus) {
                    this.$spinner.addClass('hidden');

                    if (textStatus === 'success') {
                        Craft.cp.displayNotice('Key pair created!');
                        //update select
                        this.$selectInput.find('options').prop('selected', false);
                        this.$selectInput.append(
                            $('<option value="' + response.id + '" selected>' + response.description + '</option>')
                        )
                    }
                }, this)
            );
        }
    })
})(jQuery);