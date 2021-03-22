export default class WpGutenberg {
    init() {
        // this has to be left out (because of https://github.com/WordPress/gutenberg/issues/9757)
        //wp.domReady(() => {
        this.initAltLng();
        this.initPreventLngs();
        //});
    }

    initAltLng() {
        wp.hooks.addFilter('blocks.registerBlockType', 'custom/attrs', settings => {
            settings.attributes = {
                ...settings.attributes,
                gtbabel_alt_lng: {
                    type: 'string',
                    default: ''
                },
                gtbabel_hide_block_target: {
                    type: 'boolean',
                    default: ''
                }
            };
            return settings;
        });

        wp.hooks.addFilter(
            'editor.BlockEdit',
            'custom/attrinputs',
            wp.compose.createHigherOrderComponent(
                BlockEdit => props => {
                    let options = [];
                    options.push({ value: null, label: wp.i18n.__('Page language', 'gtbabel-plugin') });
                    for (const [languages__key, languages__value] of Object.entries(
                        wpgutenberg_data.languages_with_source_at_last
                    )) {
                        options.push({ value: languages__key, label: languages__value });
                    }
                    return wp.element.createElement(
                        wp.element.Fragment,
                        null,
                        wp.element.createElement(BlockEdit, props),
                        wp.element.createElement(
                            wp.blockEditor.InspectorControls,
                            null,
                            wp.element.createElement(
                                wp.components.PanelBody,
                                {
                                    title: wp.i18n.__('Languages', 'gtbabel-plugin')
                                },
                                wp.element.createElement(wp.components.SelectControl, {
                                    label: wp.i18n.__('Source language', 'gtbabel-plugin'),
                                    value: props.attributes.gtbabel_alt_lng,
                                    onChange: val => props.setAttributes({ gtbabel_alt_lng: val }),
                                    options: options
                                }),
                                wp.element.createElement(wp.components.ToggleControl, {
                                    label: wp.i18n.__('Show only there', 'gtbabel-plugin'),
                                    checked: props.attributes.gtbabel_hide_block_target === true,
                                    onChange: val => props.setAttributes({ gtbabel_hide_block_target: val })
                                })
                            )
                        )
                    );
                },
                'withcustomattrinputs'
            )
        );
    }

    initPreventLngs() {
        wp.plugins.registerPlugin('my-plugin-sidebar', {
            render: () => {
                return wp.element.createElement(
                    wp.editPost.PluginDocumentSettingPanel,
                    {
                        title: wp.i18n.__('Language', 'gtbabel-plugin'),
                        name: 'gtbabel-gutenberg-sidebar',
                        icon: 'admin-site-alt3'
                    },
                    wp.element.createElement(
                        wp.compose.compose(
                            wp.data.withDispatch(dispatch => {
                                return {
                                    set_gtbabel_alt_lng: value => {
                                        dispatch('core/editor').editPost({
                                            meta: { gtbabel_alt_lng: value }
                                        });
                                    },
                                    set_gtbabel_prevent_lngs: value => {
                                        dispatch('core/editor').editPost({
                                            meta: { gtbabel_prevent_lngs: value }
                                        });
                                    },
                                    set_slug: value => {
                                        dispatch('core/editor').editPost({
                                            slug: value
                                        });
                                    }
                                };
                            }),
                            wp.data.withSelect(select => {
                                return {
                                    get_gtbabel_alt_lng:
                                        select('core/editor').getEditedPostAttribute('meta')['gtbabel_alt_lng'] ?? '',
                                    get_gtbabel_prevent_lngs:
                                        select('core/editor').getEditedPostAttribute('meta')['gtbabel_prevent_lngs'] ??
                                        '',
                                    get_status: select('core/editor').getEditedPostAttribute('status') ?? '',
                                    get_slug: select('core/editor').getEditedPostSlug()
                                };
                            })
                        )(props => {
                            if (
                                wpgutenberg_data.prevent_publish_wp_new_posts == true &&
                                props.get_gtbabel_prevent_lngs == '' &&
                                (props.get_slug == '' || !isNaN(props.get_slug))
                            ) {
                                props.set_gtbabel_prevent_lngs(
                                    ',' +
                                        wpgutenberg_data.languages_without_source.map(i => i + '_guest').join(',') +
                                        ','
                                );
                            }

                            let elements = [];

                            if (props.get_slug == '' || !isNaN(props.get_slug)) {
                                return wp.element.createElement('div', null, elements);
                            }

                            let options = [];
                            for (const [languages__key, languages__value] of Object.entries(
                                wpgutenberg_data.languages
                            )) {
                                options.push({
                                    value: languages__key === wpgutenberg_data.source_lng ? '' : languages__key,
                                    label: languages__value
                                });
                            }
                            elements.push(
                                wp.element.createElement(wp.components.BaseControl, {
                                    help:
                                        'gtbabel_alt_lng: ' +
                                        props.get_gtbabel_alt_lng +
                                        ', gtbabel_prevent_lngs: ' +
                                        props.get_gtbabel_prevent_lngs
                                })
                            );
                            elements.push(
                                wp.element.createElement(wp.components.SelectControl, {
                                    label: wp.i18n.__('Source language', 'gtbabel-plugin'),
                                    value: props.get_gtbabel_alt_lng,
                                    options: options,
                                    disabled: props.get_gtbabel_alt_lng != '' || props.get_status === 'publish',
                                    onChange: content => {
                                        props.set_gtbabel_alt_lng(content);

                                        // swap prevent lng settings for source and target
                                        let val = props.get_gtbabel_prevent_lngs;
                                        if (
                                            val.includes(',' + wpgutenberg_data.source_lng + ',') ||
                                            val.includes(',' + wpgutenberg_data.source_lng + '_guest,')
                                        ) {
                                            val = val.replace(',' + wpgutenberg_data.source_lng + ',', ',SWAP,');
                                            val = val.replace(
                                                ',' + wpgutenberg_data.source_lng + '_guest,',
                                                ',SWAP_guest,'
                                            );
                                        }
                                        if (
                                            val.includes(',' + content + ',') ||
                                            val.includes(',' + content + '_guest,')
                                        ) {
                                            val = val.replace(
                                                ',' + content + ',',
                                                ',' + wpgutenberg_data.source_lng + ','
                                            );
                                            val = val.replace(
                                                ',' + content + '_guest,',
                                                ',' + wpgutenberg_data.source_lng + '_guest,'
                                            );
                                        }
                                        if (val.includes(',SWAP,') || val.includes(',SWAP_guest,')) {
                                            val = val.replace(',SWAP,', ',' + content + ',');
                                            val = val.replace(',SWAP_guest,', ',' + content + '_guest,');
                                        }
                                        props.set_gtbabel_prevent_lngs(val);

                                        if (content != '') {
                                            wp.apiFetch({
                                                path: '/v1/translate/slug',
                                                method: 'POST',
                                                body: JSON.stringify({
                                                    slug: props.get_slug,
                                                    lng_source: content,
                                                    lng_target: wpgutenberg_data.source_lng
                                                }),
                                                cache: 'no-cache',
                                                headers: {
                                                    'content-type': 'application/json'
                                                }
                                            }).then(response => {
                                                props.set_slug(response.data.slug);
                                                // saving the post is very important afterwards
                                                // this stores the meta data and updates the preview link properly
                                                wp.data.dispatch('core/editor').savePost();
                                            });
                                        } else {
                                            wp.data.dispatch('core/editor').savePost();
                                        }
                                    }
                                })
                            );

                            elements.push(
                                wp.element.createElement(wp.components.BaseControl, {
                                    label: wp.i18n.__('Availability', 'gtbabel-plugin'),
                                    help: wp.i18n.__(
                                        'Here you can control whether this page should be translated in all or only in a few languages.',
                                        'gtbabel-plugin'
                                    )
                                })
                            );
                            for (const [languages__key, languages__value] of Object.entries(
                                wpgutenberg_data.languages
                            ).sort((a, b) => {
                                if (
                                    (props.get_gtbabel_alt_lng == '' && wpgutenberg_data.source_lng == a[0]) ||
                                    props.get_gtbabel_alt_lng == a[0]
                                ) {
                                    return 1;
                                }
                                if (
                                    (props.get_gtbabel_alt_lng == '' && wpgutenberg_data.source_lng == b[0]) ||
                                    props.get_gtbabel_alt_lng == b[0]
                                ) {
                                    return -1;
                                }
                                return 0;
                            })) {
                                elements.push(
                                    wp.element.createElement(
                                        wp.components.BaseControl,
                                        null,
                                        wp.element.createElement(
                                            wp.components.Flex,
                                            null,
                                            wp.element.createElement(
                                                wp.components.FlexBlock,
                                                null,
                                                wp.element.createElement(
                                                    wp.components.ButtonGroup,
                                                    null,
                                                    wp.element.createElement(wp.components.Button, {
                                                        icon: 'no-alt',
                                                        isPrimary: props.get_gtbabel_prevent_lngs.includes(
                                                            ',' + languages__key + ','
                                                        ),
                                                        onClick: () => {
                                                            let val = props.get_gtbabel_prevent_lngs;
                                                            if (val.includes(',' + languages__key + '_guest,')) {
                                                                val = val.replace('' + languages__key + '_guest,', '');
                                                            }
                                                            if (!val.includes(',' + languages__key + ',')) {
                                                                val +=
                                                                    (!val.includes(',') ? ',' : '') +
                                                                    '' +
                                                                    languages__key +
                                                                    ',';
                                                            }
                                                            props.set_gtbabel_prevent_lngs(val);
                                                        }
                                                    }),
                                                    wp.element.createElement(wp.components.Button, {
                                                        icon: 'hidden',
                                                        isPrimary: props.get_gtbabel_prevent_lngs.includes(
                                                            ',' + languages__key + '_guest,'
                                                        ),
                                                        onClick: () => {
                                                            let val = props.get_gtbabel_prevent_lngs;

                                                            if (val.includes(',' + languages__key + ',')) {
                                                                val = val.replace('' + languages__key + ',', '');
                                                            }
                                                            if (!val.includes(',' + languages__key + '_guest,')) {
                                                                val +=
                                                                    (!val.includes(',') ? ',' : '') +
                                                                    '' +
                                                                    languages__key +
                                                                    '_guest,';
                                                            }
                                                            props.set_gtbabel_prevent_lngs(val);
                                                        }
                                                    }),
                                                    wp.element.createElement(wp.components.Button, {
                                                        icon: 'yes',
                                                        isPrimary:
                                                            !props.get_gtbabel_prevent_lngs.includes(
                                                                ',' + languages__key + ','
                                                            ) &&
                                                            !props.get_gtbabel_prevent_lngs.includes(
                                                                ',' + languages__key + '_guest,'
                                                            ),
                                                        onClick: () => {
                                                            let val = props.get_gtbabel_prevent_lngs;

                                                            if (val.includes(',' + languages__key + ',')) {
                                                                val = val.replace('' + languages__key + ',', '');
                                                            }
                                                            if (val.includes(',' + languages__key + '_guest,')) {
                                                                val = val.replace('' + languages__key + '_guest,', '');
                                                            }
                                                            if (val == ',') {
                                                                val = '';
                                                            }
                                                            props.set_gtbabel_prevent_lngs(val);
                                                        }
                                                    })
                                                )
                                            ),
                                            wp.element.createElement(
                                                wp.components.FlexBlock,
                                                null,
                                                wp.element.createElement('div', {
                                                    dangerouslySetInnerHTML: {
                                                        __html: languages__value
                                                    }
                                                })
                                            )
                                        )
                                    )
                                );
                            }

                            let links = [];
                            for (const [languages__key, languages__value] of Object.entries(
                                wpgutenberg_data.languages_with_source_at_last
                            )) {
                                if (
                                    (props.get_gtbabel_alt_lng == '' &&
                                        wpgutenberg_data.source_lng == languages__key) ||
                                    props.get_gtbabel_alt_lng == languages__key
                                ) {
                                    continue;
                                }
                                if (props.get_gtbabel_prevent_lngs.includes(',' + languages__key + ',')) {
                                    continue;
                                }
                                links.push(
                                    '<li><a href="' +
                                        wpgutenberg_data.translation_url +
                                        '&lng=' +
                                        languages__key +
                                        '">' +
                                        languages__value +
                                        '</a></li>'
                                );
                            }
                            if (links.length > 0) {
                                elements.push(
                                    wp.element.createElement(wp.components.BaseControl, {
                                        label: 'Übersetzungen'
                                    })
                                );
                                elements.push(
                                    wp.element.createElement('div', {
                                        dangerouslySetInnerHTML: {
                                            __html: '<ul>' + links.join('') + '</ul>'
                                        }
                                    })
                                );
                            }

                            return wp.element.createElement('div', null, elements);
                        })
                    )
                );
            }
        });
    }
}
