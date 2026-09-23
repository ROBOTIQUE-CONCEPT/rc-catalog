(function ($) {
    'use strict';

    function refreshAxes() {
        var count = parseInt($('#wprc-rm-axes-count').val() || '0', 10);
        $('[data-axis-row]').each(function () {
            var axis = parseInt($(this).data('axis-row'), 10);
            $(this).toggle(axis <= count);
        });
    }

    function refreshSeries() {
        var familyId = parseInt($('#wprc-robot-model-family').val() || '0', 10);
        var $series = $('#wprc-robot-model-series');
        if (!$series.length) {
            return;
        }

        $series.find('option[data-parent]').each(function () {
            var $option = $(this);
            var visible = familyId > 0 && parseInt($option.attr('data-parent') || '0', 10) === familyId;
            $option.prop('disabled', !visible).toggle(visible);
        });

        var $selected = $series.find('option:selected');
        if ($selected.data('parent') && parseInt($selected.attr('data-parent') || '0', 10) !== familyId) {
            $series.val('');
        }
    }

    function selectedControllers() {
        var raw = $('#wprc-rm-controllers-list').attr('data-selected') || '[]';
        try {
            return JSON.parse(raw).map(Number);
        } catch (error) {
            return [];
        }
    }

    function refreshControllers() {
        var brandId = parseInt($('#wprc-robot-model-brand').val() || '0', 10);
        var $target = $('#wprc-rm-controllers-list');
        if (!$target.length) {
            return;
        }
        if (!brandId) {
            $target.html('<p class="description">Sélectionnez d’abord une marque.</p>');
            return;
        }

        $.get(wprcRobotModels.ajaxUrl, {
            action: 'wprc_robot_model_controllers_by_brand',
            nonce: wprcRobotModels.nonce,
            brand_id: brandId
        }).done(function (response) {
            var selected = selectedControllers();
            if (!response || !response.success || !Array.isArray(response.data)) {
                return;
            }
            var html = '';
            response.data.forEach(function (item) {
                var checked = selected.indexOf(Number(item.id)) !== -1 ? ' checked' : '';
                var suffix = item.reference ? ' — ' + $('<div>').text(item.reference).html() : '';
                html += '<label class="wprc-rm-check"><input type="checkbox" name="wprc_robot_model_controllers[]" value="' + Number(item.id) + '"' + checked + '> <strong>' + $('<div>').text(item.name).html() + '</strong>' + suffix + '</label>';
            });
            $target.html(html || '<p class="description">Aucun contrôleur actif pour cette marque.</p>');
        });
    }

    function initErpProductSearch($scope) {
        $scope.find('.wprc-erp-product-search').each(function () {
            var $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }
            $select.selectWoo({
                ajax: {
                    url: wprcRobotModels.ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: wprcRobotModels.erpProductAction,
                            q: params.term || '',
                            _ajax_nonce: wprcRobotModels.erpNonce
                        };
                    },
                    processResults: function (data) {
                        var source = wprcRobotModels.erpSource || '';
                        var results = Array.isArray(data) ? data.map(function (item) {
                            var meta = item.meta || {};
                            var reference = meta.reference || meta.sku || '';
                            var name = item.text || String(item.id || '');
                            var text = name;
                            if (reference && text.indexOf(reference) === -1) {
                                text = reference + ' — ' + text;
                            }
                            return {
                                id: source + '::' + String(item.id || '') + '::' + encodeURIComponent(name) + '::' + encodeURIComponent(reference),
                                text: text
                            };
                        }) : [];
                        return {results: results};
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: $select.data('placeholder') || wprcRobotModels.erpProductPlaceholder,
                allowClear: String($select.data('allow-clear') || $select.attr('data-allow-clear') || '') === 'true'
            });
        });
    }

    function addRow(kind) {
        var $tbody = $('.wprc-rm-repeater[data-repeater="' + kind + '"] tbody');
        var template = $('#tmpl-wprc-rm-' + kind + '-row').html();
        if (!$tbody.length || !template) {
            return;
        }
        var index = Date.now();
        var $row = $(template.replace(/__INDEX__/g, index));
        $tbody.append($row);
        initErpProductSearch($row);
    }

    function syncGalleryInput() {
        var ids = [];
        $('[data-wprc-rm-gallery-list] [data-attachment-id]').each(function () {
            ids.push(String($(this).data('attachment-id')));
        });
        $('[data-wprc-rm-gallery-input]').val(ids.join(','));
    }

    function galleryItem(attachment) {
        var url = attachment && attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
        return $('<li>', {
            'class': 'wprc-rm-gallery__item',
            'data-attachment-id': attachment.id
        }).append(
            $('<img>', {src: url, alt: ''}),
            $('<div>').append(
                $('<button>', {type: 'button', 'class': 'button-link', 'data-gallery-up': '', text: '↑'}),
                ' ',
                $('<button>', {type: 'button', 'class': 'button-link', 'data-gallery-down': '', text: '↓'}),
                ' ',
                $('<button>', {type: 'button', 'class': 'button-link-delete', 'data-gallery-remove': '', text: 'Retirer'})
            )
        );
    }

    function openGalleryPicker() {
        if (!window.wp || !wp.media) {
            return;
        }
        var frame = wp.media({
            title: 'Sélectionner les images du modèle',
            button: {text: 'Utiliser les images sélectionnées'},
            library: {type: 'image'},
            multiple: true
        });
        frame.on('select', function () {
            var $list = $('[data-wprc-rm-gallery-list]');
            var existing = {};
            $list.find('[data-attachment-id]').each(function () {
                existing[String($(this).data('attachment-id'))] = true;
            });
            frame.state().get('selection').each(function (model) {
                var attachment = model.toJSON();
                if (!existing[String(attachment.id)]) {
                    $list.append(galleryItem(attachment));
                    existing[String(attachment.id)] = true;
                }
            });
            syncGalleryInput();
        });
        frame.open();
    }

    function activateTab(tab) {
        var $tabs = $('[data-wprc-rm-tabs]');
        if (!$tabs.length) {
            return;
        }
        $tabs.find('[data-rm-tab]').removeClass('is-active').attr('aria-selected', 'false');
        $tabs.find('[data-rm-panel]').removeClass('is-active');
        $tabs.find('[data-rm-tab="' + tab + '"]').addClass('is-active').attr('aria-selected', 'true');
        var $panel = $tabs.find('[data-rm-panel="' + tab + '"]').addClass('is-active');
        initErpProductSearch($panel);
    }

    $(function () {
        refreshAxes();
        refreshSeries();
        initErpProductSearch($('[data-rm-panel].is-active'));

        $(document).on('click', '[data-rm-tab]', function () {
            activateTab(String($(this).data('rm-tab') || 'technical'));
        });

        $('#wprc-rm-axes-count').on('change', refreshAxes);
        $('#wprc-robot-model-family').on('change', refreshSeries);
        $('#wprc-robot-model-brand').on('change', function () {
            $('#wprc-rm-controllers-list').attr('data-selected', '[]');
            refreshControllers();
        });

        $(document).on('click', '.wprc-rm-add-row', function () {
            addRow($(this).data('kind'));
        });
        $(document).on('click', '.wprc-rm-remove-row', function () {
            $(this).closest('tr').remove();
        });
        $(document).on('click', '[data-wprc-rm-gallery-add]', openGalleryPicker);
        $(document).on('click', '[data-gallery-remove]', function () {
            $(this).closest('[data-attachment-id]').remove();
            syncGalleryInput();
        });
        $(document).on('click', '[data-gallery-up]', function () {
            var $item = $(this).closest('[data-attachment-id]');
            var $previous = $item.prev();
            if ($previous.length) {
                $item.insertBefore($previous);
                syncGalleryInput();
            }
        });
        $(document).on('click', '[data-gallery-down]', function () {
            var $item = $(this).closest('[data-attachment-id]');
            var $next = $item.next();
            if ($next.length) {
                $item.insertAfter($next);
                syncGalleryInput();
            }
        });
    });
})(jQuery);
