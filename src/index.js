import { registerBlockType } from '@wordpress/blocks';
import { 
    useBlockProps,
    InspectorControls,
    AlignmentControl,
} from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    RangeControl,
    ColorPicker,
    TextControl,
    __experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';

registerBlockType('wc-edd/estimated-delivery-date', {
    edit: ({ attributes, setAttributes }) => {
        const {
            displayType,
            textAlign,
            fontSize,
            textColor,
            backgroundColor,
            fontFamily,
            fontWeight,
            marginTop,
            marginBottom,
            borderWidth,
            borderStyle,
            borderColor,
            icon,
            iconPosition,
        } = attributes;

        const [estimatedDate, setEstimatedDate] = useState('');

        useEffect(() => {
            // This would be replaced with actual date calculation in frontend
            const date = new Date();
            date.setDate(date.getDate() + 7);
            setEstimatedDate(date.toLocaleDateString());
        }, []);

        const blockProps = useBlockProps({
            style: {
                textAlign,
                fontSize: fontSize ? `${fontSize}px` : undefined,
                color: textColor,
                backgroundColor,
                fontFamily,
                fontWeight,
                marginTop: marginTop ? `${marginTop}px` : undefined,
                marginBottom: marginBottom ? `${marginBottom}px` : undefined,
                borderWidth: borderWidth ? `${borderWidth}px` : undefined,
                borderStyle,
                borderColor,
            },
        });

        const renderContent = () => {
            const iconElement = icon && (
                <span className="wc-edd-icon" style={{ marginRight: iconPosition === 'left' ? '8px' : '0', marginLeft: iconPosition === 'right' ? '8px' : '0' }}>
                    <span className={`dashicons dashicons-${icon}`}></span>
                </span>
            );

            return (
                <div {...blockProps}>
                    {iconPosition === 'left' && iconElement}
                    {displayType === 'text' ? (
                        <span>
                            {__('Estimated Delivery Date: ', 'wc-estimated-delivery-date')}
                            {estimatedDate}
                        </span>
                    ) : (
                        <span>{estimatedDate}</span>
                    )}
                    {iconPosition === 'right' && iconElement}
                </div>
            );
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Display Settings', 'wc-estimated-delivery-date')}>
                        <SelectControl
                            label={__('Display Type', 'wc-estimated-delivery-date')}
                            value={displayType}
                            options={[
                                { label: __('Text', 'wc-estimated-delivery-date'), value: 'text' },
                                { label: __('Date Only', 'wc-estimated-delivery-date'), value: 'date' },
                            ]}
                            onChange={(value) => setAttributes({ displayType: value })}
                        />
                        <AlignmentControl
                            value={textAlign}
                            onChange={(value) => setAttributes({ textAlign: value })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Typography', 'wc-estimated-delivery-date')}>
                        <NumberControl
                            label={__('Font Size', 'wc-estimated-delivery-date')}
                            value={fontSize}
                            onChange={(value) => setAttributes({ fontSize: value })}
                            min={8}
                            max={100}
                        />
                        <SelectControl
                            label={__('Font Weight', 'wc-estimated-delivery-date')}
                            value={fontWeight}
                            options={[
                                { label: __('Normal', 'wc-estimated-delivery-date'), value: 'normal' },
                                { label: __('Bold', 'wc-estimated-delivery-date'), value: 'bold' },
                                { label: __('Light', 'wc-estimated-delivery-date'), value: '300' },
                                { label: __('Bolder', 'wc-estimated-delivery-date'), value: '900' },
                            ]}
                            onChange={(value) => setAttributes({ fontWeight: value })}
                        />
                        <TextControl
                            label={__('Font Family', 'wc-estimated-delivery-date')}
                            value={fontFamily}
                            onChange={(value) => setAttributes({ fontFamily: value })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Colors', 'wc-estimated-delivery-date')}>
                        <div>
                            <label>{__('Text Color', 'wc-estimated-delivery-date')}</label>
                            <ColorPicker
                                color={textColor}
                                onChange={(value) => setAttributes({ textColor: value })}
                                enableAlpha
                            />
                        </div>
                        <div>
                            <label>{__('Background Color', 'wc-estimated-delivery-date')}</label>
                            <ColorPicker
                                color={backgroundColor}
                                onChange={(value) => setAttributes({ backgroundColor: value })}
                                enableAlpha
                            />
                        </div>
                    </PanelBody>

                    <PanelBody title={__('Spacing', 'wc-estimated-delivery-date')}>
                        <NumberControl
                            label={__('Margin Top', 'wc-estimated-delivery-date')}
                            value={marginTop}
                            onChange={(value) => setAttributes({ marginTop: value })}
                            min={0}
                            max={100}
                        />
                        <NumberControl
                            label={__('Margin Bottom', 'wc-estimated-delivery-date')}
                            value={marginBottom}
                            onChange={(value) => setAttributes({ marginBottom: value })}
                            min={0}
                            max={100}
                        />
                    </PanelBody>

                    <PanelBody title={__('Border', 'wc-estimated-delivery-date')}>
                        <NumberControl
                            label={__('Border Width', 'wc-estimated-delivery-date')}
                            value={borderWidth}
                            onChange={(value) => setAttributes({ borderWidth: value })}
                            min={0}
                            max={10}
                        />
                        <SelectControl
                            label={__('Border Style', 'wc-estimated-delivery-date')}
                            value={borderStyle}
                            options={[
                                { label: __('None', 'wc-estimated-delivery-date'), value: 'none' },
                                { label: __('Solid', 'wc-estimated-delivery-date'), value: 'solid' },
                                { label: __('Dashed', 'wc-estimated-delivery-date'), value: 'dashed' },
                                { label: __('Dotted', 'wc-estimated-delivery-date'), value: 'dotted' },
                            ]}
                            onChange={(value) => setAttributes({ borderStyle: value })}
                        />
                        {borderStyle !== 'none' && (
                            <div>
                                <label>{__('Border Color', 'wc-estimated-delivery-date')}</label>
                                <ColorPicker
                                    color={borderColor}
                                    onChange={(value) => setAttributes({ borderColor: value })}
                                    enableAlpha
                                />
                            </div>
                        )}
                    </PanelBody>

                    <PanelBody title={__('Icon', 'wc-estimated-delivery-date')}>
                        <SelectControl
                            label={__('Icon', 'wc-estimated-delivery-date')}
                            value={icon}
                            options={[
                                { label: __('None', 'wc-estimated-delivery-date'), value: '' },
                                { label: __('Calendar', 'wc-estimated-delivery-date'), value: 'calendar' },
                                { label: __('Clock', 'wc-estimated-delivery-date'), value: 'clock' },
                                { label: __('Truck', 'wc-estimated-delivery-date'), value: 'truck' },
                            ]}
                            onChange={(value) => setAttributes({ icon: value })}
                        />
                        {icon && (
                            <SelectControl
                                label={__('Icon Position', 'wc-estimated-delivery-date')}
                                value={iconPosition}
                                options={[
                                    { label: __('Left', 'wc-estimated-delivery-date'), value: 'left' },
                                    { label: __('Right', 'wc-estimated-delivery-date'), value: 'right' },
                                ]}
                                onChange={(value) => setAttributes({ iconPosition: value })}
                            />
                        )}
                    </PanelBody>
                </InspectorControls>
                {renderContent()}
            </>
        );
    },

    save: ({ attributes }) => {
        const {
            displayType,
            textAlign,
            fontSize,
            textColor,
            backgroundColor,
            fontFamily,
            fontWeight,
            marginTop,
            marginBottom,
            borderWidth,
            borderStyle,
            borderColor,
            icon,
            iconPosition,
        } = attributes;

        const blockProps = useBlockProps.save({
            style: {
                textAlign,
                fontSize: fontSize ? `${fontSize}px` : undefined,
                color: textColor,
                backgroundColor,
                fontFamily,
                fontWeight,
                marginTop: marginTop ? `${marginTop}px` : undefined,
                marginBottom: marginBottom ? `${marginBottom}px` : undefined,
                borderWidth: borderWidth ? `${borderWidth}px` : undefined,
                borderStyle,
                borderColor,
            },
        });

        const iconElement = icon && (
            <span className="wc-edd-icon" style={{ marginRight: iconPosition === 'left' ? '8px' : '0', marginLeft: iconPosition === 'right' ? '8px' : '0' }}>
                <span className={`dashicons dashicons-${icon}`}></span>
            </span>
        );

        return (
            <div {...blockProps}>
                {iconPosition === 'left' && iconElement}
                <span className="wc-edd-date" data-display-type={displayType}></span>
                {iconPosition === 'right' && iconElement}
            </div>
        );
    },
});