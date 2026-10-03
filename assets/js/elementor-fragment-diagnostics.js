(function ($) {
    'use strict';
    $(document).on('click', '.iu-fragment-diagnostic', function () {
        var button = $(this);
        var output = button.parent().find('.iu-fragment-diagnostic-result');
        var panel = window.elementor && elementor.getPanelView().getCurrentPageView();
        var id = panel && panel.model && panel.model.get('id');
        var documentId = window.elementor && elementor.config.document.id;
        if (!id || !documentId) { output.text(iuFragmentDiagnostics.error); return; }
        button.prop('disabled', true);
        output.text(iuFragmentDiagnostics.loading);
        wp.ajax.post('iu_fragment_diagnostics', {
            nonce: iuFragmentDiagnostics.nonce, document_id: documentId, element_id: id
        }).done(function (result) {
            output.text(result.message);
        }).fail(function () {
            output.text(iuFragmentDiagnostics.error);
        }).always(function () {
            button.prop('disabled', false);
        });
    });
})(jQuery);
