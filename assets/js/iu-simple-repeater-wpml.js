(function ($) {
    'use strict';
    var taxonomy = window.TaxonomyTranslation;
    if (!taxonomy || !taxonomy.views || !taxonomy.views.TermPopUpView) { return; }
    var view = taxonomy.views.TermPopUpView.prototype;
    var open = view.open;
    var save = view.saveTerm;
    var update = view.updateUI;
    var close = view.close;
    var failed = view.onSaveFailed;

    view.onSaveFailed = function () {
        if (failed) { failed.apply(this, arguments); }
        if (this.iuFields) { this.$el.find('.errors').text(iuRepeaterWPML.error); }
    };

    view.updateUI = function () {
        update.apply(this, arguments);
        if (this.iuLoading) { this.$el.find('.term-save').prop('disabled', true); }
    };

    view.open = function (trid, language) {
        var self = this;
        self.iuLoading = true;
        self.iuFields = null;
        open.apply(self, arguments);
        var original = taxonomy.classes.taxonomy.getOriginalTerm(trid);
        var source = original.get('term_taxonomy_id');
        self.iuSource = source;
        self.$el.find('.errors').text(iuRepeaterWPML.loading);
        self.iuRequest = $.post(ajaxurl, {
            action: 'iu_sr_term_editor', nonce: iuRepeaterWPML.nonce,
            source: source, trid: trid, language: language,
            taxonomy: taxonomy.classes.taxonomy.get('taxonomy')
        }).done(function (response) {
            if (!self.dialog) { return; }
            if (!response.success) {
                self.$el.find('.errors').text(iuRepeaterWPML.error);
                return;
            }
            self.iuFields = response.data;
            Object.keys(response.data).forEach(function (name) {
                var field = response.data[name];
                // WPML places all original meta controls in one shared row.
                self.$el.find('.term-meta').filter(function () { return this.dataset.metaKey === name; }).each(function () {
                    $(this).prev().prev().prev('label').remove();
                    $(this).prev().prev().remove();
                    $(this).prev().remove();
                    $(this).remove();
                });
                var section = $('<div class="iu-wpml-repeater"></div>');
                section.append($('<h4></h4>').text(field.label));
                section.append($('<p></p>').text(iuRepeaterWPML.copied));
                Object.keys(field.units).forEach(function (key) {
                    var unit = field.units[key];
                    var row = $('<div class="wpml-form-row"></div>');
                    var id = 'iu-wpml-' + source + '-' + name + '-' + key;
                    var tag = unit.multiline ? '<textarea rows="4" cols="22"></textarea>' : '<input type="text">';
                    row.append($('<label></label>').attr('for', id).text(unit.label));
                    row.append($(tag).prop('readOnly', true).val(unit.source));
                    row.append($('<button type="button" class="button-copy button-secondary js-button-copy otgs-ico-copy"></button>').attr('title', labels.copyFromOriginal));
                    row.append($(tag).attr('id', id).val(unit.value).attr('data-iu-field', name).attr('data-iu-unit', key));
                    section.append(row);
                });
                self.$el.find('.wpml-dialog-body').append(section);
            });
            self.$el.find('.errors').empty();
            self.iuLoading = false;
            self.updateUI();
        }).fail(function (_, status) {
            if (status !== 'abort' && self.dialog) { self.$el.find('.errors').text(iuRepeaterWPML.error); }
        });
    };

    view.saveTerm = function (event) {
        if (this.iuLoading) { event.preventDefault(); return this; }
        if (this.iuFields && Object.keys(this.iuFields).length) {
            var payload = { nonce: iuRepeaterWPML.nonce, source: this.iuSource, fields: {} };
            var self = this;
            Object.keys(this.iuFields).forEach(function (name) {
                payload.fields[name] = { fingerprint: self.iuFields[name].fingerprint, values: {} };
            });
            this.$el.find('[data-iu-unit]').each(function () {
                payload.fields[this.dataset.iuField].values[this.dataset.iuUnit] = this.value;
            });
            this.$el.find('[data-meta-key="_iu_sr_translation"]').remove();
            $('<input type="hidden" class="term-meta" data-meta-key="_iu_sr_translation">').val(JSON.stringify(payload)).appendTo(this.$el);
        }
        this.$el.find('.errors').empty();
        return save.apply(this, arguments);
    };

    view.close = function () {
        if (this.iuRequest) { this.iuRequest.abort(); this.iuRequest = null; }
        return close.apply(this, arguments);
    };
})(jQuery);
