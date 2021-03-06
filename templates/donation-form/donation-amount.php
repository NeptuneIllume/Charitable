<?php
/**
 * The template used to display the donation amount inputs.
 *
 * @author  Studio 164a
 * @since   1.0.0
 * @version 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( ! isset( $view_args['form'] ) ) {
	return;
}

/**
 * @var Charitable_Donation_Form
 */
$form = $view_args['form'];
$campaign = $form->get_campaign();
$suggested_donations = $campaign->get_suggested_donations();
$donation_amount = $campaign->get_donation_amount_in_session();

if ( empty( $suggested_donations ) && ! $campaign->get( 'allow_custom_donations' ) ) {
	return;
}

/**
 * @hook    charitable_donation_form_before_donation_amount
 */
do_action( 'charitable_donation_form_before_donation_amount', $view_args['form'] );

if ( $donation_amount ) : ?>
	
	<p class="set-donation-amount"><?php
		printf( '%s: <strong>%s</strong>',
			__( 'Your Donation Amount', 'charitable' ),
			charitable_format_money( $donation_amount )
		) ?>
		<a href="#" class="change-donation" data-charitable-toggle="charitable-donation-options-<?php echo $view_args['form']->get_form_identifier() ?>"><?php _e( 'Change', 'charitable' ) ?></a>
	</p><!-- .set-donation-amount -->

<?php endif; ?>

<div id="charitable-donation-options-<?php echo $view_args['form']->get_form_identifier() ?>" class="charitable-donation-options">

	<?php

	/**
	 * @hook    charitable_donation_form_before_donation_amounts
	 */
	do_action( 'charitable_donation_form_before_donation_amounts', $view_args['form'] );
	?>


<?php if ( count( $suggested_donations ) ) :

	$donation_amount_is_suggestion = false; ?>

	<ul class="donation-amounts">

		<?php foreach ( $suggested_donations as $suggestion ) :

			$checked = checked( $suggestion['amount'], $donation_amount, false );

			$id = esc_attr( sprintf( 'form-%s-field-%s',
				$view_args['form']->get_form_identifier(),
				$suggestion['amount']
			) );

			if ( strlen( $checked ) ) :

				$donation_amount_is_suggestion = true;

			endif; ?>

			<li class="donation-amount suggested-donation-amount">
				<label for="<?php echo $id ?>"><input id="<?php echo $id ?>" type="radio" name="donation_amount" value="<?php echo $suggestion['amount'] ?>" <?php echo $checked ?> /><?php
				printf( '<span class="amount">%s</span> <span class="description">%s</span>',
					charitable_format_money( $suggestion['amount'] ),
					isset( $suggestion['description'] ) ? $suggestion['description'] : ''
				) ?></label>
			</li>

		<?php endforeach; ?>

		<?php if ( $campaign->get( 'allow_custom_donations' ) ) :

			$has_custom_donation_amount = ! $donation_amount_is_suggestion && $donation_amount; ?>

			<li class="donation-amount custom-donation-amount">  
				<label for="form-<?php echo $view_args['form']->get_form_identifier();?>-field-custom-amount">             
				<input id="form-<?php echo $view_args['form']->get_form_identifier();?>-field-custom-amount" type="radio" name="donation_amount" value="custom" <?php checked( $has_custom_donation_amount ) ?> />
				<span class="description"><?php _e( 'Custom amount', 'charitable' ) ?></span>
				<input type="text" class="custom-donation-input" name="custom_donation_amount" value="<?php if ( $has_custom_donation_amount ) { echo $donation_amount; } ?>" />
				</label>
			</li>

		<?php endif ?>

	</ul>

<?php elseif ( $campaign->get( 'allow_custom_donations' ) ) : ?>

	<div id="custom-donation-amount-field" class="charitable-form-field charitable-custom-donation-field-alone">
		<input type="text" class="custom-donation-input" name="custom_donation_amount" placeholder="<?php esc_attr_e( 'Enter donation amount', 'charitable' ) ?>" value="<?php if ( $donation_amount ) { echo esc_attr( $donation_amount ); } ?>" />
	</div>

<?php endif ?>

	<?php
	/**
	 * @hook    charitable_donation_form_after_donation_amounts
	 */
	do_action( 'charitable_donation_form_after_donation_amounts', $view_args['form'] );
	?>

</div><!-- #charitable-donation-options-<?php echo $view_args['form']->get_form_identifier() ?> -->

<?php
/**
 * @hook    charitable_donation_form_after_donation_amount
 */
do_action( 'charitable_donation_form_after_donation_amount', $view_args['form'] );
