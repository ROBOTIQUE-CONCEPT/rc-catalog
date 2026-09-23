(function (wp, config) {
    'use strict';

    if (!wp || !wp.blocks || !wp.element || !wp.components || !wp.blockEditor) {
        return;
    }

    const el = wp.element.createElement;
    const registerBlockType = wp.blocks.registerBlockType;
    const SelectControl = wp.components.SelectControl;
    const Placeholder = wp.components.Placeholder;
    const useBlockProps = wp.blockEditor.useBlockProps;
    const forms = config && Array.isArray(config.forms) ? config.forms : [];

    registerBlockType('wprc-leads/form', {
        apiVersion: 3,
        title: config && config.title ? config.title : 'Formulaire RConcept',
        description: config && config.description ? config.description : '',
        icon: 'feedback',
        category: 'widgets',
        attributes: {
            formSlug: {
                type: 'string',
                default: ''
            }
        },
        edit: function (props) {
            const blockProps = useBlockProps({ className: 'wprc-leads-form-block-editor' });
            const selected = props.attributes.formSlug || '';
            const selectedOption = forms.find(function (option) {
                return option.value === selected;
            });

            return el(
                'div',
                blockProps,
                el(
                    Placeholder,
                    {
                        icon: 'feedback',
                        label: config && config.title ? config.title : 'Formulaire RConcept',
                        instructions: selectedOption && selectedOption.value
                            ? selectedOption.label
                            : (config && config.description ? config.description : '')
                    },
                    el(SelectControl, {
                        label: 'Formulaire',
                        value: selected,
                        options: forms,
                        onChange: function (value) {
                            props.setAttributes({ formSlug: value || '' });
                        }
                    })
                )
            );
        },
        save: function () {
            return null;
        }
    });
})(window.wp, window.WPRCLeadsFormBlock || {});
