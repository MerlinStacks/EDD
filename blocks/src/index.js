import { registerBlockType } from '@wordpress/blocks';
import { 
    InspectorControls,
    useBlockProps,
    withColors,
    PanelColorSettings,
    FontSizePicker,
    withFontSizes,
} from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    SelectControl,
    __experimentalBorderControl as BorderControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

const Edit = (props) => {
    const {
        attributes,
        setAttributes,
        className,
        backgroundColor,
        textColor,
        setBackgroundColor,
        setTextColor,
        fontSize,
        setFontSize,
    } = props;

    const { showIcon, iconPosition } = attributes;
    const blockProps = useBlockProps({
        className: `ed-dates-ck-delivery-block ${className || ''}`,
        style: {
            backgroundColor: backgroundColor.color,
            color: textColor.color,
            fontSize: fontSize.size,
            ...attributes.style
        }
    });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Icon Settings', 'ed-dates-ck')}>
                    <ToggleControl
                        label={__('Show Airplane Icon', 'ed-dates-ck')}
                        checked={showIcon}
                        onChange={(value) => setAttributes({ showIcon: value })}
                    />
                    {showIcon && (
                        <SelectControl
                            label={__('Icon Position', 'ed-dates-ck')}
                            value={iconPosition}
                            options={[
                                { label: __('Left', 'ed-dates-ck'), value: 'left' },
                                { label: __('Right', 'ed-dates-ck'), value: 'right' },
                            ]}
                            onChange={(value) => setAttributes({ iconPosition: value })}
                        />
                    )}
                </PanelBody>
                <PanelColorSettings
                    title={__('Color Settings', 'ed-dates-ck')}
                    colorSettings={[
                        {
                            value: backgroundColor.color,
                            onChange: setBackgroundColor,
                            label: __('Background Color', 'ed-dates-ck'),
                        },
                        {
                            value: textColor.color,
                            onChange: setTextColor,
                            label: __('Text Color', 'ed-dates-ck'),
                        },
                    ]}
                />
                <PanelBody title={__('Typography', 'ed-dates-ck')}>
                    <FontSizePicker
                        value={fontSize.size}
                        onChange={setFontSize}
                    />
                </PanelBody>
                <PanelBody title={__('Border Settings', 'ed-dates-ck')}>
                    <BorderControl
                        value={attributes.style?.border || {}}
                        onChange={(value) => {
                            const newStyle = {
                                ...attributes.style,
                                border: value
                            };
                            setAttributes({ style: newStyle });
                        }}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {showIcon && iconPosition === 'left' && (
                    <span className="dashicons dashicons-airplane"></span>
                )}
                <span className="ed-dates-ck-delivery-text">
                    {__('Estimated Delivery: January 1 - January 2', 'ed-dates-ck')}
                </span>
                {showIcon && iconPosition === 'right' && (
                    <span className="dashicons dashicons-airplane"></span>
                )}
            </div>
        </>
    );
};

const editComponent = compose([
    withColors('backgroundColor', 'textColor'),
    withFontSizes('fontSize'),
])(Edit);

registerBlockType('ed-dates-ck/estimated-delivery', {
    apiVersion: 2,
    title: __('Estimated Delivery', 'ed-dates-ck'),
    description: __('Display estimated delivery dates for WooCommerce products.', 'ed-dates-ck'),
    category: 'woocommerce',
    icon: 'airplane',
    supports: {
        html: false,
        align: true,
        spacing: {
            margin: true,
            padding: true,
            blockGap: true,
        },
        typography: {
            fontSize: true,
            lineHeight: true,
            __experimentalFontFamily: true,
        },
        border: {
            color: true,
            radius: true,
            style: true,
            width: true,
        },
    },
    edit: editComponent,
    save: () => null, // Dynamic block, rendered in PHP
}); 