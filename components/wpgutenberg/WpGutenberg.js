export default class WpGutenberg {
    init() {
        // this has to be left out (because of https://github.com/WordPress/gutenberg/issues/9757)
        //wp.domReady(() => {
        this.initAltLng();
        //});
    }

    initAltLng() {
        wp.hooks.addFilter('blocks.registerBlockType', 'custom/attrs', settings => {
            console.log(settings);
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
                    for (const [languages__key, languages__value] of Object.entries(wpgutenberg_data.languages)) {
                        options.push({ value: languages__key, label: languages__value });
                    }
                    return (
                        <wp.element.Fragment>
                            <BlockEdit {...props} />
                            {
                                <wp.blockEditor.InspectorControls>
                                    <wp.components.PanelBody title={wp.i18n.__('Languages', 'gtbabel-plugin')}>
                                        <wp.components.SelectControl
                                            label={wp.i18n.__('Source language', 'gtbabel-plugin')}
                                            value={props.attributes.gtbabel_alt_lng}
                                            onChange={val => props.setAttributes({ gtbabel_alt_lng: val })}
                                            options={options}
                                        />
                                        <wp.components.ToggleControl
                                            label={wp.i18n.__('Show only there', 'gtbabel-plugin')}
                                            checked={props.attributes.gtbabel_hide_block_target === true}
                                            onChange={val => props.setAttributes({ gtbabel_hide_block_target: val })}
                                        />
                                    </wp.components.PanelBody>
                                </wp.blockEditor.InspectorControls>
                            }
                        </wp.element.Fragment>
                    );
                    return <BlockEdit {...props} />;
                },
                'withcustomattrinputs'
            )
        );
    }
}
