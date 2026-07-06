/* ISTODATA ACF Simple Repeater */
(function($){
    function reindex($field){
        var baseName = $field.data('name');
        $field.find('.iu-acf-simple-repeater__row').each(function(index){
            $(this).find(':input[name]').each(function(){
                var name = $(this).attr('name');
                if (!name) {
                    return;
                }
                name = name.replace(new RegExp(baseName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[[^\\]]+\\]'), baseName + '[' + index + ']');
                $(this).attr('name', name);
            });
        });
    }

    function updateSummary($row){
        var title = $.trim($row.find('.iu-acf-simple-repeater__title').val() || '');
        $row.find('.iu-acf-simple-repeater__summary').text(title || 'Item');
    }

    function initSortable($field){
        var $rows = $field.find('.iu-acf-simple-repeater__rows');
        if (!$rows.length || $rows.data('iu-sortable-ready')) {
            return;
        }

        $rows.sortable({
            items: '> .iu-acf-simple-repeater__row',
            handle: '.iu-acf-simple-repeater__handle',
            update: function(){
                reindex($field);
            }
        });
        $rows.data('iu-sortable-ready', true);
    }

    function init(context){
        $(context || document).find('.iu-acf-simple-repeater').each(function(){
            initSortable($(this));
        });
    }

    $(document).on('click', '.iu-acf-simple-repeater__add', function(e){
        e.preventDefault();
        var $field = $(this).closest('.iu-acf-simple-repeater');
        var template = $field.find('.iu-acf-simple-repeater__template').html();
        if (!template) {
            return;
        }

        var index = $field.find('.iu-acf-simple-repeater__row').length;
        var html = template.replace(/__i__/g, index);
        var $row = $(html);
        $field.find('.iu-acf-simple-repeater__rows').append($row);
        updateSummary($row);
        initSortable($field);
    });

    $(document).on('click', '.iu-acf-simple-repeater__remove', function(e){
        e.preventDefault();
        var $field = $(this).closest('.iu-acf-simple-repeater');
        $(this).closest('.iu-acf-simple-repeater__row').remove();
        reindex($field);
    });

    $(document).on('input', '.iu-acf-simple-repeater__title', function(){
        updateSummary($(this).closest('.iu-acf-simple-repeater__row'));
    });

    $(document).on('click', '.iu-acf-simple-repeater__select-image', function(e){
        e.preventDefault();
        var $media = $(this).closest('.iu-acf-simple-repeater__media');
        var frame = wp.media({
            title: 'Select Image',
            button: { text: 'Use Image' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function(){
            var attachment = frame.state().get('selection').first();
            if (!attachment) {
                return;
            }

            var data = attachment.toJSON();
            var thumb = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
            $media.find('.iu-acf-simple-repeater__image-id').val(data.id);
            $media.find('.iu-acf-simple-repeater__preview img').attr('src', thumb);
            $media.find('.iu-acf-simple-repeater__preview').prop('hidden', false);
            $media.find('.iu-acf-simple-repeater__clear-image').prop('hidden', false);
        });

        frame.open();
    });

    $(document).on('click', '.iu-acf-simple-repeater__clear-image', function(e){
        e.preventDefault();
        var $media = $(this).closest('.iu-acf-simple-repeater__media');
        $media.find('.iu-acf-simple-repeater__image-id').val('');
        $media.find('.iu-acf-simple-repeater__preview img').attr('src', '');
        $media.find('.iu-acf-simple-repeater__preview').prop('hidden', true);
        $(this).prop('hidden', true);
    });

    $(function(){
        init(document);
    });

    if (window.acf) {
        window.acf.addAction('append', function($el){
            init($el);
        });
    }
})(jQuery);
