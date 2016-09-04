jQuery(document).ready(function ($) {
    BPFP_WIDGETS.richcontent.init();
    BPFP_WIDGETS.groups_ui.init();
    BPFP_WIDGETS.manage.init();
});

BPFP_WIDGETS.manage = {};
(function (me, window, $) {
    var _l = {};

    me.getElements = function () {
        _l.available_widgets = $('.bpfp_available_widgets');
        if (_l.available_widgets.length == 0)
            return false;

        _l.added_widgets = $('.bpfp_added_widgets');
        return true;
    };

    me.init = function () {
        //bind 'enable custom front page' checkbox event
        //we need this only on user profiles
        if ($('body').hasClass('bp-user')) {
            $('input[name="_has_custom_frontpage"]').change(function () {
                me.toggleFPStatus($(this));
            });
        }

        if (!me.getElements())
            return;

        _l.available_widgets.find('.lnk_add_widget').click(function (e) {
            e.preventDefault();
            //1. clone this widget and add to added-widgets area
            $clone = $(this).closest('.bpfp_widget').clone();
            $clone.removeClass('preview').addClass('setting').addClass('expanded');
            $clone.find('.lnk_add_widget').remove();
            $clone.appendTo(_l.added_widgets);

            //2. scroll to new widget
            $('html, body').animate({
                scrollTop: $clone.offset().top
            }, 1000);

            //3. make sure 'no widgets added' message is now hidden
            _l.added_widgets.find('.no_widgets_message').hide();

            //4. fire up ajax to load widget settings html
            me.initWidget($clone);
        });

        _l.added_widgets.find('.bpfp_widget').each(function () {
            me.bindEvents($(this));
            $(document).trigger('bpfp_on_init', [$(this)]);
        });

        _l.added_widgets.sortable({
            items: "> .bpfp_widget",
            handle: ".widget_name",
            /*containment:    "parent", this is better, but can't use it becuase there's no extra space( padding ) for widgets to move*/
            update: function (event, ui) {
                me.updateWidgetsOrder();
            },
        });
    };

    me.initWidget = function ($widget) {
        $widget.addClass('loading');

        var data = {
            'action': 'bpfp_widget_init',
            'type': $widget.data('type'),
            'nonce': BPFP_WIDGETS.config.nonce.init
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: function (response) {
                response = $.parseJSON(response);

                if (response.status) {
                    $new_widget = $(response.html);
                    $widget.replaceWith($new_widget);

                    me.bindEvents($new_widget);
                    $(document).trigger('bpfp_on_init', [$new_widget]);
                } else {
                    if (response.message) {
                        alert(response.message);
                    }
                }

                if (response.newnonce) {
                    BPFP_WIDGETS.config.nonce.init = response.newnonce;
                }
            }
        });
    };

    me.bindEvents = function ($widget) {
        //toggle widget details
        $widget.find('.lnk_toggle_widget_details').click(function (e) {
            e.preventDefault();
            $widget.toggleClass('expanded').toggleClass('collapsed');
            $widget.find('.widget_description').slideToggle();
        });

        //delete widget
        me.handleDelete($widget);

        //update widget
        me.handleUpdate($widget);
    };

    me.handleDelete = function ($widget) {
        $widget.find('.lnk_delete_widget').click(function (e) {
            e.preventDefault();

            if (!confirm(BPFP_WIDGETS.translations.confirm_delete))
                return;

            var data = {
                'action': 'bpfp_widget_delete',
                'id': $widget.find('[name="widget_id"]').val(),
                'nonce': BPFP_WIDGETS.config.nonce.delete
            };

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: data,
                success: function (response) {
                    //nothing to do
                    response = $.parseJSON(response);
                    if (response.newnonce) {
                        BPFP_WIDGETS.config.nonce.delete = response.newnonce;
                    }
                }
            });

            $widget.slideUp(500, function () {
                $widget.remove();
                //Maybe, show 'no widgets added' message
                if (_l.added_widgets.find('.bpfp_widget').length < 1) {
                    _l.added_widgets.find('.no_widgets_message').show();
                }
            });
        });
    };

    me.handleUpdate = function ($widget) {
        $form = $widget.find('form');

        var options = {
            beforeSerialize: function () {

            },
            beforeSubmit: function () {
                $widget.find('.response').remove();
                $widget.addClass('loading');
            },
            success: function (response, status) {
                response = $.parseJSON(response);
                if (response.status) {
                    $new_widget = $(response.html);
                    $new_widget.addClass('loading');
                    $widget.replaceWith($new_widget);

                    me.bindEvents($new_widget);
                    $(document).trigger('bpfp_on_init', [$new_widget]);

                    $loading_overlay = $new_widget.find('.loading_overlay');
                    $loading_overlay.find('.img_loading').hide();
                    $loading_overlay.find('.img_success').show();

                    t = setTimeout(function () {
                        $new_widget.removeClass('loading');
                        $loading_overlay.find('.img_loading').show();
                        $loading_overlay.find('.img_success').hide();
                        $new_widget.find('form').append("<div class='response alert alert-success'>" + response.message + "</div>");
                    }, 2000);
                } else {
                    $form.append("<div class='response alert alert-error'>" + response.message + "</div>");
                    $widget.removeClass('loading');
                }
            }
        };
        $form.ajaxForm(options);
    };

    me.updateWidgetsOrder = function () {
        var widget_ids = [],
                widget_ids_csv = '';

        $(_l.added_widgets.find('.bpfp_widget')).each(function () {
            widget_ids.push($(this).find('form [name="widget_id"]').val());
        });

        if (widget_ids.length > 0) {
            widget_ids_csv = widget_ids.join(',');
        } else {
            return false;
        }

        var data = {
            'action': 'bpfp_widgets_reorder',
            'ids': widget_ids_csv,
            'nonce': BPFP_WIDGETS.config.nonce.reorder
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: function (response) {
                //nothing to do
            }
        });
    };

    me.toggleFPStatus = function ($checkbox) {
        var enabled = 0;

        if ($checkbox.is(':checked')) {
            enabled = 1;
        }

        if (enabled) {
            $('.hcfp_dependent').show();
        } else {
            $('.hcfp_dependent').hide();
        }

        var data = {
            'action': 'bpfp_change_status',
            'updated_status': enabled,
            'nonce': BPFP_WIDGETS.config.nonce.change_status
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: function (response) {
                response = $.parseJSON(response);
                if (response.newnonce) {
                    BPFP_WIDGETS.config.nonce.change_status = response.newnonce;
                }
            }
        });
    };

    me.initGroupWidgetsUI = function () {
        //show group description on top?
    };

})(BPFP_WIDGETS.manage, window, window.jQuery);

BPFP_WIDGETS.richcontent = {};
(function (me, window, $) {
    me.init = function () {
        $(document).on('bpfp_on_init', function (e, $widget) {
            if ($widget.find('[name="widget_type"]').val() != 'richcontent')
                return;

            //init visual editor
            $widget.find('textarea').trumbowyg();
        });
    };
})(BPFP_WIDGETS.richcontent, window, window.jQuery);

BPFP_WIDGETS.groups_ui = {};
(function (me, window, $) {
    me.init = function () {
        if ($('body').hasClass('single-item') && $('body').hasClass('groups')) {
            if ($('#bpfp_widgets_ui_wrapper').length > 0) {
                me.initGroupWidgetsUI();
                return false;
            }
        }
    };

    me.initGroupWidgetsUI = function () {
        var $temp_container = $('#bpfp_widgets_ui_wrapper');
        var $parent_form = $temp_container.closest('form');

        $parent_form.after($temp_container);

        var data = {
            action: BPFP_WIDGETS.config.action.init_group,
            nonce: BPFP_WIDGETS.config.nonce.init_group
        };

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: data,
            success: function (response) {
                $temp_container.find('#bpfp_widgets_ui').html(response);
                $temp_container.removeClass('loading');
                BPFP_WIDGETS.manage.init();
            }
        });
    };
})(BPFP_WIDGETS.groups_ui, window, window.jQuery);