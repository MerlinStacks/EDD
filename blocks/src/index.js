import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

// Register the block
registerBlockType(metadata.name, {
    ...metadata,
    edit: function Edit({ attributes, setAttributes }) {
        const blockProps = useBlockProps({
            className: `ed-dates-ck-block ed-dates-ck-style-${attributes.displayStyle} ed-dates-ck-border-${attributes.borderStyle} ed-dates-ck-icon-${attributes.iconPosition}`,
        });

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Display Settings', 'ed-dates-ck')}>
                        <ToggleControl
                            label={__('Show Icon', 'ed-dates-ck')}
                            checked={attributes.showIcon}
                            onChange={(value) => setAttributes({ showIcon: value })}
                        />
                        {attributes.showIcon && (
                            <SelectControl
                                label={__('Icon Position', 'ed-dates-ck')}
                                value={attributes.iconPosition}
                                options={[
                                    { label: __('Left', 'ed-dates-ck'), value: 'left' },
                                    { label: __('Right', 'ed-dates-ck'), value: 'right' },
                                ]}
                                onChange={(value) => setAttributes({ iconPosition: value })}
                            />
                        )}
                        <SelectControl
                            label={__('Display Style', 'ed-dates-ck')}
                            value={attributes.displayStyle}
                            options={[
                                { label: __('Default', 'ed-dates-ck'), value: 'default' },
                                { label: __('Compact', 'ed-dates-ck'), value: 'compact' },
                                { label: __('Prominent', 'ed-dates-ck'), value: 'prominent' },
                            ]}
                            onChange={(value) => setAttributes({ displayStyle: value })}
                        />
                        <SelectControl
                            label={__('Border Style', 'ed-dates-ck')}
                            value={attributes.borderStyle}
                            options={[
                                { label: __('Left Accent', 'ed-dates-ck'), value: 'left-accent' },
                                { label: __('Full Border', 'ed-dates-ck'), value: 'full-border' },
                                { label: __('No Border', 'ed-dates-ck'), value: 'no-border' },
                            ]}
                            onChange={(value) => setAttributes({ borderStyle: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    {attributes.showIcon && attributes.iconPosition === 'left' && (
                        <span className="ed-dates-ck-icon dashicons dashicons-calendar-alt"></span>
                    )}
                    <div className="ed-dates-ck-content">
                        <h3>{__('Estimated Delivery', 'ed-dates-ck')}</h3>
                        <p className="delivery-date">{__('Loading...', 'ed-dates-ck')}</p>
                    </div>
                    {attributes.showIcon && attributes.iconPosition === 'right' && (
                        <span className="ed-dates-ck-icon dashicons dashicons-calendar-alt"></span>
                    )}
                </div>
            </>
        );
    },
}); 