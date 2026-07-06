/* ISTODATA Gallery Metabox JS */
(function($){
    function refreshHiddenInput($list, $input){
        var ids = [];
        $list.find('.iu-isto-gallery-item').each(function(){
            var id = $(this).data('id');
            if (id) {
                ids.push(id);
            }
        });
        $input.val(ids.join(','));
    }

    function getCurrentIds($input){
        var raw = ($input.val() || '').toString().trim();
        if (!raw) {
            return [];
        }

        return raw.split(',').map(function(id){
            return parseInt(id, 10);
        }).filter(function(id){
            return !isNaN(id) && id > 0;
        });
    }

    function preloadSelection(frame, ids){
        if (!frame || !ids.length) {
            return;
        }

        var selection = frame.state().get('selection');
        selection.reset();

        ids.forEach(function(id){
            var attachment = wp.media.attachment(id);
            attachment.fetch();
            selection.add(attachment);
        });
    }

    $(document).on('click', '#iu_isto_gallery_select', function(e){
        e.preventDefault();

        var $input = $('#iu_isto_gallery_ids');
        var existingIds = getCurrentIds($input);
        var frame = wp.media({
            title: 'Επιλογή εικόνων',
            button: { text: 'Χρήση εικόνων' },
            multiple: true,
            library: { type: 'image' }
        });

        frame.on('open', function(){
            preloadSelection(frame, existingIds);
        });

        frame.on('select', function(){
            var selection = frame.state().get('selection');
            var $list = $('#iu_isto_gallery_list');
            var $input = $('#iu_isto_gallery_ids');

            $list.empty();

            selection.each(function(attachment){
                var id = attachment.get('id');
                var thumb = attachment.get('sizes') && attachment.get('sizes').thumbnail ? attachment.get('sizes').thumbnail.url : attachment.get('url');
                var $li = $('<li class="iu-isto-gallery-item" style="width:90px; position:relative; cursor:move;"></li>').attr('data-id', id);

                $li.append($('<img />').attr('src', thumb).css({ width:'100%', height:'auto', display:'block', border:'1px solid #ccd0d4', borderRadius:'2px'}));
                $li.append($('<a class="iu-isto-remove" href="#" title="Αφαίρεση">×</a>').css({position:'absolute', top:'4px', right:'4px', background:'#b32d2e', color:'#fff', textDecoration:'none', borderRadius:'2px', padding:'0 5px', lineHeight:'20px'}));
                $list.append($li);
            });

            refreshHiddenInput($list, $input);
        });

        frame.open();
    });

    $(document).on('click', '#iu_isto_gallery_clear', function(e){
        e.preventDefault();
        $('#iu_isto_gallery_list').empty();
        $('#iu_isto_gallery_ids').val('');
    });

    $(document).on('click', '#iu_isto_gallery_list .iu-isto-remove', function(e){
        e.preventDefault();
        var $item = $(this).closest('.iu-isto-gallery-item');
        $item.remove();
        refreshHiddenInput($('#iu_isto_gallery_list'), $('#iu_isto_gallery_ids'));
    });

    $(function(){
        var $list = $('#iu_isto_gallery_list');
        if ($list.length) {
            $list.sortable({
                items: '> .iu-isto-gallery-item',
                update: function(){
                    refreshHiddenInput($list, $('#iu_isto_gallery_ids'));
                }
            });
        }
    });
})(jQuery);

