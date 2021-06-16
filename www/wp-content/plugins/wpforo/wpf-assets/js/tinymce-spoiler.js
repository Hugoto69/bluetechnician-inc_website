(function () {
    tinymce.create('tinymce.plugins.wpforo_spoiler_button', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init: function (ed, url) {

            function showDialog() {
                var data = {text: ed.selection.getContent({format: 'raw'})};

                ed.windowManager.open({
                    title: 'Insert Spoiler',
                    data: data,
                    body: [{
                        name: 'title',
                        type: 'textbox',
                        label: 'Title'
                    }],
                    onSubmit: function (e) {
                        data = tinymce.extend(data, e.data);
                        ed.insertContent('[spoiler' + (data.title ? ' title="' + data.title + '"' : '') + ']' + data.text + '[/spoiler]', {format: 'bbcode'});
                    }
                });
            }

            ed.addButton('wpf_spoil', {
                icon: 'pluscircle',
                tooltip: 'Spoiler',
                onclick: showDialog
            });
        }
    });

    // Register plugin
    tinymce.PluginManager.add('wpforo_spoiler_button', tinymce.plugins.wpforo_spoiler_button);
})();