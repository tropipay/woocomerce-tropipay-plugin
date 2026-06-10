const settings = wp.htmlEntities.decodeEntities( wc.wcSettings.getSetting( 'tropipay_data', {} ) );

const TropipayLabel = ( props ) => {
    const { Label } = props;
    return wp.element.createElement(
        'span',
        { style: { display: 'flex', alignItems: 'center', gap: '8px' } },
        Label,
        settings.showLogo && wp.element.createElement( 'img', {
            src: settings.logoUrl,
            alt: 'Tropipay',
            style: { maxHeight: '24px' }
        } )
    );
};

const TropipayContent = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { useState, useEffect } = wp.element;

    const [selectedMethod, setSelectedMethod] = useState(settings.defaultMethod);

    // Update session on load or selection
    useEffect(() => {
        wp.apiFetch({
            path: '/tropipay/v1/set-payment-method',
            method: 'POST',
            data: { method: selectedMethod }
        }).catch((err) => console.error(err));
    }, [selectedMethod]);

    useEffect(() => {
        const unsubscribe = eventRegistration.onCheckoutValidation(async () => {
            return {
                type: emitResponse.responseTypes.SUCCESS
            };
        });
        return () => unsubscribe();
    }, [eventRegistration, emitResponse]);

    useEffect(() => {
        const unsubscribe = eventRegistration.onPaymentProcessing(async () => {
            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        tropipay_payment_method: selectedMethod
                    }
                }
            };
        });
        return () => unsubscribe();
    }, [eventRegistration, emitResponse, selectedMethod]);

    const methods = settings.paymentMethods;
    const keys = Object.keys(methods);

    if (keys.length <= 1) {
        return wp.htmlEntities.decodeEntities( settings.description );
    }

    return wp.element.createElement(
        'div',
        { className: 'tropipay-blocks-payment-fields', style: { padding: '10px 0' } },
        wp.element.createElement('p', { style: { marginBottom: '10px' } }, wp.htmlEntities.decodeEntities( settings.description )),
        keys.map((key) => {
            return wp.element.createElement(
                'div',
                { key: key, style: { marginBottom: '8px', display: 'flex', alignItems: 'center' } },
                wp.element.createElement('input', {
                    type: 'radio',
                    id: 'tropipay_method_' + key,
                    name: 'tropipay_payment_method',
                    value: key,
                    checked: selectedMethod === key,
                    onChange: () => {
                        setSelectedMethod(key);
                        wp.apiFetch({
                            path: '/tropipay/v1/set-payment-method',
                            method: 'POST',
                            data: { method: key }
                        }).then(() => {
                            try {
                                wp.data.dispatch('wc/store/cart').invalidateResolution('getCart');
                            } catch (e) {
                                console.error(e);
                            }
                        }).catch((err) => console.error(err));
                    },
                    style: { marginRight: '8px', cursor: 'pointer' }
                }),
                wp.element.createElement('label', { 
                    htmlFor: 'tropipay_method_' + key,
                    style: { cursor: 'pointer', fontWeight: 'normal' }
                }, wp.htmlEntities.decodeEntities(methods[key]))
            );
        })
    );
};

wc.wcBlocksRegistry.registerPaymentMethod( {
    name: 'tropipay',
    label: wp.element.createElement( TropipayLabel, { Label: settings.title } ),
    content: wp.element.createElement( TropipayContent ),
    edit: wp.element.createElement( TropipayContent ),
    canMakePayment: () => true,
    ariaLabel: settings.title,
    supports: {
        features: settings.supports,
    }
} );
