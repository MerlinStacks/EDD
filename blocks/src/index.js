import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
    InspectorControls,
    useBlockProps,
    PanelColorSettings,
    FontSizePicker,
} from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    SelectControl,
    __experimentalBoxControl as BoxControl,
} from '@wordpress/components';
import { Icon, calendar } from '@wordpress/icons';

const FONT_SIZES = [
    {
        name: __('Small', 'ed-dates-ck'),
        slug: 'small',
        size: 14,
    },
    {
        name: __('Medium', 'ed-dates-ck'),
        slug: 'medium',
        size: 16,
    },
    {
        name: __('Large', 'ed-dates-ck'),
        slug: 'large',
        size: 18,
    },
];

const DISPLAY_STYLES = [
    { label: __('Default', 'ed-dates-ck'), value: 'default' },
    { label: __('Compact', 'ed-dates-ck'), value: 'compact' },
    { label: __('Prominent', 'ed-dates-ck'), value: 'prominent' },
];

const BORDER_STYLES = [
    { label: __('Left Accent', 'ed-dates-ck'), value: 'left-accent' },
    { label: __('Full Border', 'ed-dates-ck'), value: 'full-border' },
    { label: __('No Border', 'ed-dates-ck'), value: 'no-border' },
];

registerBlockType('ed-dates-ck/estimated-delivery', {
    title: __('Estimated Delivery Date', 'ed-dates-ck'),
    icon: 'calendar-alt',
    category: 'woocommerce',
    description: __('Display estimated delivery date for the current product.', 'ed-dates-ck'),
    supports: {
        html: false,
        align: ['wide', 'full'],
        spacing: {
            margin: true,
            padding: true,
        },
        typography: {
            fontSize: true,
            lineHeight: true,
        },
        color: {
            background: true,
            text: true,
            link: true,
            gradients: true,
        },
    },
    attributes: {
        className: {
            type: 'string',
            default: '',
        },
        textColor: {
            type: 'string',
        },
        backgroundColor: {
            type: 'string',
        },
        fontSize: {
            type: 'string',
        },
        customFontSize: {
            type: 'number',
        },
        style: {
            type: 'object',
        },
        showIcon: {
            type: 'boolean',
            default: true,
        },
        iconPosition: {
            type: 'string',
            default: 'left',
        },
        displayStyle: {
            type: 'string',
            default: 'default',
        },
        borderStyle: {
            type: 'string',
            default: 'left-accent',
        },
    },
    edit: function({ attributes, setAttributes }) {
        const {
            showIcon,
            iconPosition,
            displayStyle,
            borderStyle,
            textColor,
            backgroundColor,
            fontSize,
            customFontSize,
        } = attributes;

        const blockProps = useBlockProps();
        const postType = useSelect(select => select('core/editor').getCurrentPostType());

        // Only show preview in product editor
        if (postType !== 'product') {
            return (
                <div {...blockProps}>
                    <div className="ed-dates-ck-block ed-dates-ck-block-warning">
                        <p>{__('This block can only be used in product pages.', 'ed-dates-ck')}</p>
                    </div>
                </div>
            );
        }

        const classes = [
            'ed-dates-ck-block',
            `ed-dates-ck-style-${displayStyle}`,
            `ed-dates-ck-border-${borderStyle}`,
            iconPosition === 'left' ? 'ed-dates-ck-icon-left' : 'ed-dates-ck-icon-right',
        ].filter(Boolean).join(' ');

        const style = {
            color: textColor,
            backgroundColor,
            fontSize: customFontSize ? `${customFontSize}px` : undefined,
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Display Settings', 'ed-dates-ck')}>
                        <SelectControl
                            label={__('Display Style', 'ed-dates-ck')}
                            value={displayStyle}
                            options={DISPLAY_STYLES}
                            onChange={value => setAttributes({ displayStyle: value })}
                        />
                        <SelectControl
                            label={__('Border Style', 'ed-dates-ck')}
                            value={borderStyle}
                            options={BORDER_STYLES}
                            onChange={value => setAttributes({ borderStyle: value })}
                        />
                        <ToggleControl
                            label={__('Show Calendar Icon', 'ed-dates-ck')}
                            checked={showIcon}
                            onChange={value => setAttributes({ showIcon: value })}
                        />
                        {showIcon && (
                            <SelectControl
                                label={__('Icon Position', 'ed-dates-ck')}
                                value={iconPosition}
                                options={[
                                    { label: __('Left', 'ed-dates-ck'), value: 'left' },
                                    { label: __('Right', 'ed-dates-ck'), value: 'right' },
                                ]}
                                onChange={value => setAttributes({ iconPosition: value })}
                            />
                        )}
                    </PanelBody>
                    <PanelColorSettings
                        title={__('Color Settings', 'ed-dates-ck')}
                        colorSettings={[
                            {
                                value: textColor,
                                onChange: value => setAttributes({ textColor: value }),
                                label: __('Text Color', 'ed-dates-ck'),
                            },
                            {
                                value: backgroundColor,
                                onChange: value => setAttributes({ backgroundColor: value }),
                                label: __('Background Color', 'ed-dates-ck'),
                            },
                        ]}
                    />
                    <PanelBody title={__('Typography', 'ed-dates-ck')}>
                        <FontSizePicker
                            value={fontSize}
                            onChange={value => setAttributes({ fontSize: value })}
                            fontSizes={FONT_SIZES}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps} className={classes} style={style}>
                    {showIcon && iconPosition === 'left' && (
                        <Icon icon={calendar} size={24} className="ed-dates-ck-icon" />
                    )}
                    <div className="ed-dates-ck-content">
                        <h3>{__('Estimated Delivery Date', 'ed-dates-ck')}</h3>
                        <p>{__('Delivery date will be calculated and displayed here', 'ed-dates-ck')}</p>
                        <em>{__('(Preview only - actual date will show on frontend)', 'ed-dates-ck')}</em>
                    </div>
                    {showIcon && iconPosition === 'right' && (
                        <Icon icon={calendar} size={24} className="ed-dates-ck-icon" />
                    )}
                </div>
            </>
        );
    },
    save: function() {
        return null; // Use server-side render
    }
}); 