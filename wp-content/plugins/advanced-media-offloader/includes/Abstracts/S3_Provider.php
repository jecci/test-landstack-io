<?php

namespace Advanced_Media_Offloader\Abstracts;

abstract class S3_Provider
{

	protected $s3Client;

	/**
	 * Get the client instance.
	 *
	 * @return mixed
	 */
	abstract protected function getClient();

	/**
	 * Get the credentials field for the UI.
	 *
	 * @return mixed
	 */
	abstract public function credentialsField();

	/**
	 * Check for required constants and return any that are missing.
	 *
	 * @param array $constants Associative array of constant names and messages.
	 * @return array Associative array of missing constants and their messages.
	 */
	protected function checkRequiredConstants(array $constants)
	{
		$missingConstants = [];
		foreach ($constants as $constant => $message) {
			if (!defined($constant)) {
				$missingConstants[$constant] = $message;
			}
		}
		return $missingConstants;
	}

	abstract function getBucket();

	abstract function getProviderName();

	abstract function getDomain();

	/**
	 * Upload a file to the specified bucket.
	 *
	 * @param string $file Path to the file to upload.
	 * @param string $key The key to store the file under in the bucket.
	 * @param string $bucket The bucket to upload the file to.
	 * @return string URL of the uploaded object.
	 */
	public function uploadFile($file, $key)
	{
		$client = $this->getClient();
		try {
			$result = $client->putObject([
				'Bucket' => $this->getBucket(),
				'Key' => $key,
				'SourceFile' => $file,
				'ACL' => 'public-read',
			]);
			return $client->getObjectUrl($this->getBucket(), $key);
		} catch (\Exception $e) {
			error_log("Advanced Media Offloader: Error uploading file to S3: {$e->getMessage()}");
			return false;
		}
	}

	/**
	 * Check if an object exists in the bucket.
	 *
	 * @param string $key The object key to check
	 * @return bool True if object exists, false otherwise
	 */
	public function objectExists(string $key): bool
	{
		$client = $this->getClient();
		try {
			$client->headObject([
				'Bucket' => $this->getBucket(),
				'Key' => $key,
			]);
			return true;
		} catch (\Exception $e) {
			// Object doesn't exist or other error
			return false;
		}
	}

	/**
	 * Check the connection to the service.
	 *
	 * @return mixed
	 */
	public function checkConnection()
	{
		$client = $this->getClient();
		try {
			# get bucket info
			$result = $client->headBucket([
				'Bucket' => $this->getBucket(),
				'@http'  => [
					'timeout' => 5,
				],
			]);
			return true;
		} catch (\Exception $e) {
			error_log("Advanced Media Offloader: Error checking connection to S3: {$e->getMessage()}");
			return false;
		}
	}

	protected function getLastCheckTime()
	{
		return get_option('advmo_last_connection_check', '');
	}

	public function TestConnectionHTMLButton()
	{
		$html = sprintf(
			'<button type="button" class="button advmo_js_test_connection">%s</button>',
			esc_html__('Test Connection', 'advanced-media-offloader')
		);

		return $html;
	}

	protected function getConnectionStatusHTML()
	{
		$last_check = $this->getLastCheckTime();
		
		if (empty($last_check)) {
			return '';
		}

		$is_connected = $this->checkConnection();
		$status_text = $is_connected ?
			esc_html__('Connected', 'advanced-media-offloader') :
			esc_html__('Disconnected', 'advanced-media-offloader');

		$icon = $is_connected ? 
			'<span class="dashicons dashicons-yes-alt"></span>' : 
			'<span class="dashicons dashicons-warning"></span>';

		$html = sprintf(
			'<div class="advmo-connection-status %s">%s <span class="advmo-status-text">%s</span> <span class="advmo-status-time">%s: %s</span></div>',
			$is_connected ? 'connected' : 'disconnected',
			$icon,
			esc_html($status_text),
			esc_html__('Last check', 'advanced-media-offloader'),
			esc_html($last_check)
		);

		return $html;
	}

	private function getConstantCodes($missingConstants)
	{
		$html = '';
		foreach ($missingConstants as $constant => $message) {
			if (is_bool($message)) {
				$html .= 'define(\'' . esc_html($constant) . '\', ' . ($message ? 'true' : 'false') . ');' . "\n";
			} else {
				$html .= 'define(\'' . esc_html($constant) . '\', \'' . esc_html(sanitize_title($message)) . '\');' . "\n";
			}
		}
		return $html;
	}

	/**
	 * Render a credential input field.
	 *
	 * @param string $provider_key The provider key (e.g., 'cloudflare_r2').
	 * @param string $field_name The field name (e.g., 'key', 'secret').
	 * @param string $field_label The label for the field.
	 * @param string $field_type The input type ('text' or 'password').
	 * @param string $placeholder Optional placeholder text.
	 * @param string $description Optional description text.
	 * @param string $default Optional default value (used when field value is empty).
	 * @return string The HTML for the field.
	 */
	protected function renderCredentialField($provider_key, $field_name, $field_label, $field_type = 'text', $placeholder = '', $description = '', $default = '')
	{
		$constant_name = 'ADVMO_' . strtoupper($provider_key) . '_' . strtoupper($field_name);
		$is_constant_defined = advmo_credential_exists_in_config($constant_name);
		$field_value = advmo_get_provider_credential($provider_key, $field_name);
		
		// Apply default value if field is empty and default is provided
		if (($field_value === null || $field_value === '') && !empty($default)) {
			$field_value = $default;
		}
		
		$input_name = "advmo_credentials[{$provider_key}][{$field_name}]";
		$input_id = "advmo_credential_{$provider_key}_{$field_name}";
		$disabled = $is_constant_defined ? 'disabled readonly' : '';
		$disabled_class = $is_constant_defined ? 'advmo-field-disabled' : '';
		
		// For password fields with constants, show masked value
		$display_value = $field_value;
		if ($is_constant_defined && $field_type === 'password' && !empty($field_value)) {
			$display_value = str_repeat('•', min(strlen($field_value), 20));
		}
		
		// Handle checkbox fields
		if ($field_type === 'checkbox') {
			$checked = !empty($field_value) ? checked(1, $field_value, false) : '';
			$html = '<div class="advmo-credential-field advmo-checkbox-option ' . esc_attr($disabled_class) . '">';
			$html .= '<input type="checkbox" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="1" ' . $checked . ' ' . $disabled . ' />';
			$html .= '<label for="' . esc_attr($input_id) . '">' . esc_html($field_label) . '</label>';
			
			if (!empty($description)) {
				$html .= '<p class="description">' . esc_html($description) . '</p>';
			}
			
			if ($is_constant_defined) {
				$html .= '<p class="description">' . sprintf(
					esc_html__('This value is set in %s and cannot be changed here.', 'advanced-media-offloader'),
					'<code>wp-config.php</code>'
				) . '</p>';
			}
			
			$html .= '</div>';
			return $html;
		}
		
		$html = '<div class="advmo-credential-field ' . esc_attr($disabled_class) . '">';
		$html .= '<label for="' . esc_attr($input_id) . '">' . esc_html($field_label) . '</label>';
		
		if ($field_type === 'password' && !$is_constant_defined) {
			$html .= '<div class="advmo-password-field-wrapper">';
			$html .= '<input type="password" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($field_value) . '" placeholder="' . esc_attr($placeholder) . '" class="regular-text advmo-password-input" ' . $disabled . ' />';
			$html .= '<button type="button" class="button advmo-toggle-password" aria-label="' . esc_attr__('Toggle password visibility', 'advanced-media-offloader') . '">';
			$html .= '<span class="dashicons dashicons-visibility"></span>';
			$html .= '</button>';
			$html .= '</div>';
		} else {
			$html .= '<input type="' . esc_attr($field_type) . '" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($display_value) . '" placeholder="' . esc_attr($placeholder) . '" class="regular-text" ' . $disabled . ' />';
		}
		
		if ($is_constant_defined) {
			$html .= '<p class="description">' . sprintf(
				esc_html__('This value is set in %s and cannot be changed here.', 'advanced-media-offloader'),
				'<code>wp-config.php</code>'
			) . '</p>';
		}
		
		$html .= '</div>';
		
		return $html;
	}

	public function getCredentialsFieldHTML($credentialFields, $provider_key, $provider_description = '')
	{
		$html = '<div class="advmo-credentials-container">';
		
		// Add provider description if provided
		if (!empty($provider_description)) {
			$html .= '<div class="advmo-provider-description notice notice-info inline">';
			$html .= '<p>' . esc_html($provider_description) . '</p>';
			$html .= '</div>';
		}
		
		// Add informational note about wp-config.php
		$info_note = sprintf(
			esc_html__('%s You can configure credentials here or define them in %s for enhanced security. Constants defined in wp-config.php will take priority and disable these fields.', 'advanced-media-offloader'),
			"<strong>" . esc_html__('Note:', 'advanced-media-offloader') . "</strong>",
			"<code>wp-config.php</code>"
		);
		
		$html .= '<div class="advmo-credentials-info notice notice-info inline">';
		$html .= '<p>' . $info_note . '</p>';
		$html .= '</div>';
		
		// Render credential fields
		$html .= '<div class="advmo-credential-fields">';
		foreach ($credentialFields as $field) {
			$html .= $this->renderCredentialField(
				$provider_key,
				$field['name'],
				$field['label'],
				$field['type'] ?? 'text',
				$field['placeholder'] ?? '',
				$field['description'] ?? '',
				$field['default'] ?? ''
			);
		}
		$html .= '</div>';
		
		// Add connection status if available
		$html .= $this->getConnectionStatusHTML();
		
		// Add action buttons container
		$html .= '<div class="advmo-credentials-actions">';
		$html .= '<button type="submit" class="button button-primary advmo-save-credentials">';
		$html .= '<span class="dashicons dashicons-saved"></span> ';
		$html .= esc_html__('Save Credentials', 'advanced-media-offloader');
		$html .= '</button>';
		$html .= $this->TestConnectionHTMLButton();
		$html .= '</div>';
		
		$html .= '</div>'; // Close advmo-credentials-container

		return $html;
	}

	/**
	 * Delete a file from the specified bucket.
	 *
	 * @param int $attachment_id The WordPress attachment ID.
	 * @return bool True on success, false on failure.
	 */
	public function deleteAttachment($attachment_id)
	{
		try {
			// Delete the main file
			$key = $this->getAttachmentKey($attachment_id);
			$this->deleteS3Object($key);

			if (wp_attachment_is_image($attachment_id)) {
				$base_dir = trailingslashit(dirname($key));
				$this->deleteImageSizes($attachment_id, $base_dir);
				$this->deleteImageBackupSizes($attachment_id, $base_dir);
			}

			return true;
		} catch (\Exception $e) {
			error_log("Advanced Media Offloader: Error deleting file from S3: {$e->getMessage()}");
			return false;
		}
	}

	private function deleteImageSizes($attachment_id, $base_dir)
	{
		// Check if there are any thumbnails to delete
		$metadata = wp_get_attachment_metadata($attachment_id);
		// Make sure $sizes is always defined to allow the removal of original images after the first foreach loop.
		$sizes = ! isset($metadata['sizes']) || ! is_array($metadata['sizes']) ? array() : $metadata['sizes'];

		foreach ($sizes as $size => $sizeinfo) {
			$thumbnail_key = $base_dir . $sizeinfo['file'];
			$this->deleteS3Object($thumbnail_key);
		}

		if (!empty($metadata['original_image'])) {
			$original_image = $base_dir . $metadata['original_image'];
			$this->deleteS3Object($original_image);
		}
	}
	private function deleteImageBackupSizes($attachment_id, $base_dir)
	{
		$backup_sizes = get_post_meta($attachment_id, '_wp_attachment_backup_sizes', true);

		if (!is_array($backup_sizes)) {
			return;
		}

		foreach ($backup_sizes as $size => $sizeinfo) {
			$backup_key = $base_dir . $sizeinfo['file'];
			$this->deleteS3Object($backup_key);
		}
	}

	private function getAttachmentKey(int $attachment_id): string
	{
		$attached_file = get_post_meta($attachment_id, '_wp_attached_file', true);
		$advmo_path = get_post_meta($attachment_id, 'advmo_path', true);

		if (!$attached_file || !$advmo_path) {
			throw new \Exception("Unable to find S3 key for attachment ID {$attachment_id}");
		}

		$file_name = basename($attached_file);
		return trailingslashit($advmo_path) . $file_name;
	}

	private function deleteS3Object(string $key): void
	{
		$client = $this->getClient();
		$bucket = $this->getBucket();

		$client->deleteObject([
			'Bucket' => $bucket,
			'Key'    => $key,
		]);
	}
}
