import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

registerBlockType('ed-dates-ck/estimated-delivery', {
    title: __('Estimated Delivery Date', 'ed-dates-ck'),
    icon: 'calendar-alt',
    category: 'woocommerce',
    description: __('Display estimated delivery date for the current product.', 'ed-dates-ck'),
    supports: {
        html: false,
        align: ['wide', 'full']
    },
    edit: function Edit() {
        const currentPostId = useSelect(select => {
            return select('core/editor').getCurrentPostId();
        }, []);

        return (
            <div className="ed-dates-ck-block">
                <h3>{__('Estimated Delivery Date', 'ed-dates-ck')}</h3>
                <p>{__('Delivery date will be calculated dynamically', 'ed-dates-ck')}</p>
            </div>
        );
    },
    save: function Save() {
        return null; // Dynamic block, rendered by PHP
    }
}); 