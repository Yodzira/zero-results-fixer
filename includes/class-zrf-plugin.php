<?php
/**
 * Plugin boot + admin page.
 *
 * @package ZeroResultsFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZRF_Plugin {

	public static function boot() {
		ZRF_Applier::boot();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( 'ZRF_Admin', 'menu' ) );
			add_action( 'admin_post_zrf_save_rule', array( 'ZRF_Admin', 'handle_save_rule' ) );
			add_action( 'admin_post_zrf_delete_rule', array( 'ZRF_Admin', 'handle_delete_rule' ) );
		}
	}
}

class ZRF_Admin {

	public static function menu() {
		add_menu_page( 'Zero Results Fixer', 'Search Fixer', 'manage_options', 'zero-results-fixer', array( __CLASS__, 'render' ), 'dashicons-search' );
	}

	public static function handle_save_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'zero-results-fixer' ) );
		}
		check_admin_referer( 'zrf_save_rule' );

		$data = array(
			'id'       => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'type'     => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'mapping',
			'query'    => isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '',
			'posts'    => isset( $_POST['posts'] ) ? sanitize_text_field( wp_unslash( $_POST['posts'] ) ) : '',
			'priority' => isset( $_POST['priority'] ) ? absint( $_POST['priority'] ) : 10,
			'enabled'  => isset( $_POST['enabled'] ) ? 1 : 0,
		);
		if ( '' !== $data['query'] ) {
			ZRF_Store::save_rule( $data );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=zero-results-fixer&saved=1' ) );
		exit;
	}

	public static function handle_delete_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'zero-results-fixer' ) );
		}
		check_admin_referer( 'zrf_delete_rule' );
		ZRF_Store::delete_rule( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
		wp_safe_redirect( admin_url( 'admin.php?page=zero-results-fixer&deleted=1' ) );
		exit;
	}

	public static function render() {
		$saved   = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag.
		$rules   = ZRF_Store::all();
		?>
		<div class="wrap">
			<h1>Zero Results Fixer</h1>

			<?php if ( '1' === $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>Rule saved.</p></div>
			<?php endif; ?>

			<h2>Add a rule</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="background:#fff;padding:14px 18px;border:1px solid #ccd0d4;max-width:960px">
				<input type="hidden" name="action" value="zrf_save_rule">
				<?php wp_nonce_field( 'zrf_save_rule' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th>Type</th>
						<td>
							<label><input type="radio" name="type" value="mapping" checked> Mapping — «query» → show posts (comma-separated IDs)</label><br>
							<label><input type="radio" name="type" value="synonym"> Synonym — «query» also matches this word</label>
						</td>
					</tr>
					<tr><th>Query</th><td><input type="text" name="query" class="regular-text" placeholder="айфон" required></td></tr>
					<tr><th>Posts / word</th><td><input type="text" name="posts" class="regular-text" placeholder="123, 124 — or a synonym word"><p class="description">For mapping: comma-separated post IDs. For synonym: the word to also search for.</p></td></tr>
					<tr><th>Priority</th><td><input type="number" name="priority" value="10" class="small-text" min="0" max="100"></td></tr>
					<tr><th></th><td><label><input type="checkbox" name="enabled" value="1" checked> Enabled</label></td></tr>
				</table>
				<button type="submit" class="button button-primary">Save rule</button>
			</form>

			<h2>Rules</h2>
			<?php if ( ! $rules ) : ?>
				<p><em>No rules yet.</em></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:1000px">
					<thead><tr><th style="width:80px">Type</th><th>Query</th><th>Posts / word</th><th style="width:70px">Hits</th><th style="width:70px">On</th><th style="width:70px"></th></tr></thead>
					<tbody>
					<?php foreach ( $rules as $rule ) : ?>
						<tr>
							<td><?php echo esc_html( $rule['type'] ); ?></td>
							<td><code><?php echo esc_html( $rule['query'] ); ?></code></td>
							<td><?php echo esc_html( $rule['posts'] ); ?></td>
							<td><?php echo esc_html( $rule['hits'] ); ?></td>
							<td><?php echo $rule['enabled'] ? '✓' : '—'; ?></td>
							<td><a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=zrf_delete_rule&id=' . (int) $rule['id'] ), 'zrf_delete_rule' ) ); ?>">Delete</a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
