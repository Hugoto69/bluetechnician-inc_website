(function () {
  tinymce.create('tinymce.plugins.wpforo_pre_button', {
    init: function (ed, url) {
      function showDialog() {
        ed.windowManager.open({
          title: "Code",
          body: {
            type: 'textbox',
            value: ed.selection.getContent({format: 'text'}),
            name: 'preformatted',
            multiline: true,
            minWidth: ed.getParam("code_dialog_width", 600),
            minHeight: ed.getParam("code_dialog_height", Math.min(tinymce.DOM.getViewPort().h - 200, 500)),
            spellcheck: false,
            style: 'direction: ltr; text-align: left'
          },
          onSubmit: function (e) {
            ed.focus();
            ed.undoManager.transact(function () {
              ed.insertContent('<pre contenteditable="false">' + escapeHtml(e.data.preformatted) + '</pre>');
            });
            ed.selection.setCursorLocation();
            ed.nodeChanged();
          }
        });
      }
      function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
      }
      ed.addButton('pre', {
        title: 'Code',
        icon: 'code',
        onclick: showDialog
      });

      ed.addShortcut('ctrl+0','Format Code', showDialog);

      ed.on('dblclick', function(e){
        if( e.target.tagName === 'PRE' && 'false' === e.target.getAttribute('contenteditable') ){
          showDialog();
        }
      });

      ed.on('Dirty ExecCommand KeyPress SetContent', function(e) {
        ed.dom.select('pre').forEach(function(pre){
          pre.setAttribute('contenteditable', 'false');
        });
      });

    }
  });
  tinymce.PluginManager.add('wpforo_pre_button', tinymce.plugins.wpforo_pre_button);
})();