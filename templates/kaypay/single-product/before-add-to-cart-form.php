<?php
/**
 * This template can be overridden by copying it to your-theme/woocommerce/kaypay/single-product/before-add-to-cart-form.php.
 */

defined( 'ABSPATH' ) || exit;

/** @var string $amount */

?>

<kaypay-price-promotion amount="<?php echo esc_attr( $amount ) ?>"
                        context="woocommerce/<?php echo KAYPAY_VERSION ?>"
                        currency-unit="<?php echo esc_attr( get_woocommerce_currency_symbol() ) ?>"
                        locale="<?php echo esc_attr( get_locale() ) ?>"
                        price-font-size="1em"
                        price-font-weight="bold"
                        installment-text-font-size="1em">
</kaypay-price-promotion>
