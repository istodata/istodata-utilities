/* ISTODATA ACF Post Gallery */
(function($){
    function getIds($field) {
        return $field.find('.iu-acf-post-gallery__input').val().split(',').map(function(id){
            return parseInt(id, 10);
        }).filter(function(id){
            return !isNaN(id) && id > 0;
        });
    }

    function update($field) {
        var ids = [];
        $field.find('.iu-acf-post-gallery__item').each(function(){ ids.push($(this).data('id')); });
        $field.find('.iu-acf-post-gallery__input').val(ids.join(','));
        $field.find('.iu-acf-post-gallery__clear').prop('hidden', !ids.length);
    }

    function item(attachment) {
        var id = attachment.get('id');
        var sizes = attachment.get('sizes') || {};
        var thumb = sizes.thumbnail ? sizes.thumbnail.url : attachment.get('url');
        return $('<li class="iu-acf-post-gallery__item"></li>').attr('data-id', id)
            .append($('<img alt="" />').attr('src', thumb))
            .append($('<button type="button" class="iu-acf-post-gallery__remove" aria-label="Remove image">&times;</button>'));
    }

    function init($field) {
        var $list = $field.find('.iu-acf-post-gallery__list');
        if (!$list.data('ui-sortable')) {
            $list.sortable({ update: function(){ update($field); } });
        }
    }

    $(document).on('click', '.iu-acf-post-gallery__select', function(e){
        e.preventDefault();
        var $field = $(this).closest('.iu-acf-post-gallery');
        var existingIds = getIds($field);
        var maxItems = parseInt($field.data('max-items'), 10) || 0;
        var frame = wp.media({ title: 'Select Images', button: { text: 'Use Images' }, multiple: true, library: { type: 'image' } });

        frame.on('open', function(){
            var selection = frame.state().get('selection');
            existingIds.forEach(function(id){
                var attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add(attachment);
            });
        });

        frame.on('select', function(){
            var $list = $field.find('.iu-acf-post-gallery__list').empty();
            var selected = frame.state().get('selection').toArray();
            if (maxItems) { selected = selected.slice(0, maxItems); }
            selected.forEach(function(attachment){ $list.append(item(attachment)); });
            update($field);
        });
        frame.open();
    });

    $(document).on('click', '.iu-acf-post-gallery__remove', function(){
        var $field = $(this).closest('.iu-acf-post-gallery');
        $(this).closest('.iu-acf-post-gallery__item').remove();
        update($field);
    });

    $(document).on('click', '.iu-acf-post-gallery__clear', function(){
        var $field = $(this).closest('.iu-acf-post-gallery');
        $field.find('.iu-acf-post-gallery__list').empty();
        update($field);
    });

    $(function(){ $('.iu-acf-post-gallery').each(function(){ init($(this)); }); });
    if (window.acf) { window.acf.add_action('append', function($el){ $el.find('.iu-acf-post-gallery').each(function(){ init($(this)); }); }); }
})(jQuery);
