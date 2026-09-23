jQuery(function ($) {
    'use strict';

    var selectConfigs = [
        {selector: '#wprc_product_erp_id', action: wprc_admin_params.actions.products, placeholder: wprc_admin_params.placeholders.products},
        {selector: '#wprc_product_location_id', action: wprc_admin_params.actions.companies, placeholder: wprc_admin_params.placeholders.companies},
        {selector: '#wprc_product_retailer_id', action: wprc_admin_params.actions.companies, placeholder: wprc_admin_params.placeholders.companies},
        {selector: '#wprc_product_shipping_erp_id', action: wprc_admin_params.actions.products, placeholder: wprc_admin_params.placeholders.products},
        {selector: '#wprc_product_shipping_standard_erp_id', action: wprc_admin_params.actions.products, placeholder: wprc_admin_params.placeholders.products},
        {selector: '#wprc_product_shipping_express_erp_id', action: wprc_admin_params.actions.products, placeholder: wprc_admin_params.placeholders.products}
    ];

    function labelInput($select) {
        return $('#' + $select.attr('id') + '__label');
    }

    function syncSelectedLabel($select, item) {
        var text = item && item.text ? String(item.text) : '';
        $select.attr('data-selected-text', text);
        labelInput($select).val(text);
    }

    function syncErpSource($select, hasValue) {
        var map = {
            'wprc_product_shipping_erp_id': 'wprc_product_shipping_erp_source',
            'wprc_product_shipping_standard_erp_id': 'wprc_product_shipping_standard_erp_source',
            'wprc_product_shipping_express_erp_id': 'wprc_product_shipping_express_erp_source'
        };
        var sourceId = map[$select.attr('id')];
        if (sourceId) {
            $('#' + sourceId).val(hasValue ? (wprc_admin_params.erp_source || '') : '');
        }
    }

    function initAjaxSelect($select, cfg) {
        if (!$select.length || $select.prop('type') !== 'select-one') {
            return;
        }

        var selectedId = String($select.attr('data-selected') || '');
        var selectedText = String($select.attr('data-selected-text') || '');
        if (selectedId && selectedText && !$select.find('option[value="' + selectedId.replace(/"/g, '\\"') + '"]').length) {
            $select.append(new Option(selectedText, selectedId, true, true));
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.selectWoo('destroy').removeClass('enhanced');
        }

        $select.selectWoo({
            ajax: {
                url: wprc_admin_params.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        action: cfg.action,
                        q: params.term || '',
                        _ajax_nonce: wprc_admin_params.nonce
                    };
                },
                processResults: function (data) {
                    return {results: Array.isArray(data) ? data : []};
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: cfg.placeholder,
            allowClear: true
        });

        $select.on('select2:select', function (event) {
            var data = event.params && event.params.data ? event.params.data : {};
            syncSelectedLabel($(this), data);
            syncErpSource($(this), !!data.id);
        });

        $select.on('select2:clear change', function () {
            if (!$(this).val()) {
                syncSelectedLabel($(this), null);
                syncErpSource($(this), false);
            }
        });
    }

    function addressSelect() { return $('#wprc_product_location_address_id'); }
    function addressLabelInput() { return $('#wprc_product_location_address_id__label'); }

    function clearAddressMeta() {
        $('#_wprc_product_location_address_zip').val('');
        $('#_wprc_product_location_address_country').val('');
        $('#_wprc_product_location_address_country_code').val('');
    }

    function applyAddressData(item) {
        var data = item || {};
        var meta = data.meta || {};
        var text = data.id && data.text ? String(data.text) : '';

        addressLabelInput().val(text);
        $('#_wprc_product_location_address_zip').val(meta.zip_code || meta.postal_code || '');
        $('#_wprc_product_location_address_country').val(meta.country || '');
        $('#_wprc_product_location_address_country_code').val(meta.country_code || '');
    }

    function initAddressSelect() {
        var $select = addressSelect();
        if (!$select.length) {
            return;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.selectWoo('destroy').removeClass('enhanced');
        }

        $select.selectWoo({
            ajax: {
                url: wprc_admin_params.ajax_url,
                dataType: 'json',
                delay: 150,
                data: function () {
                    return {
                        action: wprc_admin_params.actions.addresses,
                        location_id: $('#wprc_product_location_id').val() || '',
                        _ajax_nonce: wprc_admin_params.nonce
                    };
                },
                processResults: function (items) {
                    return {results: Array.isArray(items) ? items : []};
                },
                cache: true
            },
            minimumInputLength: 0,
            placeholder: wprc_admin_params.placeholders.addresses,
            allowClear: true
        });

        $select.on('select2:opening', function (event) {
            if (!$('#wprc_product_location_id').val()) {
                event.preventDefault();
            }
        });

        $select.on('select2:select', function (event) {
            applyAddressData(event.params && event.params.data ? event.params.data : null);
        });

        $select.on('select2:clear', function () {
            addressLabelInput().val('');
            clearAddressMeta();
        });
    }

    selectConfigs.forEach(function (cfg) {
        initAjaxSelect($(cfg.selector), cfg);
    });
    initAddressSelect();

    $(document).on('change', '#wprc_product_location_id', function () {
        var $select = addressSelect();
        $select.find('option[value!=""]').remove();
        $select.val('').trigger('change');
        addressLabelInput().val('');
        clearAddressMeta();
        $('#wprc_product_location_address_id_field').toggle(!!$(this).val());
    });

    function loadRobotControllers(modelId, selectedId) {
        var $select = $('#wprc_product_robot_controller_id');
        if (!$select.length) {
            return;
        }
        $select.empty().append(new Option('— Sélectionner un contrôleur —', '', false, false));
        if (!modelId) {
            $select.val('').trigger('change');
            return;
        }
        $.ajax({
            url: wprc_admin_params.ajax_url,
            method: 'GET',
            dataType: 'json',
            data: {
                action: wprc_admin_params.actions.robotControllers,
                model_id: modelId,
                _ajax_nonce: wprc_admin_params.nonce
            }
        }).done(function (items) {
            (items || []).forEach(function (item) {
                var selected = selectedId && String(item.id) === String(selectedId);
                $select.append(new Option(item.text, item.id, selected, selected));
            });
            $select.trigger('change');
        });
    }

    $(document).on('change', '#wprc_product_robot_model_entity_id', function () {
        loadRobotControllers($(this).val() || '', '');
    });
});
