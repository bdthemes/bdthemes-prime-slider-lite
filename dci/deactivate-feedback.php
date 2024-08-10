<?php

/**
 * Deactivate Feedback File
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'dci_deactivate_feedback' ) ) {
	function dci_deactivate_feedback( $data ) {
		$api_endpoint = isset( $data['api_endpoint'] ) ? $data['api_endpoint'] : false;
		$public_key   = isset( $data['public_key'] ) ? $data['public_key'] : false;
		$product_id   = isset( $data['product_id'] ) ? $data['product_id'] : false;
		$dci_name     = isset( $data['name'] ) ? $data['name'] : '';
		$nonce        = isset( $data['nonce'] ) ? $data['nonce'] : '';
		$slug         = isset( $data['slug'] ) ? $data['slug'] : '';


		/**
		 * If Core file name not match with Slug
		 */
		$core_file = $slug;

		if ( false !== $data['core_file'] ) {
			$core_file = $data['core_file'];
		}

		$deactivate_url = wp_nonce_url(
			admin_url( 'plugins.php?action=deactivate&plugin=' . $slug . '/' . $core_file . '.php' ),
			'deactivate-plugin_' . $slug . '/' . $core_file . '.php'
		);

		$plugin_page_url = admin_url( 'plugins.php' );

		/**
		 * If deactivate id not match with Slug
		 */
		$plugin_deactivate_id = $slug;

		if ( false !== $data['plugin_deactivate_id'] ) {
			$plugin_deactivate_id = $data['plugin_deactivate_id'];
		}

		?>
		<div class="dci-feedback-wrapper" id="<?php echo esc_attr( $plugin_deactivate_id ); ?>" style="display:none;">
			<div class="dci-feedback-card">
				<h2><?php esc_html_e( 'Give feedback', 'data-collector-insights' ); ?></h2>
				<p><?php esc_html_e( 'Goodbyes are never easy. If you have a moment, please share your feedback on how we can improve.', 'data-collector-insights' ); ?>
				</p>
				<form method="get" class="dci-notice-data">
					<input type="hidden" name="nonce" value="<?php echo esc_html( $nonce ); ?>">
					<input type="hidden" name="slug" value="<?php echo esc_html( $slug ); ?>">
					<input type="hidden" name="product_id" value="<?php echo esc_html( $product_id ); ?>">
					<input type="hidden" name="public_key" value="<?php echo esc_html( $public_key ); ?>">
					<input type="hidden" name="api_endpoint" value="<?php echo esc_html( $api_endpoint ); ?>/deactivate">
					<div class="dci-feedback-tabs">
						<div class="checkbox-group">
							<div class="checkbox">
								<label class="checkbox-wrapper">
									<input type="checkbox" class="checkbox-input" name="switching-domain" />
									<span class="checkbox-tile">
										<span class="checkbox-icon">
											<svg width="192" height="192" fill="currentColor" viewBox="0 0 24 24"
												xmlns="http://www.w3.org/2000/svg">
												<path
													d="M16.4437 2.00021C14.9719 1.98733 13.5552 2.55719 12.4986 3.58488L12.4883 3.59504L11.6962 4.38801C11.3059 4.77876 11.3063 5.41192 11.697 5.80222C12.0878 6.19252 12.721 6.19216 13.1113 5.80141L13.8979 5.01386C14.5777 4.35511 15.4855 3.99191 16.4262 4.00014C17.3692 4.00839 18.2727 4.38923 18.9416 5.06286C19.6108 5.73671 19.9916 6.64971 19.9998 7.6056C20.008 8.55874 19.6452 9.47581 18.9912 10.1607L16.2346 12.9367C15.8688 13.3052 15.429 13.5897 14.9453 13.7714C14.4616 13.9531 13.945 14.0279 13.4304 13.9907C12.9159 13.9536 12.4149 13.8055 11.9616 13.5561C11.5083 13.3067 11.1129 12.9617 10.8027 12.5441C10.4734 12.1007 9.847 12.0083 9.40364 12.3376C8.96029 12.6669 8.86785 13.2933 9.19718 13.7367C9.67803 14.384 10.2919 14.9202 10.9975 15.3084C11.7032 15.6966 12.4838 15.9277 13.2866 15.9856C14.0893 16.0435 14.8949 15.9268 15.6486 15.6437C16.4022 15.3606 17.0861 14.9177 17.654 14.3457L20.4168 11.5635L20.429 11.551C21.4491 10.4874 22.0125 9.0642 21.9997 7.58834C21.987 6.11247 21.3992 4.69931 20.3607 3.65359C19.3221 2.60764 17.9155 2.01309 16.4437 2.00021Z"
													fill="#000000" />
												<path
													d="M10.7134 8.01444C9.91064 7.95655 9.10506 8.0732 8.35137 8.35632C7.59775 8.63941 6.91382 9.08232 6.34597 9.65431L3.5831 12.4365L3.57097 12.449C2.5508 13.5126 1.98748 14.9358 2.00021 16.4117C2.01295 17.8875 2.60076 19.3007 3.6392 20.3464C4.67787 21.3924 6.08439 21.9869 7.55623 21.9998C9.02807 22.0127 10.4447 21.4428 11.5014 20.4151L11.5137 20.4029L12.3012 19.6099C12.6903 19.218 12.6881 18.5849 12.2962 18.1957C11.9043 17.8066 11.2712 17.8088 10.882 18.2007L10.1011 18.9871C9.42133 19.6452 8.51402 20.0081 7.57373 19.9999C6.63074 19.9916 5.72728 19.6108 5.05834 18.9371C4.38918 18.2633 4.00839 17.3503 4.00014 16.3944C3.99191 15.4412 4.35479 14.5242 5.00874 13.8393L7.76537 11.0633C8.13118 10.6948 8.57097 10.4103 9.05467 10.2286C9.53836 10.0469 10.0549 9.97215 10.5695 10.0093C11.0841 10.0464 11.585 10.1945 12.0383 10.4439C12.4917 10.6933 12.887 11.0383 13.1972 11.4559C13.5266 11.8993 14.1529 11.9917 14.5963 11.6624C15.0397 11.3331 15.1321 10.7067 14.8028 10.2633C14.3219 9.61599 13.708 9.07982 13.0024 8.69161C12.2968 8.30338 11.5161 8.07233 10.7134 8.01444Z"
													fill="#000000" />
											</svg>
										</span>
										<span
											class="checkbox-label"><?php esc_html_e( 'Switching Domain', 'data-collector-insights' ); ?></span>
									</span>
								</label>
							</div>
							<div class="checkbox">
								<label class="checkbox-wrapper">
									<input type="checkbox" class="checkbox-input" name="couldnt-understand" />
									<span class="checkbox-tile">
										<span class="checkbox-icon">
											<svg width="192" height="192" fill="currentColor" viewBox="0 0 64 64"
												xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
												aria-hidden="true" role="img" class="iconify iconify--emojione-monotone"
												preserveAspectRatio="xMidYMid meet">
												<path
													d="M61.539 26.797C58.974 12.239 46.316 2 32.031 2c-1.73 0-3.482.15-5.246.461C10.471 5.338-.425 20.895 2.452 37.215C5.018 51.772 17.677 62.01 31.961 62.01c1.729 0 3.482-.15 5.244-.461c16.316-2.877 27.213-18.432 24.334-34.752M54.523 47.78c-4.213 6.016-10.518 10.031-17.752 11.307c-1.592.28-3.21.423-4.811.423c-13.351 0-24.726-9.559-27.047-22.729C2.281 21.848 12.288 7.556 27.22 4.923c1.591-.28 3.21-.423 4.812-.423c13.35 0 24.725 9.56 27.047 22.731c1.275 7.235-.343 14.533-4.556 20.549"
													fill="#000000"></path>
												<circle cx="42.383" cy="24.683" r="5" fill="#000000"></circle>
												<circle cx="19.732" cy="28.676" r="4.999" fill="#000000"></circle>
												<path
													d="M43.27 41.832c-5.766-1.549-12.049-.428-16.93 3.013c-1.205.87 1.053 4.028 2.252 3.153c3.223-2.268 8.352-3.835 13.66-2.432c1.422.376 2.535-3.309 1.018-3.734"
													fill="#000000"></path>
											</svg>
										</span>
										<span
											class="checkbox-label"><?php esc_html_e( 'Couldn\'t understand', 'data-collector-insights' ); ?></span>
									</span>
								</label>
							</div>
							<div class="checkbox">
								<label class="checkbox-wrapper">
									<input type="checkbox" class="checkbox-input" name="found-a-better-plugin" />
									<span class="checkbox-tile">
										<span class="checkbox-icon">
											<svg width="192" height="192" fill="currentColor" version="1.1"
												xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
												viewBox="0 0 1800 1800" enable-background="new 0 0 1800 1800"
												xml:space="preserve">
												<g>
													<path fill="#333333" d="M900.114,54.882c-329.509,0-597.583,268.077-597.583,597.59c0,219.592,118.159,418.518,309.714,523.794
		v152.835h-0.127v122.121h-0.172v185.238h172.682c3.58,60.518,53.924,108.656,115.315,108.656
		c61.39,0,111.736-48.139,115.31-108.656h172.571v-122.122h0.177v-185.237h-0.019v-152.835
		c191.557-105.276,309.715-304.203,309.715-523.794C1497.697,322.959,1229.623,54.882,900.114,54.882z M675.235,1392.218h449.649
		v59.005H675.235V1392.218z M899.943,1682.002c-29.441,0-48.627-22.507-51.876-45.541h103.788
		C948.464,1662.119,926.504,1682.002,899.943,1682.002z M1124.708,1573.344H675.063v-59.005h449.645V1573.344z M1142.116,1129.132
		l-17.25,8.778v189.457H931.559V861l187.201-187.21c12.323-12.323,12.323-32.302-0.005-44.63
		c-12.318-12.318-32.302-12.323-44.629,0.004L900,803.299L725.875,629.165c-12.323-12.323-32.307-12.328-44.63-0.004
		c-12.328,12.328-12.328,32.307-0.005,44.63L868.441,861v466.367H675.362V1137.91l-17.249-8.778
		c-180.4-91.778-292.465-274.427-292.465-476.66c0-294.71,239.761-534.471,534.466-534.471
		c294.706,0,534.466,239.761,534.466,534.471C1434.58,854.705,1322.516,1037.354,1142.116,1129.132z" />
													<path fill="#333333" d="M1066.667,246.225c-17.43,0-31.558,14.128-31.558,31.559s14.128,31.558,31.558,31.558
		c49.641,0,165.99,59.634,165.99,175.279c0,17.431,14.128,31.559,31.558,31.559c17.431,0,31.559-14.128,31.559-31.559
		C1295.773,328.101,1146.624,246.225,1066.667,246.225z" />
													<path fill="#333333" d="M209.331,712.881c0-17.43-14.128-31.558-31.558-31.558H34.686c-17.43,0-31.558,14.128-31.558,31.558
		s14.128,31.558,31.558,31.558h143.087C195.203,744.439,209.331,730.312,209.331,712.881z" />
													<path fill="#333333" d="M220.157,300.096c6.164,6.163,14.239,9.245,22.317,9.245c8.075,0,16.153-3.082,22.313-9.241
		c12.328-12.328,12.328-32.307,0.004-44.629L163.623,154.297c-12.318-12.319-32.303-12.323-44.63-0.004
		c-12.327,12.327-12.327,32.307-0.004,44.63L220.157,300.096z" />
													<path fill="#333333" d="M220.17,1125.662l-101.178,101.174c-12.327,12.327-12.327,32.307-0.004,44.634
		c6.164,6.164,14.238,9.246,22.317,9.246c8.074,0,16.153-3.082,22.312-9.246l101.179-101.173
		c12.327-12.327,12.327-32.307,0.004-44.625C252.478,1113.344,232.493,1113.344,220.17,1125.662z" />
													<path fill="#333333" d="M1765.314,681.323h-143.083c-17.43,0-31.559,14.128-31.559,31.558s14.129,31.558,31.559,31.558h143.083
		c17.43,0,31.558-14.128,31.558-31.558S1782.744,681.323,1765.314,681.323z" />
													<path fill="#333333" d="M1557.521,309.341c8.074,0,16.153-3.082,22.316-9.241l101.174-101.173
		c12.322-12.327,12.322-32.307,0-44.634c-12.328-12.319-32.307-12.319-44.635,0l-101.173,101.173
		c-12.323,12.328-12.323,32.307,0,44.634C1541.368,306.259,1549.447,309.341,1557.521,309.341z" />
													<path fill="#333333" d="M1579.829,1125.662c-12.318-12.318-32.302-12.318-44.63,0.01c-12.323,12.318-12.323,32.298,0.005,44.625
		l101.178,101.173c6.159,6.164,14.238,9.246,22.312,9.246c8.075,0,16.154-3.082,22.318-9.246
		c12.322-12.327,12.322-32.307-0.005-44.634L1579.829,1125.662z" />
												</g>
											</svg>
										</span>
										<span
											class="checkbox-label"><?php esc_html_e( 'Found a better plugin', 'data-collector-insights' ); ?></span>
									</span>
								</label>
							</div>
							<div class="checkbox">
								<label class="checkbox-wrapper">
									<input type="checkbox" class="checkbox-input" name="missing-a-specific-feature" />
									<span class="checkbox-tile">
										<span class="checkbox-icon">
											<svg width="192" height="192" fill="currentColor" version="1.1" id="Layer_1"
												xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
												viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve">
												<path d="M499.5,385.4L308.9,57.2c-31.8-52.9-74.1-52.9-105.9,0L12.5,385.4c-31.8,52.9,0,95.3,63.5,95.3h360
	C499.5,480.7,531.3,438.3,499.5,385.4z M298.4,438.3h-84.7v-84.7h84.7V438.3z M298.4,311.3h-84.7V120.7h84.7V311.3z" />
											</svg>
										</span>
										<span
											class="checkbox-label"><?php esc_html_e( 'Missing a specific feature', 'data-collector-insights' ); ?></span>
									</span>
								</label>
							</div>
							<div class="checkbox">
								<label class="checkbox-wrapper">
									<input type="checkbox" class="checkbox-input" name="not-working" />
									<span class="checkbox-tile">
										<span class="checkbox-icon">
											<svg width="192" height="192" fill="currentColor" version="1.1" id="_x32_"
												xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
												viewBox="0 0 512 512" xml:space="preserve">
												<g>
													<path class="st0" d="M462.052,217.01c4.348-8.909,6.886-18.786,6.891-29.154c0-17.424-6.797-33.38-17.834-45.34
		c1.336-5.135,2.079-10.771,2.079-16.89c0.004-22.403-10.964-42.247-28.006-54.396c-0.824-32.214-24.277-59.236-56.299-64.421
		l-0.152-0.033l-0.443-0.048l-22.575-3.282l-0.821-0.09l-4.66-0.517h0.021C326.469,1.271,314.955-0.017,292.773,0
		c-11.612,0-25.672,0.328-44.273,1.001c-22.514,0.836-48.399,3.363-72.611,7.604c-24.237,4.257-46.689,10.115-62.87,18.17
		C74.061,46.201,48.36,84.363,40.584,132.77c-1.657,10.238-2.466,20.55-2.466,30.771c0.046,45.676,15.956,89.883,44.77,118.784
		c12.855,12.846,24.946,23.313,36.472,33.288h-0.008c14.602,12.65,28.059,24.356,39.798,38.162
		c21.894,25.742,38.543,48.44,49.859,65.889c5.66,8.712,9.984,16.119,12.928,21.837c1.473,2.863,2.601,5.299,3.368,7.202
		c0.771,1.886,1.161,3.306,1.21,3.708l1.23,7.432l-1.226-7.432c2.842,17.162,10.529,31.976,21.558,42.608
		c11.004,10.623,25.488,16.988,41.205,16.981c13.495,0.008,26.181-4.692,36.542-12.978c10.378-8.276,18.495-20.048,23.462-34.232
		v0.008c5.185-14.774,7.145-28.974,7.141-42.05c-0.004-27.235-8.426-49.664-13.47-63.248l0.11,0.295l-1.386-3.774l-0.131-0.344
		l0.135,0.369c-3.413-9.261-8.966-16.923-14.352-24.503l0.004,0.017c-1.862-2.625-4.647-6.546-6.923-10.205
		c-0.333-0.533-0.62-1.034-0.919-1.542c2.621-0.476,6.226-0.935,11.173-1.164h75.995c37.083-0.017,67.201-30.139,67.218-67.234
		C473.881,238.404,469.209,226.591,462.052,217.01z M406.663,287.057h-76.75c-14.409,0.616-29.954,3.076-37.866,11.575
		c-21.312,22.854,13.65,50.966,19.983,68.284c6.296,17.284,20.48,50.318,7.465,87.414c-6.247,17.785-18.318,26.087-30.208,26.087
		c-14.155,0-28.055-11.747-31.607-33.158c-3.606-21.837-38.666-71.836-74.47-113.952c-23.174-27.268-49.49-44.815-77.984-73.312
		c-50.47-50.467-50.422-168.791,21.911-204.967c25.84-12.929,80.179-20.886,122.532-22.444c18.687-0.706,32.366-1.001,43.104-1.001
		c22.884,0,32.407,1.362,48.638,3.15l22.395,3.249c16.94,2.756,29.876,17.325,29.876,35.028c0,3.232-0.488,6.333-1.337,9.286h-6.336
		c-4.443,0-8.023,3.576-8.023,8.006c0,4.429,3.58,8.023,8.023,8.023h22.522c7.929,6.48,13.072,16.243,13.072,27.3
		c0,6.932-1.396,13.355-6.252,18.802h-14.614c-4.43,0-8.01,3.594-8.01,8.023c0,4.43,3.58,8.006,8.01,8.006h23.412
		c8.01,6.497,13.207,16.292,13.207,27.4c0,10.418-4.519,19.704-11.66,26.168h-17.473c-4.426,0-8.019,3.61-8.019,8.039
		c0,4.422,3.593,8.007,8.019,8.007h23.014c6.308,5.364,11.066,12.928,11.066,21.345
		C442.303,271.102,426.367,287.057,406.663,287.057z" />
												</g>
											</svg>
										</span>
										<span
											class="checkbox-label"><?php esc_html_e( 'Not working', 'data-collector-insights' ); ?></span>
									</span>
								</label>
							</div>
							<div class="checkbox">
								<label class="checkbox-wrapper">
									<input type="checkbox" class="checkbox-input" name="others" />
									<span class="checkbox-tile">
										<span class="checkbox-icon">
											<svg width="192" height="192" fill="currentColor" viewBox="0 0 32 32" id="icon"
												xmlns="http://www.w3.org/2000/svg">
												<defs>
													<style>
														.dci-icon-others-1 {
															fill: none;
														}
													</style>
												</defs>
												<path
													d="M27.71,4.29a1,1,0,0,0-1.05-.23l-22,8a1,1,0,0,0,0,1.87l9.6,3.84,3.84,9.6A1,1,0,0,0,19,28h0a1,1,0,0,0,.92-.66l8-22A1,1,0,0,0,27.71,4.29ZM19,24.2l-2.79-7L21,12.41,19.59,11l-4.83,4.83L7.8,13,25.33,6.67Z" />
												<rect id="_Transparent_Rectangle_" data-name="&lt;Transparent Rectangle&gt;"
													class="dci-icon-others-1" width="32" height="32" />
											</svg>
										</span>
										<span
											class="checkbox-label"><?php esc_html_e( 'Others', 'data-collector-insights' ); ?></span>
									</span>
								</label>
							</div>
						</div>
					</div>
					<div class="dci-feedback-comments">
						<label>
							<?php esc_html_e( 'What is the main reason for deactivating?', 'data-collector-insights' ); ?>
						</label>
						<textarea name="dci-feedback-input-comments" rows="4"></textarea>
					</div>
					<div class="dci-feedback-actions">
						<a class="button" href="<?php echo esc_url( $deactivate_url ); ?>">
							<?php esc_html_e( 'Skip & Deactivate', 'data-collector-insights' ); ?>
						</a>
						<div>
							<a class="button" href="<?php echo esc_url( $plugin_page_url ); ?>">
								<?php esc_html_e( 'Cancel', 'data-collector-insights' ); ?>
							</a>
							<button name="dci_status_sub_and_dea" value="skip" type="button"
								class="dci-feedback-submit-btn button button-secondary"
								data-deactivate-url="<?php echo esc_url( $deactivate_url ); ?>">
								<?php esc_html_e( 'Submit & Deactivate', 'data-collector-insights' ); ?>
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}
