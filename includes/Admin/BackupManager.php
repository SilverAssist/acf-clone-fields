<?php
/**
 * Backup Manager Component
 *
 * Provides admin interface for managing field backups.
 * Allows users to view, restore, and delete backups.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Admin
 * @since 1.1.0
 * @version 1.1.1
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Admin;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;
use SilverAssist\ACFCloneFields\Services\FieldCloner;

defined( 'ABSPATH' ) || exit;

/**
 * Class BackupManager
 *
 * Manages backup interface and operations.
 */
class BackupManager implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var BackupManager|null
	 */
	private static ?BackupManager $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return BackupManager
	 */
	public static function instance(): BackupManager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize backup manager
	 *
	 * @return void
	 */
	public function init(): void {
		$this->init_hooks();
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 40; // Admin components.
	}

	/**
	 * Determine if backup manager should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return is_admin();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Add meta box to post edit screens.
		add_action( 'add_meta_boxes', [ $this, 'add_backup_meta_box' ] );

		// AJAX handlers.
		add_action( 'wp_ajax_acf_clone_restore_backup', [ $this, 'handle_restore_backup' ] );
		add_action( 'wp_ajax_acf_clone_delete_backup', [ $this, 'handle_delete_backup' ] );
		add_action( 'wp_ajax_acf_clone_cleanup_backups', [ $this, 'handle_cleanup_backups' ] );
	}

	/**
	 * Add backup management meta box
	 *
	 * @return void
	 */
	public function add_backup_meta_box(): void {
		$enabled_post_types = get_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post', 'page' ] );

		foreach ( $enabled_post_types as $post_type ) {
			add_meta_box(
				'silver-acf-clone-backups',
				__( 'Field Backups', 'silver-assist-acf-clone-fields' ),
				[ $this, 'render_backup_meta_box' ],
				$post_type,
				'side',
				'low'
			);
		}
	}

	/**
	 * Render backup meta box
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_backup_meta_box( $post ): void {
		// Get backups for this post.
		$backups = FieldCloner::instance()->get_post_backups( $post->ID );

		// Nonce for security.
		wp_nonce_field( 'acf_clone_backup_action', 'acf_clone_backup_nonce' );

		if ( empty( $backups ) ) {
			echo '<p>' . esc_html__( 'No backups available for this post.', 'silver-assist-acf-clone-fields' ) . '</p>';
			return;
		}

		echo '<div id="acf-clone-backups-list">';
		echo '<p class="description">' . esc_html__( 'Backups created when fields were cloned to this post.', 'silver-assist-acf-clone-fields' ) . '</p>';

		foreach ( $backups as $backup ) {
			$this->render_backup_item( $backup );
		}

		echo '</div>';

		// Cleanup button.
		echo '<p style="margin-top: 15px;">';
		echo '<button type="button" class="button" id="acf-clone-cleanup-backups">';
		echo esc_html__( 'Clean Up Old Backups', 'silver-assist-acf-clone-fields' );
		echo '</button>';
		echo '</p>';

		// Add inline JavaScript.
		$this->render_backup_scripts();
	}

	/**
	 * Render individual backup item
	 *
	 * @param array<string, mixed> $backup Backup data.
	 * @return void
	 */
	private function render_backup_item( array $backup ): void {
		$user        = get_userdata( $backup['user_id'] );
		$user_name   = $user ? $user->display_name : __( 'Unknown', 'silver-assist-acf-clone-fields' );
		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$backup_date = mysql2date( $date_format, $backup['created_at'] );

		?>
		<div class="acf-clone-backup-item" style="padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; background: #f9f9f9;">
			<div style="margin-bottom: 5px;">
				<strong><?php echo esc_html( (string) $backup_date ); ?></strong>
			</div>
			<div style="font-size: 12px; color: #666; margin-bottom: 8px;">
				<?php
				printf(
					/* translators: 1: number of fields, 2: user name */
					esc_html__( '%1$d field(s) backed up by %2$s', 'silver-assist-acf-clone-fields' ),
					(int) $backup['field_count'],
					esc_html( $user_name )
				);
				?>
			</div>
			<div class="acf-clone-backup-actions">
				<button type="button" 
					class="button button-small acf-clone-restore-backup" 
					data-backup-id="<?php echo esc_attr( $backup['backup_id'] ); ?>"
					data-post-id="<?php echo esc_attr( $backup['post_id'] ); ?>">
					<?php esc_html_e( 'Restore', 'silver-assist-acf-clone-fields' ); ?>
				</button>
				<button type="button" 
					class="button button-small acf-clone-delete-backup" 
					data-backup-id="<?php echo esc_attr( $backup['backup_id'] ); ?>"
					style="color: #b32d2e;">
					<?php esc_html_e( 'Delete', 'silver-assist-acf-clone-fields' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render backup management scripts
	 *
	 * @return void
	 */
	private function render_backup_scripts(): void {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Restore backup.
			$('.acf-clone-restore-backup').on('click', function(e) {
				e.preventDefault();
				
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to restore this backup? Current field values will be replaced.', 'silver-assist-acf-clone-fields' ) ); ?>')) {
					return;
				}
				
				var $button = $(this);
				var backupId = $button.data('backup-id');
				var postId = $button.data('post-id');
				
				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Restoring...', 'silver-assist-acf-clone-fields' ) ); ?>');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_clone_restore_backup',
						backup_id: backupId,
						post_id: postId,
						nonce: $('#acf_clone_backup_nonce').val()
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							location.reload();
						} else {
							alert(response.data.message || '<?php echo esc_js( __( 'Failed to restore backup.', 'silver-assist-acf-clone-fields' ) ); ?>');
							$button.prop('disabled', false).text('<?php echo esc_js( __( 'Restore', 'silver-assist-acf-clone-fields' ) ); ?>');
						}
					},
					error: function() {
						alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'silver-assist-acf-clone-fields' ) ); ?>');
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Restore', 'silver-assist-acf-clone-fields' ) ); ?>');
					}
				});
			});
			
			// Delete backup.
			$('.acf-clone-delete-backup').on('click', function(e) {
				e.preventDefault();
				
				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to delete this backup? This cannot be undone.', 'silver-assist-acf-clone-fields' ) ); ?>')) {
					return;
				}
				
				var $button = $(this);
				var $item = $button.closest('.acf-clone-backup-item');
				var backupId = $button.data('backup-id');
				
				$button.prop('disabled', true);
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_clone_delete_backup',
						backup_id: backupId,
						nonce: $('#acf_clone_backup_nonce').val()
					},
					success: function(response) {
						if (response.success) {
							$item.fadeOut(300, function() {
								$(this).remove();
								if ($('.acf-clone-backup-item').length === 0) {
									$('#acf-clone-backups-list').html('<p><?php echo esc_js( __( 'No backups available for this post.', 'silver-assist-acf-clone-fields' ) ); ?></p>');
								}
							});
						} else {
							alert(response.data.message || '<?php echo esc_js( __( 'Failed to delete backup.', 'silver-assist-acf-clone-fields' ) ); ?>');
							$button.prop('disabled', false);
						}
					},
					error: function() {
						alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'silver-assist-acf-clone-fields' ) ); ?>');
						$button.prop('disabled', false);
					}
				});
			});
			
			// Clean up old backups.
			$('#acf-clone-cleanup-backups').on('click', function(e) {
				e.preventDefault();
				
				if (!confirm('<?php echo esc_js( __( 'This will delete backups older than the retention period. Continue?', 'silver-assist-acf-clone-fields' ) ); ?>')) {
					return;
				}
				
				var $button = $(this);
				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Cleaning up...', 'silver-assist-acf-clone-fields' ) ); ?>');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_clone_cleanup_backups',
						nonce: $('#acf_clone_backup_nonce').val()
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							location.reload();
						} else {
							alert(response.data.message || '<?php echo esc_js( __( 'Cleanup failed.', 'silver-assist-acf-clone-fields' ) ); ?>');
						}
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Clean Up Old Backups', 'silver-assist-acf-clone-fields' ) ); ?>');
					},
					error: function() {
						alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'silver-assist-acf-clone-fields' ) ); ?>');
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Clean Up Old Backups', 'silver-assist-acf-clone-fields' ) ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Handle restore backup AJAX request
	 *
	 * @return void
	 */
	public function handle_restore_backup(): void {
		// Verify nonce.
		check_ajax_referer( 'acf_clone_backup_action', 'nonce' );

		// Check capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'silver-assist-acf-clone-fields' ) ] );
		}

		// Get backup ID.
		$backup_id = sanitize_text_field( $_POST['backup_id'] ?? '' );

		if ( empty( $backup_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid backup ID.', 'silver-assist-acf-clone-fields' ) ] );
		}

		// Restore backup.
		$result = FieldCloner::instance()->restore_backup( $backup_id, false );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Handle delete backup AJAX request
	 *
	 * @return void
	 */
	public function handle_delete_backup(): void {
		// Verify nonce.
		check_ajax_referer( 'acf_clone_backup_action', 'nonce' );

		// Check capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'silver-assist-acf-clone-fields' ) ] );
		}

		// Get backup ID.
		$backup_id = sanitize_text_field( $_POST['backup_id'] ?? '' );

		if ( empty( $backup_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid backup ID.', 'silver-assist-acf-clone-fields' ) ] );
		}

		// Delete backup.
		$deleted = FieldCloner::instance()->delete_backup( $backup_id );

		if ( $deleted ) {
			wp_send_json_success( [ 'message' => __( 'Backup deleted successfully.', 'silver-assist-acf-clone-fields' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to delete backup.', 'silver-assist-acf-clone-fields' ) ] );
		}
	}

	/**
	 * Handle cleanup backups AJAX request
	 *
	 * @return void
	 */
	public function handle_cleanup_backups(): void {
		// Verify nonce.
		check_ajax_referer( 'acf_clone_backup_action', 'nonce' );

		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'silver-assist-acf-clone-fields' ) ] );
		}

		// This will trigger the cleanup in FieldCloner.
		// We'll use reflection to access the private method.
		$reflection = new \ReflectionClass( FieldCloner::instance() );
		$method     = $reflection->getMethod( 'cleanup_old_backups' );
		$method->setAccessible( true );
		$deleted_count = $method->invoke( FieldCloner::instance() );

		wp_send_json_success(
			[
				'message' => sprintf(
					/* translators: %d: number of deleted backups */
					__( 'Cleanup complete. %d backup(s) deleted.', 'silver-assist-acf-clone-fields' ),
					$deleted_count
				),
			]
		);
	}
}
