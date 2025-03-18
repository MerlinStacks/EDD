import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

registerBlockType('ed-dates-ck/estimated-delivery', {
    title: __('Estimated Delivery Date', 'ed-dates-ck'),
    icon: 'calendar-alt',
    category: 'woocommerce',
    description: __('Display estimated delivery date for the current product.', 'ed-dates-ck'),
    supports: {
        html: false,
        align: ['wide', 'full']
    },
    attributes: {
        className: {
            type: 'string',
            default: ''
        }
    },
    edit: function({ className, attributes, setAttributes }) {
        const postId = useSelect(select => select('core/editor').getCurrentPostId());
        const postType = useSelect(select => select('core/editor').getCurrentPostType());

        // Only show preview in product editor
        if (postType !== 'product') {
            return (
                <div className={className}>
                    <div className="ed-dates-ck-block ed-dates-ck-block-warning">
                        <p>{__('This block can only be used in product pages.', 'ed-dates-ck')}</p>
                    </div>
                </div>
            );
        }

        return (
            <>
                <InspectorControls>
                    <PanelBody
                        title={__('Block Settings', 'ed-dates-ck')}
                        initialOpen={true}
                    >
                        <p>{__('This block will display the estimated delivery date for the current product.', 'ed-dates-ck')}</p>
                    </PanelBody>
                </InspectorControls>
                <div className={className}>
                    <div className="ed-dates-ck-block">
                        <h3>{__('Estimated Delivery Date', 'ed-dates-ck')}</h3>
                        <p>{__('Delivery date will be calculated and displayed here', 'ed-dates-ck')}</p>
                        <em>{__('(Preview only - actual date will show on frontend)', 'ed-dates-ck')}</em>
                    </div>
                </div>
            </>
        );
    },
    save: function() {
        return null; // Use server-side render
    }
}); 