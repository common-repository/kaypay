<?php
/**
 * This template can be overridden by copying it to your-theme/woocommerce/kaypay/checkout/payment-method.php.
 */

defined( 'ABSPATH' ) || exit;

?>

<kaypay-payment-method amount-from="woocommerce"
                       context="woocommerce/<?php echo KAYPAY_VERSION ?>"
                       currency-unit="<?php echo esc_attr( get_woocommerce_currency_symbol() ) ?>"
                       locale="<?php echo esc_attr( get_locale() ) ?>">
</kaypay-payment-method>
