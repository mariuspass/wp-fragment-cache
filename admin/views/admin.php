<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   WP_Fragment_Cache
 * @author    Marius Dobre <mariuspass@gmail.com>
 * @license   GPL-2.0+
 * @link      https://github.com/mariuspass/WP-Fragment-Cache
 * @copyright 2014 Marius Dobre
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<div class="postbox" style="width: 35%;margin-right: 10px;">
		<form method="post" action="options.php">
			<?php if ( $this->it_meets_the_requirements ): ?>
				<?php settings_fields( 'wp-fragment-cache-settings-group' ); ?>
				<?php do_settings_sections( 'wp-fragment-cache-settings-group' ); ?>
			<?php endif; ?>
			<?php $is_enabled_option = (bool) get_option( 'wp_fragment_cache_is_enabled' ); ?>
			<div class="inside">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Enabled' ); ?></th>
						<td>
							<p><label for="wp_fragment_cache_is_enabled_1">
									<input id="wp_fragment_cache_is_enabled_1" type="radio" value="1"
										   <?php checked( $is_enabled_option ); ?>
										   <?php disabled( ! $this->it_meets_the_requirements ); ?>
									       name="wp_fragment_cache_is_enabled">
									<?php _e( 'Yes' ) ?>
								</label>
							</p>

							<p>
								<label for="wp_fragment_cache_is_enabled_0">
									<input id="wp_fragment_cache_is_enabled_0" type="radio" value="0"
									       <?php checked( ! $is_enabled_option ); ?>
										   <?php disabled( ! $this->it_meets_the_requirements ); ?>
										   name="wp_fragment_cache_is_enabled">
									<?php _e( 'No' ) ?>
								</label>
							</p>
						</td>
					</tr>
				</table>
			</div>
			<div id="major-publishing-actions">
				<div id="publishing-action">
					<input type="submit" class="button-primary" <?php disabled( ! $this->it_meets_the_requirements ); ?>
						   value="<?php _e( 'Save' ) ?>">
				</div>
				<div class="clear"></div>
			</div>
		</form>
	</div>

	<div class="postbox" style="width: 35%;margin-right: 10px;">
		<form id="purgeall" action="" method="post" class="clearfix">
			<div class="inside">
				<table class="form-table">
					<tr valign="top">
						<th><?php _e( 'Purge All Cache' ); ?></th>

						<td>
							<?php if ( $this->it_meets_the_requirements && $is_enabled_option ): ?>
								<?php $purge_url  = add_query_arg( array( 'action' => 'purge' ) ); ?>
								<?php $nonced_url = wp_nonce_url( $purge_url, 'purge', 'wp_fragment_cache_nonce' ); ?>
								<a style="background: #DD3D36; border: 0;" href="<?php echo esc_url( $nonced_url ); ?>"
								   class="button button-primary button-large"><?php _e( 'Purge Cache' ); ?></a>
							<?php else : ?>
								<button style="background: #DD3D36 !important; border: 0;" disabled="disabled"
								   class="button button-primary button-large"><?php _e( 'Purge Cache' ); ?></button>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</div>

</div>
