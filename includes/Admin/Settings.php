<?php
/**
 * Settings Component
 *
 * Manages plugin settings and configuration through WordPress Settings API.
 * Integrates with SilverAssist Settings Hub for centralized admin interface.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Admin
 * @since 1.0.0
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Admin;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;
use SilverAssist\SettingsHub\SettingsHub;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * Handles plugin settings and configuration.
 */
class Settings implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var Settings|null
	 */
	private static ?Settings $instance = null;

	/**
	 * Settings page slug
	 *
	 * @var string
	 */
	private string $page_slug = 'acf-clone-fields';

	/**
	 * Settings group
	 *
	 * @var string
	 */
	private string $settings_group = 'silver_assist_acf_clone_fields_settings';

	/**
	 * Get singleton instance
	 *
	 * @return Settings
	 */
	public static function instance(): Settings {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize settings functionality
	 *
	 * @return void
	 */
	public function init(): void {
		$this->init_hooks();
		$this->register_settings();
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
	 * Determine if settings should load
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
		// Register with Settings Hub immediately (use plugins_loaded timing, after our plugin has been initialized).
		add_action( 'plugins_loaded', [ $this, 'register_with_settings_hub' ], 15 );

		// Settings API.
		add_action( 'admin_init', [ $this, 'init_settings' ] );

		// Enqueue admin assets for settings page.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_settings_assets' ] );

		// Settings validation.
		add_filter( 'pre_update_option_silver_assist_acf_clone_fields_enabled_post_types', [ $this, 'validate_enabled_post_types' ] );

		// Plugin action links.
		add_filter( 'plugin_action_links_silver-assist-acf-clone-fields/silver-assist-acf-clone-fields.php', [ $this, 'add_plugin_action_links' ] );
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	private function register_settings(): void {
		// Register settings with default values.
		$default_settings = $this->get_default_settings();

		foreach ( $default_settings as $option_name => $default_value ) {
			if ( get_option( $option_name ) === false ) {
				add_option( $option_name, $default_value );
			}
		}
	}

	/**
	 * Get default settings
	 *
	 * @return array<string, mixed> Default settings
	 */
	private function get_default_settings(): array {
		return [
			'silver_assist_acf_clone_fields_enabled_post_types' => [ 'post', 'page' ],
			'silver_assist_acf_clone_fields_default_overwrite' => false,
			'silver_assist_acf_clone_fields_create_backup' => true,
			'silver_assist_acf_clone_fields_copy_attachments' => true,
			'silver_assist_acf_clone_fields_validate_data' => true,
			'silver_assist_acf_clone_fields_log_operations' => true,
			'silver_assist_acf_clone_fields_max_source_posts' => 50,
		];
	}

	/**
	 * Register plugin with Settings Hub
	 *
	 * If the Settings Hub is available, register this plugin with it.
	 * Otherwise, fall back to standalone settings page.
	 *
	 * @return void
	 */
	public function register_with_settings_hub(): void {
		// Debug: Log the attempt with timing info.
		// error_log( 'ACF Clone Fields: Attempting to register with Settings Hub at hook: ' . current_filter() );
		// error_log( 'ACF Clone Fields: Checking if SettingsHub class exists: ' . ( class_exists( SettingsHub::class ) ? 'YES' : 'NO' ) );

		// Check if Settings Hub is available.
		if ( ! class_exists( SettingsHub::class ) ) {
			// error_log( 'ACF Clone Fields: Settings Hub class not available, using fallback standalone page' );
			// Fallback: Register standalone settings page if hub is not available.
			add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
			return;
		}

		// error_log( 'ACF Clone Fields: Settings Hub class found, getting instance and registering...' );

		// Get the hub instance.
		$hub = SettingsHub::get_instance();

		// Get actions array for plugin card.
		$actions = $this->get_hub_actions();

		// Register our plugin with the hub.
		$hub->register_plugin(
			$this->page_slug,
			__( 'ACF Clone Fields', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_settings_page' ],
			[
				'description' => __( 'Advanced ACF field cloning system that allows selective copying of custom fields between posts of the same type. Features granular field selection, sidebar interface, and intelligent repeater field cloning.', 'silver-assist-acf-clone-fields' ),
				'version'     => SILVER_ACF_CLONE_VERSION,
				'tab_title'   => __( 'ACF Clone Fields', 'silver-assist-acf-clone-fields' ),
				'actions'     => $actions,
			]
		);

		// error_log( 'ACF Clone Fields: Plugin registered successfully with Settings Hub' );
	}

	/**
	 * Get actions array for Settings Hub plugin card
	 *
	 * @return array<int, array{label: string, callback?: callable, url?: string, class?: string}>
	 */
	private function get_hub_actions(): array {
		$actions = [];

		// Add "View Documentation" button.
		$actions[] = [
			'label' => __( 'View Documentation', 'silver-assist-acf-clone-fields' ),
			'url'   => 'https://github.com/SilverAssist/acf-clone-fields#readme',
			'class' => 'button',
		];

		return $actions;
	}

	/**
	 * Add settings page to WordPress admin menu (fallback)
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'ACF Clone Fields Settings', 'silver-assist-acf-clone-fields' ),
			__( 'ACF Clone Fields', 'silver-assist-acf-clone-fields' ),
			'manage_options',
			$this->page_slug,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Add standalone settings page
	 *
	 * @return void
	 */
	private function add_standalone_settings_page(): void {
		add_options_page(
			__( 'ACF Clone Fields Settings', 'silver-assist-acf-clone-fields' ),
			__( 'ACF Clone Fields', 'silver-assist-acf-clone-fields' ),
			'manage_options',
			$this->page_slug,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Initialize settings sections and fields
	 *
	 * @return void
	 */
	public function init_settings(): void {
		// General Settings Section.
		add_settings_section(
			'acf_clone_general',
			__( 'General Settings', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_general_section' ],
			$this->page_slug
		);

		// Post Types Field.
		add_settings_field(
			'enabled_post_types',
			__( 'Enabled Post Types', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_post_types_field' ],
			$this->page_slug,
			'acf_clone_general'
		);

		// Default Behavior Section.
		add_settings_section(
			'acf_clone_behavior',
			__( 'Default Behavior', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_behavior_section' ],
			$this->page_slug
		);

		// Default Overwrite Field.
		add_settings_field(
			'default_overwrite',
			__( 'Overwrite Existing Fields', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_overwrite_field' ],
			$this->page_slug,
			'acf_clone_behavior'
		);

		// Create Backup Field.
		add_settings_field(
			'create_backup',
			__( 'Create Backup', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_backup_field' ],
			$this->page_slug,
			'acf_clone_behavior'
		);

		// Copy Attachments Field.
		add_settings_field(
			'copy_attachments',
			__( 'Copy Attachments', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_attachments_field' ],
			$this->page_slug,
			'acf_clone_behavior'
		);

		// Advanced Settings Section.
		add_settings_section(
			'acf_clone_advanced',
			__( 'Advanced Settings', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_advanced_section' ],
			$this->page_slug
		);

		// Validate Data Field.
		add_settings_field(
			'validate_data',
			__( 'Validate Field Data', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_validation_field' ],
			$this->page_slug,
			'acf_clone_advanced'
		);

		// Log Operations Field.
		add_settings_field(
			'log_operations',
			__( 'Log Clone Operations', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_logging_field' ],
			$this->page_slug,
			'acf_clone_advanced'
		);

		// Max Source Posts Field.
		add_settings_field(
			'max_source_posts',
			__( 'Max Source Posts', 'silver-assist-acf-clone-fields' ),
			[ $this, 'render_max_posts_field' ],
			$this->page_slug,
			'acf_clone_advanced'
		);

		// Register all settings.
		register_setting( $this->settings_group, 'silver_assist_acf_clone_fields_enabled_post_types' );
		register_setting( $this->settings_group, 'silver_assist_acf_clone_fields_default_overwrite' );
		register_setting( $this->settings_group, 'silver_assist_acf_clone_fields_create_backup' );
		register_setting( $this->settings_group, 'silver_assist_acf_clone_fields_copy_attachments' );
		register_setting( $this->settings_group, 'silver_assist_acf_clone_fields_validate_data' );
		register_setting( $this->settings_group, 'silver_assist_acf_clone_fields_log_operations' );
		register_setting( $this->settings_group, 'silver_assist_acf_clone_fields_max_source_posts' );
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! $this->validate_admin_access() ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'silver-assist-acf-clone-fields' ),
				esc_html__( 'Permission Denied', 'silver-assist-acf-clone-fields' ),
				[ 'response' => 403 ]
			);
		}

		// Handle settings save.
		if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], $this->settings_group . '-options' ) ) {
			$this->save_settings();
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->page_slug ) ); ?>">
				<?php
				wp_nonce_field( $this->settings_group . '-options' );
				do_settings_sections( $this->page_slug );
				submit_button();
				?>
			</form>

			<!-- Plugin Information Card -->
			<div class="card">
				<h2><?php esc_html_e( 'How It Works', 'silver-assist-acf-clone-fields' ); ?></h2>
				<p><?php esc_html_e( 'This plugin provides advanced ACF field cloning capabilities for:', 'silver-assist-acf-clone-fields' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Selective field cloning with granular control', 'silver-assist-acf-clone-fields' ); ?></li>
					<li><?php esc_html_e( 'Support for all ACF field types including repeaters', 'silver-assist-acf-clone-fields' ); ?></li>
					<li><?php esc_html_e( 'Backup and restore functionality', 'silver-assist-acf-clone-fields' ); ?></li>
					<li><?php esc_html_e( 'Batch operations with progress tracking', 'silver-assist-acf-clone-fields' ); ?></li>
					<li><?php esc_html_e( 'Integration with WordPress post edit screens', 'silver-assist-acf-clone-fields' ); ?></li>
				</ul>
				<p>
					<?php esc_html_e( 'The plugin adds a metabox to your enabled post types, allowing you to clone ACF fields from other posts of the same type with intelligent field detection and validation.', 'silver-assist-acf-clone-fields' ); ?>
				</p>
			</div>

			<!-- Plugin Status Card -->
			<div class="card">
				<h2><?php esc_html_e( 'Plugin Status', 'silver-assist-acf-clone-fields' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Version', 'silver-assist-acf-clone-fields' ); ?></th>
						<td><strong><?php echo esc_html( SILVER_ACF_CLONE_VERSION ); ?></strong></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'ACF Pro Status', 'silver-assist-acf-clone-fields' ); ?></th>
						<td>
							<?php if ( function_exists( 'acf_get_field_groups' ) ) : ?>
								<span class="acf-status-active" style="color: #46b450; font-weight: bold;">✓ <?php esc_html_e( 'Active', 'silver-assist-acf-clone-fields' ); ?></span>
							<?php else : ?>
								<span class="acf-status-inactive" style="color: #dc3232; font-weight: bold;">✗ <?php esc_html_e( 'Not Active', 'silver-assist-acf-clone-fields' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enabled Post Types', 'silver-assist-acf-clone-fields' ); ?></th>
						<td><strong><?php echo esc_html( (string) count( get_option( 'silver_assist_acf_clone_fields_enabled_post_types', [] ) ) ); ?></strong> <?php esc_html_e( 'post types configured', 'silver-assist-acf-clone-fields' ); ?></td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render general section description
	 *
	 * @return void
	 */
	public function render_general_section(): void {
		echo '<p>' . esc_html__( 'Configure which post types should have field cloning functionality.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render behavior section description
	 *
	 * @return void
	 */
	public function render_behavior_section(): void {
		echo '<p>' . esc_html__( 'Set default behavior for field cloning operations.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render advanced section description
	 *
	 * @return void
	 */
	public function render_advanced_section(): void {
		echo '<p>' . esc_html__( 'Advanced configuration options for power users.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render post types field
	 *
	 * @return void
	 */
	public function render_post_types_field(): void {
		$enabled_post_types = get_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post', 'page' ] );
		$all_post_types     = get_post_types( [ 'public' => true ], 'objects' );

		echo '<fieldset>';
		foreach ( $all_post_types as $post_type ) {
			$checked = in_array( $post_type->name, $enabled_post_types, true ) ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="silver_assist_acf_clone_fields_enabled_post_types[]" value="%s" %s> %s</label><br>',
				esc_attr( $post_type->name ),
				esc_attr( $checked ),
				esc_html( $post_type->labels->name ?? $post_type->name )
			);
		}
		echo '</fieldset>';
		echo '<p class="description">' . esc_html__( 'Select which post types should show the field cloning meta box.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render overwrite field
	 *
	 * @return void
	 */
	public function render_overwrite_field(): void {
		$value = get_option( 'silver_assist_acf_clone_fields_default_overwrite', false );
		printf(
			'<label><input type="checkbox" name="silver_assist_acf_clone_fields_default_overwrite" value="1" %s> %s</label>',
			checked( $value, true, false ),
			esc_html__( 'Allow overwriting existing field values by default', 'silver-assist-acf-clone-fields' )
		);
		echo '<p class="description">' . esc_html__( 'When enabled, cloning will overwrite existing field values without asking for confirmation.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render backup field
	 *
	 * @return void
	 */
	public function render_backup_field(): void {
		$value = get_option( 'silver_assist_acf_clone_fields_create_backup', true );
		printf(
			'<label><input type="checkbox" name="silver_assist_acf_clone_fields_create_backup" value="1" %s> %s</label>',
			checked( $value, true, false ),
			esc_html__( 'Create backup of existing field values before cloning', 'silver-assist-acf-clone-fields' )
		);
		echo '<p class="description">' . esc_html__( 'Recommended. Allows you to restore previous values if needed.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render attachments field
	 *
	 * @return void
	 */
	public function render_attachments_field(): void {
		$value = get_option( 'silver_assist_acf_clone_fields_copy_attachments', true );
		printf(
			'<label><input type="checkbox" name="silver_assist_acf_clone_fields_copy_attachments" value="1" %s> %s</label>',
			checked( $value, true, false ),
			esc_html__( 'Include image and file attachments when cloning fields', 'silver-assist-acf-clone-fields' )
		);
		echo '<p class="description">' . esc_html__( 'When enabled, image and file fields will reference the same attachments. When disabled, attachment fields will be skipped.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render validation field
	 *
	 * @return void
	 */
	public function render_validation_field(): void {
		$value = get_option( 'silver_assist_acf_clone_fields_validate_data', true );
		printf(
			'<label><input type="checkbox" name="silver_assist_acf_clone_fields_validate_data" value="1" %s> %s</label>',
			checked( $value, true, false ),
			esc_html__( 'Validate field data before cloning', 'silver-assist-acf-clone-fields' )
		);
		echo '<p class="description">' . esc_html__( 'Recommended. Ensures cloned data meets field requirements and constraints.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render logging field
	 *
	 * @return void
	 */
	public function render_logging_field(): void {
		$value = get_option( 'silver_assist_acf_clone_fields_log_operations', true );
		printf(
			'<label><input type="checkbox" name="silver_assist_acf_clone_fields_log_operations" value="1" %s> %s</label>',
			checked( $value, true, false ),
			esc_html__( 'Log field cloning operations for debugging', 'silver-assist-acf-clone-fields' )
		);
		echo '<p class="description">' . esc_html__( 'Helps troubleshoot issues. Logs are stored in the plugin directory.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Render max posts field
	 *
	 * @return void
	 */
	public function render_max_posts_field(): void {
		$value = get_option( 'silver_assist_acf_clone_fields_max_source_posts', 50 );
		printf(
			'<input type="number" name="silver_assist_acf_clone_fields_max_source_posts" value="%d" min="10" max="200" class="small-text">',
			(int) $value
		);
		echo '<p class="description">' . esc_html__( 'Maximum number of source posts to show in the clone interface. Higher values may impact performance.', 'silver-assist-acf-clone-fields' ) . '</p>';
	}

	/**
	 * Save settings
	 *
	 * @return void
	 */
	private function save_settings(): void {
		// Save enabled post types.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method.
		$post_types = $_POST['silver_assist_acf_clone_fields_enabled_post_types'] ?? [];
		$post_types = array_map( 'sanitize_text_field', $post_types );
		update_option( 'silver_assist_acf_clone_fields_enabled_post_types', $post_types );

		// Save boolean options.
		$boolean_options = [
			'silver_assist_acf_clone_fields_default_overwrite',
			'silver_assist_acf_clone_fields_create_backup',
			'silver_assist_acf_clone_fields_copy_attachments',
			'silver_assist_acf_clone_fields_validate_data',
			'silver_assist_acf_clone_fields_log_operations',
		];

		foreach ( $boolean_options as $option ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method.
			$value = isset( $_POST[ $option ] ) && $_POST[ $option ] === '1';
			update_option( $option, $value );
		}

		// Save numeric options.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method.
		$max_posts = (int) ( $_POST['silver_assist_acf_clone_fields_max_source_posts'] ?? 50 );
		$max_posts = max( 10, min( 200, $max_posts ) ); // Clamp between 10-200.
		update_option( 'silver_assist_acf_clone_fields_max_source_posts', $max_posts );

		add_settings_error(
			'silver_assist_acf_clone_fields_messages',
			'silver_assist_acf_clone_fields_message',
			__( 'Settings saved.', 'silver-assist-acf-clone-fields' ),
			'updated'
		);
	}

	/**
	 * Validate enabled post types
	 *
	 * @param mixed $value Input value.
	 * @return array<string> Validated post types
	 */
	public function validate_enabled_post_types( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$valid_post_types = get_post_types( [ 'public' => true ] );
		$validated        = [];

		foreach ( $value as $post_type ) {
			$post_type = sanitize_text_field( $post_type );
			if ( isset( $valid_post_types[ $post_type ] ) ) {
				$validated[] = $post_type;
			}
		}

		return array_unique( $validated );
	}

	/**
	 * Add plugin action links
	 *
	 * @param array<string> $links Existing links.
	 * @return array<string> Modified links
	 */
	public function add_plugin_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=' . $this->page_slug ),
			__( 'Settings', 'silver-assist-acf-clone-fields' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Enqueue settings page assets
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_settings_assets( string $hook_suffix ): void {
		// Only load on settings page.
		$settings_pages = [
			'settings_page_' . $this->page_slug,
			'silverassist-settings_page_' . $this->page_slug,
		];

		if ( ! in_array( $hook_suffix, $settings_pages, true ) ) {
			return;
		}

		// Enqueue settings-specific styles.
		wp_enqueue_style(
			'acf-clone-fields-admin',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/admin.css',
			[],
			'1.0.0'
		);
	}

	/**
	 * Get current settings as array
	 *
	 * @return array<string, mixed> Current settings
	 */
	public function get_settings(): array {
		return [
			'enabled_post_types' => get_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post', 'page' ] ),
			'default_overwrite'  => get_option( 'silver_assist_acf_clone_fields_default_overwrite', false ),
			'create_backup'      => get_option( 'silver_assist_acf_clone_fields_create_backup', true ),
			'copy_attachments'   => get_option( 'silver_assist_acf_clone_fields_copy_attachments', true ),
			'validate_data'      => get_option( 'silver_assist_acf_clone_fields_validate_data', true ),
			'log_operations'     => get_option( 'silver_assist_acf_clone_fields_log_operations', true ),
			'max_source_posts'   => get_option( 'silver_assist_acf_clone_fields_max_source_posts', 50 ),
		];
	}

	/**
	 * Validate admin access
	 *
	 * @return bool
	 */
	private function validate_admin_access(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}
}