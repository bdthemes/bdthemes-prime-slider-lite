<?php

namespace PrimeSlider\Admin;

if (!class_exists('PrimeSlider_Rollback_Version')):

	class PrimeSlider_Rollback_Version {

		private $plugin_slug = 'bdthemes-prime-slider';
		private $backup_dir;
		private $htaccess_content = "Order deny,allow\nDeny from all";

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->backup_dir = wp_upload_dir()['basedir'] . '/bdthemes-prime-slider-backups/';
			
			// Add AJAX handlers for rollback functionality
			add_action('wp_ajax_ps_rollback_version', array($this, 'handle_rollback_version'));
			
			// Hook into plugin update process to create backup (BEFORE update)
			add_action('upgrader_pre_install', array($this, 'create_backup_before_update'), 10, 2);
			
			// Hook into plugin update process to create backup (AFTER update - fallback)
			add_action('upgrader_process_complete', array($this, 'create_backup_on_update'), 10, 2);
			
			// Additional hook to catch any plugin updates
			add_action('upgrader_process_complete', array($this, 'check_and_backup_on_any_update'), 5, 2);
			
			// Create initial backup on plugin activation
			add_action('activated_plugin', array($this, 'create_initial_backup'));
			
			// Track activation time for better detection
			add_action('activated_plugin', array($this, 'track_activation_time'));
			
			// Create backup on fresh installation
			add_action('plugins_loaded', array($this, 'create_backup_on_installation'));
			
			// Create backup on activation (more reliable)
			add_action('admin_init', array($this, 'create_backup_on_activation'));
			
			// Create backup if none exists (for existing installations)
			add_action('admin_init', array($this, 'check_and_create_backup'));
		}

		/**
		 * Check and backup on any plugin update (catch-all method)
		 */
		public function check_and_backup_on_any_update($upgrader, $hook_extra) {
			
			// Only proceed if this is a plugin update
			if ($hook_extra['type'] !== 'plugin') {
				return;
			}

			// Check if our plugin directory was modified
			$plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
			$plugin_file = $plugin_dir . '/bdthemes-prime-slider.php';
			
			// Check if our plugin file exists and was recently modified
			if (file_exists($plugin_file)) {
				$file_time = filemtime($plugin_file);
				$current_time = time();
				
				// If file was modified in the last 30 seconds, it was likely updated
				if ($current_time - $file_time < 30) {
					$this->create_backup();
				}
			}
		}

		/**
		 * Create backup when plugin is updated (AFTER the update - fallback)
		 */
		public function create_backup_on_update($upgrader, $hook_extra) {
			
			// Only proceed if this is a plugin update
			if ($hook_extra['type'] !== 'plugin') {
				return;
			}

			// Check if our plugin is being updated - more flexible detection
			$plugin_path = '';
			if (isset($hook_extra['plugin'])) {
				$plugin_path = $hook_extra['plugin'];
			} elseif (isset($hook_extra['plugins']) && is_array($hook_extra['plugins'])) {
				// Handle bulk updates
				foreach ($hook_extra['plugins'] as $plugin) {
					if (strpos($plugin, $this->plugin_slug) !== false) {
						$plugin_path = $plugin;
						break;
					}
				}
			}
			
			// Check if this is our plugin (multiple ways to detect)
			$is_our_plugin = false;
			if ($plugin_path) {
				$expected_paths = array(
					$this->plugin_slug . '/' . $this->plugin_slug . '.php',
					$this->plugin_slug . '.php',
					'bdthemes-prime-slider/bdthemes-prime-slider.php'
				);
				
				foreach ($expected_paths as $expected) {
					if ($plugin_path === $expected) {
						$is_our_plugin = true;
						break;
					}
				}
			}
			
			if ($is_our_plugin) {
				$this->create_backup();
			}
		}

		/**
		 * Hook into pre-update to create backup before update
		 */
		public function create_backup_before_update($upgrader, $hook_extra) {

			// Check if our plugin is being updated - more flexible detection
			$plugin_path = '';
			if (isset($hook_extra['plugin'])) {
				$plugin_path = $hook_extra['plugin'];
			} elseif (isset($hook_extra['plugins']) && is_array($hook_extra['plugins'])) {
				// Handle bulk updates
				foreach ($hook_extra['plugins'] as $plugin) {
					if (strpos($plugin, $this->plugin_slug) !== false) {
						$plugin_path = $plugin;
						break;
					}
				}
			}
			
			// Check if this is our plugin (multiple ways to detect)
			$is_our_plugin = false;
			if ($plugin_path) {
				$expected_paths = array(
					$this->plugin_slug . '/' . $this->plugin_slug . '.php',
					$this->plugin_slug . '.php',
					'bdthemes-prime-slider/bdthemes-prime-slider.php'
				);
				
				foreach ($expected_paths as $expected) {
					if ($plugin_path === $expected) {
						$is_our_plugin = true;
						break;
					}
				}
			}
			
			if ($is_our_plugin) {
				$this->create_backup();
			}
		}

		/**
		 * Create initial backup on plugin activation
		 */
		public function create_initial_backup($plugin) {
			if ($plugin === $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
				$this->create_backup();
			}
		}

		/**
		 * Track activation time for better backup detection
		 */
		public function track_activation_time($plugin) {
			if ($plugin === $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
				update_option('prime_slider_activation_time', time());
			}
		}

		/**
		 * Check and create backup if none exists (for existing installations)
		 */
		public function check_and_create_backup() {
			// Only run once per session
			if (get_transient('prime_slider_backup_checked')) {
				return;
			}

			$backups = get_option('prime_slider_backups', array());
			if (empty($backups)) {
				$this->create_backup();
			}

			set_transient('prime_slider_backup_checked', true, HOUR_IN_SECONDS);
		}

		/**
		 * Create backup on plugin installation (hook into activation)
		 */
		public function create_backup_on_installation() {
			// Check if this is a fresh installation by looking for the installation flag
			$installation_flag = get_option('prime_slider_installation_complete', false);
			
			if (!$installation_flag) {
				$this->create_backup();
				
				// Mark installation as complete
				update_option('prime_slider_installation_complete', true);
			}
		}

		/**
		 * Create backup on plugin activation (more reliable method)
		 */
		public function create_backup_on_activation() {
			// Check if plugin was just activated
			$activation_time = get_option('prime_slider_activation_time', 0);
			$current_time = time();
			
			// If activation time is within the last 5 minutes, consider it a fresh activation
			if ($current_time - $activation_time < 300) {
				$this->create_backup();
			}
		}

		/**
		 * Create backup of current version (ZIP-based)
		 */
		private function create_backup() {
			$current_version = BDTPS_CORE_VER;
			$plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
			
			// Check if backup already exists for this version
			$backups = get_option('prime_slider_backups', array());
			if (isset($backups[$current_version])) {
				return true; // Return true since backup exists
			}
			
			// Check if plugin directory exists
			if (!is_dir($plugin_dir)) {
				return false;
			}
			
			// Create backup directory if it doesn't exist
			if (!wp_mkdir_p($this->backup_dir)) {
				return false;
			}

			// Empty the backup directory before creating a new backup
			$this->empty_directory($this->backup_dir);

			// Test if we can write to the backup directory
			if (!is_writable($this->backup_dir)) {
				return false;
			}

			// Create .htaccess to protect the directory
			$htaccess_file = $this->backup_dir . '.htaccess';
			if (!file_exists($htaccess_file)) {
				$htaccess_result = file_put_contents($htaccess_file, $this->htaccess_content);
			}

			// Create ZIP backup
			$zip_filename = 'bdthemes-prime-slider-v' . $current_version . '-' . date('Y-m-d-H-i-s') . '.zip';
			$zip_file_path = $this->backup_dir . $zip_filename;
			
			// Check if ZipArchive is available
			if (!class_exists('\ZipArchive')) {
				return false;
			}
			
			$zip = new \ZipArchive();
			$zip_result = $zip->open($zip_file_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
			
			if ($zip_result !== TRUE) {
				return false;
			}
			
			// Add plugin files to ZIP
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($plugin_dir),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);
			
			$file_count = 0;
			foreach ($iterator as $file) {
				if (!$file->isDir()) {
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($plugin_dir) + 1); // +1 for the trailing slash
					
					// Add file to ZIP with relative path
					$zip->addFile($filePath, $this->plugin_slug . '/' . $relativePath);
					$file_count++;
				}
			}
			
			$zip->close();
			
			// Verify ZIP file was created
			if (!file_exists($zip_file_path)) {
				return false;
			}
			
			$file_size = filesize($zip_file_path);
			
			// Save backup info to database
			$this->save_backup_info($current_version, $zip_file_path, $file_count, $file_size);

			return true;
		}

		private function empty_directory($dir) {
		    if (!is_dir($dir)) {
		        return;
		    }

		    $files = array_diff(scandir($dir), array('.', '..'));
		    foreach ($files as $file) {
		        $file_path = $dir . DIRECTORY_SEPARATOR . $file;
		        if (is_dir($file_path)) {
		            $this->empty_directory($file_path);
		            rmdir($file_path);
		        } else {
		            unlink($file_path);
		        }
		    }
		}


		/**
		 * Save backup information to database
		 */
		private function save_backup_info($version, $backup_path, $file_count = 0, $file_size = 0) {
			$backups = get_option('prime_slider_backups', array());
			
			$backups[$version] = array(
				'version' => $version,
				'backup_path' => $backup_path,
				'backup_type' => 'zip',
				'file_count' => $file_count,
				'file_size' => $file_size,
				'created_at' => current_time('mysql'),
				'created_timestamp' => time()
			);

			update_option('prime_slider_backups', $backups);
		}

		/**
		 * Rollback Version Content
		 */
		public function prime_slider_rollback_version_content() {
			// Check if debug mode is requested
			if (isset($_GET['debug']) && $_GET['debug'] == '1') {
				$this->show_debug_information();
				return;
			}
			
			$current_version = BDTPS_CORE_VER;
			$available_versions = $this->get_available_rollback_versions();
			?>
			<div class="ps-dashboard-panel"
				bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">
				<div class="ps-dashboard-rollback-version">
					<!-- <div class="bdt-card bdt-card-body"> -->
						
						<div class="ps-rollback-form">
							<div class="bdt-grid bdt-grid-small" bdt-grid>
							<?php if (!empty($available_versions)): ?>	
							<div class="bdt-width-1-2@m">
									<h2 class="bdt-margin-small-bottom bdt-text-bold bdt-text-lead"><strong><?php esc_html_e('Current Version:', 'bdthemes-prime-slider'); ?></strong> <?php echo esc_html($current_version); ?></h2>
									<p><?php esc_html_e('Rollback to the previous version if available. This will restore the plugin to the previous version.', 'bdthemes-prime-slider'); ?></p>
									<div class="ps-rollback-warning bdt-margin-small-top">
											<div class="bdt-alert bdt-alert-warning" bdt-alert>
												<p><strong><?php esc_html_e('Warning:', 'bdthemes-prime-slider'); ?></strong> <?php esc_html_e('Please backup your database before making the rollback.', 'bdthemes-prime-slider'); ?></p>
											</div>
										</div>
								</div>
								<?php endif; ?>
								<div class="bdt-width-1-2@m">
									<?php if (!empty($available_versions)): ?>
										<div class="ps-rollback-form-wrapper">
											<?php 
											$previous_version = $available_versions[0];
											?>
											<div class="bdt-margin-medium-bottom">
												<p><strong><?php esc_html_e('Previous Version:', 'bdthemes-prime-slider'); ?></strong> <?php echo esc_html($previous_version['version']); ?></p>
												
												<p><strong><?php esc_html_e('Backup Date:', 'bdthemes-prime-slider'); ?></strong> <?php echo esc_html($previous_version['created_at']); ?></p>
												<p><strong><?php esc_html_e('Backup Type:', 'bdthemes-prime-slider'); ?></strong> <?php echo esc_html(ucfirst($previous_version['backup_type'])); ?></p>
												<?php if (isset($previous_version['file_count']) && $previous_version['file_count'] > 0): ?>
													<p><strong><?php esc_html_e('Files:', 'bdthemes-prime-slider'); ?></strong> <?php echo esc_html($previous_version['file_count']); ?></p>
												<?php endif; ?>
												<?php if (isset($previous_version['file_size']) && $previous_version['file_size'] > 0): ?>
													<p><strong><?php esc_html_e('Size:', 'bdthemes-prime-slider'); ?></strong> <?php echo esc_html(size_format($previous_version['file_size'])); ?></p>
												<?php endif; ?>
											</div>
											
											<form id="ps-rollback-form" method="post">
												<?php wp_nonce_field('ps-rollback-nonce', 'ep_rollback_nonce'); ?>
												<input type="hidden" id="ps-rollback-version" name="rollback_version" value="<?php echo esc_attr($previous_version['version']); ?>">
												<div class="bdt-form-row">
													<div class="bdt-width-1-1">
														<button type="submit" id="ps-rollback-submit" class="bdt-button bdt-button-danger bdt-flex bdt-flex-middle">
															<i class="dashicons dashicons-update"></i>
															<span class="ps-rollback-button-text bdt-margin-small-left"><?php esc_html_e('Rollback to v', 'bdthemes-prime-slider'); ?><?php echo esc_html($previous_version['version']); ?></span>
														</button>
														<span class="ps-rollback-loading" style="display: none;">
															<i class="dashicons dashicons-update-alt"></i>
															<?php esc_html_e('Rolling back...', 'bdthemes-prime-slider'); ?>
														</span>
													</div>
												</div>
											</form>
										</div>
										
									<?php else: ?>
										<div class="ps-no-versions ">
											<div class="bdt-alert bdt-alert-info" bdt-alert>
												<p><?php esc_html_e('No previous version is available for rollback. This usually means this is a fresh installation or no backup was created during the last update.', 'bdthemes-prime-slider'); ?></p>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<!-- </div> -->
				</div>
			</div>

			<style>
			.ps-rollback-form-wrapper {
				background: #f8f9fa;
				padding: 20px;
				border-radius: 8px;
				border: 1px solid #e9ecef;
			}
			.ps-rollback-loading {
				color: #dc3545;
				font-weight: 600;
			}
			.ps-rollback-loading .dashicons {
				animation: spin 1s linear infinite;
			}
			@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
			}
			</style>

			<script>
			jQuery(document).ready(function($) {
				
				// Handle form submission
				$('#ps-rollback-form').on('submit', function(e) {
					e.preventDefault();
					
					var selectedVersion = $('#ps-rollback-version').val();
					var nonce = $('input[name="ps_rollback_nonce"]').val();
					
					// Confirm rollback
					if (!confirm('<?php esc_html_e('Are you sure you want to rollback to version', 'bdthemes-prime-slider'); ?> ' + selectedVersion + '? <?php esc_html_e('This action cannot be undone.', 'bdthemes-prime-slider'); ?>')) {
						return;
					}
					
					// Show loading state
					$('#ps-rollback-submit').hide();
					$('.ps-rollback-loading').show();
					
					// Send AJAX request
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'ps_rollback_version',
							version: selectedVersion,
							nonce: nonce
						},
						success: function(response) {
							if (response.success) {
								alert(response.data.message);
								location.reload();
							} else {
								alert('Error: ' + response.data.message);
							}
						},
						error: function() {
							alert('<?php esc_html_e('An error occurred during rollback. Please try again.', 'bdthemes-prime-slider'); ?>');
						},
						complete: function() {
							$('#ps-rollback-submit').show();
							$('.ps-rollback-loading').hide();
						}
					});
				});

				// URL validation function
				function isValidUrl(string) {
					try {
						new URL(string);
						return true;
					} catch (_) {
						return false;
					}
				}
			});
			</script>
			<?php
		}

		/**
		 * Get available versions for rollback
		 */
		private function get_available_rollback_versions() {
			$backups = get_option('prime_slider_backups', array());
			$available_versions = array();
			$current_version = BDTPS_CORE_VER;
			
			foreach ($backups as $version => $backup_info) {
				// Skip current version - don't allow rollback to current version
				if ($version === $current_version) {
					continue;
				}
				
				// Check if backup file still exists (ZIP or directory)
				$backup_exists = false;
				if (isset($backup_info['backup_type']) && $backup_info['backup_type'] === 'zip') {
					// Check if ZIP file exists
					$backup_exists = file_exists($backup_info['backup_path']);
				} else {
					// Legacy: check if directory exists
					$backup_exists = is_dir($backup_info['backup_path']);
				}
				
				if ($backup_exists) {
					$available_versions[] = array(
						'version' => $version,
						'backup_path' => $backup_info['backup_path'],
						'backup_type' => isset($backup_info['backup_type']) ? $backup_info['backup_type'] : 'directory',
						'file_count' => isset($backup_info['file_count']) ? $backup_info['file_count'] : 0,
						'file_size' => isset($backup_info['file_size']) ? $backup_info['file_size'] : 0,
						'created_at' => $backup_info['created_at']
					);
				}
			}
			
			// Sort by version (newest first)
			usort($available_versions, function($a, $b) {
				return version_compare($b['version'], $a['version']);
			});
			
			// Only return the most recent previous version (limit to 1)
			return array_slice($available_versions, 0, 1);
		}

		/**
		 * Handle rollback version AJAX request
		 */
		public function handle_rollback_version() {
			// Check nonce for security
			if (!check_ajax_referer('ps-rollback-nonce', 'nonce', false)) {
				wp_send_json_error(array('message' => 'Security check failed'));
			}

			// Check user capabilities
			if (!current_user_can('manage_options')) {
				wp_send_json_error(array('message' => 'Insufficient permissions'));
			}

			$version = sanitize_text_field($_POST['version']);

			if (empty($version)) {
				wp_send_json_error(array('message' => 'Invalid version data'));
			}

			// Perform rollback logic here
			$result = $this->perform_rollback($version);

			if ($result['success']) {
				wp_send_json_success(array(
					'message' => sprintf('Successfully rolled back to version %s', $version)
				));
			} else {
				wp_send_json_error(array('message' => $result['message']));
			}
		}

		/**
		 * Perform the actual rollback
		 */
		private function perform_rollback($version) {
			$backups = get_option('prime_slider_backups', array());
			
			if (!isset($backups[$version])) {
				return array(
					'success' => false,
					'message' => 'Backup for version ' . $version . ' not found'
				);
			}
			
			$backup_info = $backups[$version];
			$backup_path = $backup_info['backup_path'];
			$backup_type = isset($backup_info['backup_type']) ? $backup_info['backup_type'] : 'directory';
			
			$plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
			
			// Create temporary backup of current version
			$temp_backup_dir = $this->backup_dir . 'temp-current-' . time() . '/';
			if (!wp_mkdir_p($temp_backup_dir)) {
				return array(
					'success' => false,
					'message' => 'Could not create temporary backup directory'
				);
			}
			
			// Backup current version
			$current_backup_result = $this->copy_directory($plugin_dir, $temp_backup_dir);
			if (is_wp_error($current_backup_result)) {
				return array(
					'success' => false,
					'message' => 'Could not backup current version: ' . $current_backup_result->get_error_message()
				);
			}
			
			// Remove current plugin files
			$this->remove_directory($plugin_dir);
			
			// Restore from backup based on type
			if ($backup_type === 'zip') {
				// Extract ZIP backup
				if (!class_exists('\ZipArchive')) {
					// Restore current version on failure
					$this->copy_directory($temp_backup_dir, $plugin_dir);
					return array(
						'success' => false,
						'message' => 'ZipArchive class not available'
					);
				}
				
				$zip = new \ZipArchive();
				$zip_result = $zip->open($backup_path);
				
				if ($zip_result !== TRUE) {
					// Restore current version on failure
					$this->copy_directory($temp_backup_dir, $plugin_dir);
					return array(
						'success' => false,
						'message' => 'Could not open ZIP backup: ' . $zip_result
					);
				}
				
				// Extract to plugin directory
				$extract_result = $zip->extractTo($plugin_dir);
				$zip->close();
				
				if (!$extract_result) {
					// Restore current version on failure
					$this->copy_directory($temp_backup_dir, $plugin_dir);
					return array(
						'success' => false,
						'message' => 'Could not extract ZIP backup'
					);
				}
				
				// Move files from subdirectory to plugin directory
				$extracted_plugin_dir = $plugin_dir . '/' . $this->plugin_slug;
				if (is_dir($extracted_plugin_dir)) {
					$move_result = $this->move_directory_contents($extracted_plugin_dir, $plugin_dir);
					if (is_wp_error($move_result)) {
						// Restore current version on failure
						$this->copy_directory($temp_backup_dir, $plugin_dir);
						return array(
							'success' => false,
							'message' => 'Could not move extracted files: ' . $move_result->get_error_message()
						);
					}
					// Remove the empty subdirectory
					rmdir($extracted_plugin_dir);
				}
				
			} else {
				// Legacy: restore from directory
				if (!is_dir($backup_path)) {
					// Restore current version on failure
					$this->copy_directory($temp_backup_dir, $plugin_dir);
					return array(
						'success' => false,
						'message' => 'Backup directory for version ' . $version . ' not found'
					);
				}
				
				$restore_result = $this->copy_directory($backup_path, $plugin_dir);
				if (is_wp_error($restore_result)) {
					// Restore current version on failure
					$this->copy_directory($temp_backup_dir, $plugin_dir);
					return array(
						'success' => false,
						'message' => 'Could not restore version: ' . $restore_result->get_error_message()
					);
				}
			}
			
			// Clean up temporary backup
			$this->remove_directory($temp_backup_dir);
			
			return array(
				'success' => true,
				'message' => 'Rollback completed successfully'
			);
		}

		/**
		 * Copy directory recursively
		 */
		private function copy_directory($source, $destination) {
			if (!is_dir($source)) {
				return new WP_Error('source_not_dir', 'Source is not a directory: ' . $source);
			}
			
			if (!wp_mkdir_p($destination)) {
				return new WP_Error('dest_creation_failed', 'Could not create destination directory: ' . $destination);
			}
			
			$dir = opendir($source);
			if (!$dir) {
				return new WP_Error('source_read_failed', 'Could not read source directory: ' . $source);
			}
			
			while (($file = readdir($dir)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				
				$source_path = $source . '/' . $file;
				$dest_path = $destination . '/' . $file;
				
				if (is_dir($source_path)) {
					$result = $this->copy_directory($source_path, $dest_path);
					if (is_wp_error($result)) {
						closedir($dir);
						return $result;
					}
				} else {
					if (!is_readable($source_path)) {
						closedir($dir);
						return new WP_Error('file_not_readable', 'Source file not readable: ' . $source_path);
					}
					
					if (!copy($source_path, $dest_path)) {
						closedir($dir);
						return new WP_Error('file_copy_failed', 'Failed to copy file: ' . $file . ' from ' . $source_path . ' to ' . $dest_path);
					}
				}
			}
			
			closedir($dir);
			return true;
		}

		/**
		 * Remove directory recursively
		 */
		private function remove_directory($dir) {
			if (!is_dir($dir)) {
				return;
			}
			
			$files = array_diff(scandir($dir), array('.', '..'));
			foreach ($files as $file) {
				$path = $dir . '/' . $file;
				if (is_dir($path)) {
					$this->remove_directory($path);
				} else {
					unlink($path);
				}
			}
			
			rmdir($dir);
		}

		/**
		 * Move directory contents from source to destination
		 */
		private function move_directory_contents($source, $destination) {
			if (!is_dir($source)) {
				return new WP_Error('source_not_dir', 'Source is not a directory: ' . $source);
			}
			
			if (!is_dir($destination)) {
				if (!wp_mkdir_p($destination)) {
					return new WP_Error('dest_creation_failed', 'Could not create destination directory: ' . $destination);
				}
			}
			
			$dir = opendir($source);
			if (!$dir) {
				return new WP_Error('source_read_failed', 'Could not read source directory: ' . $source);
			}
			
			while (($file = readdir($dir)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				
				$source_path = $source . '/' . $file;
				$dest_path = $destination . '/' . $file;
				
				if (is_dir($source_path)) {
					// Create destination subdirectory
					if (!wp_mkdir_p($dest_path)) {
						closedir($dir);
						return new WP_Error('subdir_creation_failed', 'Could not create subdirectory: ' . $dest_path);
					}
					
					// Recursively move contents
					$result = $this->move_directory_contents($source_path, $dest_path);
					if (is_wp_error($result)) {
						closedir($dir);
						return $result;
					}
					
					// Remove empty source directory
					rmdir($source_path);
				} else {
					if (!is_readable($source_path)) {
						closedir($dir);
						return new WP_Error('file_not_readable', 'Source file not readable: ' . $source_path);
					}
					
					if (!rename($source_path, $dest_path)) {
						closedir($dir);
						return new WP_Error('file_move_failed', 'Failed to move file: ' . $file . ' from ' . $source_path . ' to ' . $dest_path);
					}
				}
			}
			
			closedir($dir);
			return true;
		}
	}

endif; 