<?php
/**
 * Template for single Signature post.
 *
 * Preview chrome (page UI) uses modern CSS. Only .signature-card email-safe
 * markup is copied to the clipboard for email clients.
 *
 * @var WP_Post $post
 */

global $post;

require_once dirname( __DIR__ ) . '/includes/esp-signature-render.php';

$preview_key = isset( $_GET['esp_preview'] ) ? sanitize_key( wp_unslash( $_GET['esp_preview'] ) ) : '';
$context     = esp_build_signature_context( $post, array(), $preview_key );

$primary        = $context['primary'];
$secondary      = $context['secondary'];
$neutral        = $context['neutral'];
$tertiary       = $context['tertiary'];
$fonts_url      = $context['fonts_url'];
$heading_css    = $context['heading_css'];
$body_css       = $context['body_css'];
$avatar_display = (int) $context['avatar_display'];
$need_render    = $context['need_render'];
$is_preview     = $context['is_preview'];
$regenerate_confirm = $is_preview
	? __( 'Regenerate this preview?', 'email-signatures-pro' )
	: __( 'This will replace the images used by your current email signature. After regenerating, copy the new signature and replace the old one in your email app. Continue?', 'email-signatures-pro' );

$html2canvas_url = plugins_url( 'assets/js/html2canvas.min.js', dirname( __DIR__ ) . '/email-signatures-rtd.php' );
if ( ! file_exists( dirname( __DIR__ ) . '/assets/js/html2canvas.min.js' ) ) {
	$html2canvas_url = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $context['title'] ); ?> – Signature</title>
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<?php if ( $fonts_url ) : ?>
		<?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Template-specific Google Fonts for signature preview */ ?>
		<link rel="stylesheet" href="<?php echo esc_url( $fonts_url ); ?>" />
	<?php endif; ?>
	<style>
		<?php /* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Font stacks are hardcoded literals. */ ?>
		body{margin:0;padding:48px 32px;background:#f0f2f5;color:#1d2327;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;line-height:1.5;}
		.esp-page{max-width:720px;margin:0;}
		.esp-page-header{margin:0 0 28px;padding:0 0 20px;border-bottom:1px solid #dcdcde;}
		.esp-page-title{margin:0 0 6px;font-family:<?php echo $heading_css; ?>;font-size:28px;font-weight:700;color:<?php echo esc_html( $primary ); ?>;line-height:1.2;}
		.esp-page-subtitle{margin:0;font-size:15px;color:#646970;}
		.esp-preview-banner{margin:0 0 16px;padding:10px 14px;background:#fff8e5;border:1px solid #f0d58a;border-radius:4px;font-size:14px;color:#6a5d1b;}
		.esp-preview{background:#fff;max-width:380px;padding:24px;border:1px solid #dcdcde;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,.04);}
		.esp-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px;align-items:center;}
		.esp-btn{display:inline-block;padding:10px 18px;font-family:inherit;font-size:14px;font-weight:500;line-height:1.4;color:#fff;border:none;border-radius:4px;cursor:pointer;transition:opacity .15s ease;}
		.esp-btn:hover{opacity:.9;}
		.esp-btn:disabled{opacity:.65;cursor:not-allowed;}
		.esp-btn--primary{background:<?php echo esc_html( $primary ); ?>;}
		.esp-btn--secondary{background:<?php echo esc_html( $neutral ); ?>;}
		html:not(.esp-js) #esp-copy-btn{display:none;}
		.esp-generating{font-size:14px;color:#646970;}
		.esp-back{margin:20px 0 0;padding:0;}
		.esp-back a{font-size:14px;color:<?php echo esc_html( $primary ); ?>;text-decoration:none;}
		.esp-back a:hover{text-decoration:underline;}
		.esp-instructions{margin:40px 0 0;padding:28px 0 0;border-top:1px solid #dcdcde;max-width:600px;}
		.esp-instructions h2{margin:0 0 4px;font-size:18px;font-weight:600;color:#1d2327;}
		.esp-instructions p.esp-instructions-intro{margin:0 0 20px;font-size:14px;color:#646970;}
		.esp-instructions details{margin:0 0 12px;background:#fff;border:1px solid #dcdcde;border-radius:6px;overflow:hidden;}
		.esp-instructions summary{padding:14px 16px;font-size:15px;font-weight:600;color:<?php echo esc_html( $primary ); ?>;cursor:pointer;list-style:none;display:flex;align-items:center;gap:8px;}
		.esp-instructions summary::-webkit-details-marker{display:none;}
		.esp-instructions summary::before{content:'\25B6';font-size:10px;color:#646970;transition:transform .15s ease;}
		.esp-instructions details[open] summary::before{transform:rotate(90deg);}
		.esp-instructions summary:hover{background:#f6f7f7;}
		.esp-instructions .esp-steps{margin:0;padding:0 16px 16px 40px;font-size:14px;line-height:1.7;color:#1d2327;}
		.esp-instructions .esp-steps li{margin:0 0 6px;}
		.esp-instructions .esp-steps li:last-child{margin:0;}
		.esp-instructions .esp-steps strong{font-weight:600;}
		.esp-instructions .esp-steps code{background:#f0f2f5;padding:2px 5px;border-radius:3px;font-size:13px;}
		.esp-header{position:relative;width:380px;height:86px;overflow:hidden;}
		.esp-header-bar{position:absolute;left:40px;top:19px;width:330px;height:40px;background:<?php echo esc_html( $primary ); ?>;z-index:1;}
		.esp-header-slant{position:absolute;left:370px;top:19px;width:0;height:0;border-top:40px solid <?php echo esc_html( $primary ); ?>;border-right:10px solid transparent;z-index:1;}
		.esp-header-avatar{position:absolute;left:0;top:0;width:<?php echo (int) $avatar_display; ?>px;height:<?php echo (int) $avatar_display; ?>px;border-radius:50%;overflow:hidden;background-color:#e4e5e7;z-index:2;}
		.esp-header-avatar-img{display:block;width:<?php echo (int) $avatar_display; ?>px;height:<?php echo (int) $avatar_display; ?>px;object-fit:cover;object-position:center;border:0;}
		.esp-header-name{position:absolute;left:102px;top:21px;height:40px;line-height:40px;z-index:3;font-family:<?php echo $heading_css; ?>;font-size:26px;font-weight:700;color:#ffffff;white-space:nowrap;}
		.esp-header-title{position:absolute;left:102px;top:62px;line-height:22px;z-index:3;font-family:<?php echo $heading_css; ?>;font-size:17.5px;font-weight:500;color:<?php echo esc_html( $secondary ); ?>;white-space:nowrap;}
		.esp-mono{font-family:<?php echo $body_css; ?>;font-size:17.5px;font-weight:300;line-height:20px;color:<?php echo esc_html( $neutral ); ?>;white-space:nowrap;}
		<?php /* phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
	</style>
	<script>document.documentElement.className += ' esp-js';</script>
</head>
<body>
	<div class="esp-page">
		<header class="esp-page-header">
			<h1 class="esp-page-title"><?php echo esc_html( $context['title'] ); ?></h1>
			<p class="esp-page-subtitle">
				<?php
				if ( $is_preview ) {
					esc_html_e( 'Preview — changes not saved', 'email-signatures-pro' );
				} else {
					esc_html_e( 'Email signature preview', 'email-signatures-pro' );
				}
				?>
			</p>
		</header>

		<?php if ( $is_preview ) : ?>
			<p class="esp-preview-banner"><?php esc_html_e( 'This is a preview of unsaved changes. Save the signature post to persist updates.', 'email-signatures-pro' ); ?></p>
		<?php endif; ?>

		<div class="esp-preview">
			<div class="signature-card">
				<?php
				if ( $need_render ) {
					include dirname( __DIR__ ) . '/templates/partials/signature-capture.php';
				} else {
					echo esp_render_signature_email_html( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Email-safe HTML from partial.
				}
				?>
			</div>
		</div>

		<div class="esp-actions">
			<?php if ( $need_render ) : ?>
				<span class="esp-generating" id="esp-generating-status"><?php esc_html_e( 'Generating signature…', 'email-signatures-pro' ); ?></span>
			<?php elseif ( current_user_can( 'read' ) ) : ?>
				<button type="button" id="esp-copy-btn" class="esp-btn esp-btn--primary"><?php esc_html_e( 'Copy Signature', 'email-signatures-pro' ); ?></button>
			<?php endif; ?>

			<?php if ( current_user_can( 'edit_post', $post->ID ) ) : ?>
				<button type="button" id="esp-regenerate-btn" class="esp-btn esp-btn--secondary"><?php esc_html_e( 'Regenerate Signature', 'email-signatures-pro' ); ?></button>
			<?php endif; ?>
		</div>

		<p class="esp-back">
			<?php
			$esp_back_url = get_edit_post_link( $post->ID, 'raw' );
			if ( ! $esp_back_url ) {
				$esp_back_url = admin_url( 'edit.php?post_type=signature' );
			}
			?>
			<a href="<?php echo esc_url( $esp_back_url ); ?>">&lt;- <?php esc_html_e( 'Back to WordPress', 'email-signatures-pro' ); ?></a>
		</p>

		<div class="esp-instructions">
			<h2><?php esc_html_e( 'How to Use Your Signature', 'email-signatures-pro' ); ?></h2>
			<p class="esp-instructions-intro"><?php esc_html_e( 'Click "Copy Signature" above, then follow the steps for your email client.', 'email-signatures-pro' ); ?></p>

			<details>
				<summary><?php esc_html_e( 'Gmail (Web)', 'email-signatures-pro' ); ?></summary>
				<ol class="esp-steps">
					<li><?php esc_html_e( 'Open Gmail and click the gear icon in the top-right corner, then click "See all settings".', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Scroll down to the "Signature" section on the General tab.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Click "+ Create new" to add a new signature, or select an existing one to replace.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Click inside the signature text box and delete any existing content.', 'email-signatures-pro' ); ?></li>
					<li><?php
						printf(
							/* translators: %s: keyboard shortcut */
							esc_html__( 'Paste your copied signature (%s on Mac, %s on Windows).', 'email-signatures-pro' ),
							'<code>Cmd + V</code>',
							'<code>Ctrl + V</code>'
						);
					?></li>
					<li><?php esc_html_e( 'Under "Signature defaults", choose this signature for new emails and/or replies.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Scroll to the bottom and click "Save Changes".', 'email-signatures-pro' ); ?></li>
				</ol>
			</details>

			<details>
				<summary><?php esc_html_e( 'Outlook (Desktop App — Windows)', 'email-signatures-pro' ); ?></summary>
				<ol class="esp-steps">
					<li><?php
						printf(
							esc_html__( 'Go to %1$s > %2$s > %3$s > %4$s.', 'email-signatures-pro' ),
							'<strong>File</strong>',
							'<strong>Options</strong>',
							'<strong>Mail</strong>',
							'<strong>Signatures</strong>'
						);
					?></li>
					<li><?php esc_html_e( 'Click "New" to create a new signature, or select an existing one to edit.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Click inside the "Edit signature" box and clear any existing content.', 'email-signatures-pro' ); ?></li>
					<li><?php
						printf(
							esc_html__( 'Paste your copied signature (%s).', 'email-signatures-pro' ),
							'<code>Ctrl + V</code>'
						);
					?></li>
					<li><?php esc_html_e( 'Use the "Choose default signature" dropdowns to assign it to new messages and/or replies.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Click "OK" to save.', 'email-signatures-pro' ); ?></li>
				</ol>
			</details>

			<details>
				<summary><?php esc_html_e( 'Outlook (Web / Microsoft 365)', 'email-signatures-pro' ); ?></summary>
				<ol class="esp-steps">
					<li><?php esc_html_e( 'Click the gear icon in the top-right corner and choose "View all Outlook settings".', 'email-signatures-pro' ); ?></li>
					<li><?php
						printf(
							esc_html__( 'Go to %1$s > %2$s.', 'email-signatures-pro' ),
							'<strong>Mail</strong>',
							'<strong>Compose and reply</strong>'
						);
					?></li>
					<li><?php esc_html_e( 'Under "Email signature", click "+ New signature" or select an existing one.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Clear the editor, then paste your copied signature.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Set it as the default for new messages and/or replies using the dropdowns below.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Click "Save".', 'email-signatures-pro' ); ?></li>
				</ol>
			</details>

			<details>
				<summary><?php esc_html_e( 'Apple Mail (Mac)', 'email-signatures-pro' ); ?></summary>
				<ol class="esp-steps">
					<li><?php
						printf(
							esc_html__( 'Open Mail and go to %1$s > %2$s > %3$s.', 'email-signatures-pro' ),
							'<strong>Mail</strong>',
							'<strong>Settings</strong>',
							'<strong>Signatures</strong>'
						);
					?></li>
					<li><?php esc_html_e( 'Select your email account on the left, then click the "+" button to add a new signature.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Clear the preview pane on the right, then paste your copied signature.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Use the "Choose Signature" dropdown at the bottom to set it as the default.', 'email-signatures-pro' ); ?></li>
					<li><?php esc_html_e( 'Close the Settings window — changes save automatically.', 'email-signatures-pro' ); ?></li>
				</ol>
			</details>
		</div>
	</div>

	<?php if ( current_user_can( 'edit_post', $post->ID ) && $need_render ) : ?>
		<?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- html2canvas required for signature image generation */ ?>
		<script src="<?php echo esc_url( $html2canvas_url ); ?>"></script>
		<script>
		window.addEventListener('load', function(){
			var renders = document.querySelectorAll('.esp-render');
			if(!renders.length){ return; }

			var pending = renders.length;
			var statusEl = document.getElementById('esp-generating-status');
			var total = pending;

			function setStatus(msg){
				if(statusEl){ statusEl.textContent = msg; }
			}

			function trimCanvas(canvas){
				var ctx = canvas.getContext('2d');
				var width = canvas.width;
				var height = canvas.height;
				var imgData = ctx.getImageData(0, 0, width, height).data;
				var top = height, left = width, right = 0, bottom = 0;

				for(var y = 0; y < height; y++){
					for(var x = 0; x < width; x++){
						var alpha = imgData[(y * width + x) * 4 + 3];
						if(alpha !== 0){
							if(x < left){ left = x; }
							if(x > right){ right = x; }
							if(y < top){ top = y; }
							if(y > bottom){ bottom = y; }
						}
					}
				}

				if(right - left <= 0 || bottom - top <= 0){ return canvas; }

				var trimmedWidth = right - left + 1;
				var trimmedHeight = bottom - top + 1;
				var trimmed = document.createElement('canvas');
				trimmed.width = trimmedWidth;
				trimmed.height = trimmedHeight;
				trimmed.getContext('2d').drawImage(canvas, left, top, trimmedWidth, trimmedHeight, 0, 0, trimmedWidth, trimmedHeight);
				return trimmed;
			}

			function fitName(){
				var name = document.querySelector('.esp-header-name');
				if(!name){ return; }
				var available = 380 - name.offsetLeft - 8;
				var size = parseFloat(window.getComputedStyle(name).fontSize);
				while(name.scrollWidth > available && size > 12){
					size -= 0.5;
					name.style.fontSize = size + 'px';
				}
			}

			function waitForImages(root){
				if(!root){ return Promise.resolve(); }
				var imgs = root.querySelectorAll('img');
				if(!imgs.length){ return Promise.resolve(); }
				return Promise.all(Array.prototype.map.call(imgs, function(img){
					if(img.complete && img.naturalWidth){ return Promise.resolve(); }
					return new Promise(function(resolve){
						img.addEventListener('load', resolve, {once: true});
						img.addEventListener('error', resolve, {once: true});
					});
				}));
			}

			function swapEmailHtml(html){
				var wrapper = document.querySelector('.signature-card');
				if(!wrapper){ return; }
				wrapper.innerHTML = html;
				ensureImgDimensions();
				showCopyButton();
			}

			function showCopyButton(){
				var status = document.getElementById('esp-generating-status');
				if(status){ status.remove(); }
				var actions = document.querySelector('.esp-actions');
				if(!actions || document.getElementById('esp-copy-btn')){ return; }
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.id = 'esp-copy-btn';
				btn.className = 'esp-btn esp-btn--primary';
				btn.textContent = '<?php echo esc_js( __( 'Copy Signature', 'email-signatures-pro' ) ); ?>';
				actions.insertBefore(btn, actions.firstChild);
				if(typeof window.espInitCopyButton === 'function'){
					window.espInitCopyButton(btn);
				}
			}

			function fetchEmailHtml(){
				var xhr = new XMLHttpRequest();
				xhr.open('POST', '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>');
				var formData = new FormData();
				formData.append('action', 'esp_get_signature_html');
				formData.append('nonce', '<?php echo esc_attr( wp_create_nonce( 'esp_signature_image' ) ); ?>');
				formData.append('post_id', '<?php echo (int) $post->ID; ?>');
				<?php if ( $preview_key ) : ?>
				formData.append('preview_key', '<?php echo esc_attr( $preview_key ); ?>');
				<?php endif; ?>
				xhr.onload = function(){
					try {
						var res = JSON.parse(xhr.responseText);
						if(res.success && res.data && res.data.html){
							swapEmailHtml(res.data.html);
						} else {
							setStatus('<?php echo esc_js( __( 'Generation failed. Please reload.', 'email-signatures-pro' ) ); ?>');
						}
					} catch(e){
						setStatus('<?php echo esc_js( __( 'Generation failed. Please reload.', 'email-signatures-pro' ) ); ?>');
					}
				};
				xhr.send(formData);
			}

			function render(){
				fitName();
				var completed = 0;

				renders.forEach(function(el){
					var field = el.dataset.field;
					setStatus('<?php echo esc_js( __( 'Generating signature…', 'email-signatures-pro' ) ); ?> (' + (completed + 1) + ' <?php echo esc_js( __( 'of', 'email-signatures-pro' ) ); ?> ' + total + ')');

					html2canvas(el, {backgroundColor: null, scale: 2, useCORS: true}).then(function(canvas){
						canvas = trimCanvas(canvas);
						var dataUrl = canvas.toDataURL('image/png');
						var xhr = new XMLHttpRequest();
						xhr.open('POST', '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>');
						var formData = new FormData();
						formData.append('action', 'esp_upload_signature_image');
						formData.append('nonce', '<?php echo esc_attr( wp_create_nonce( 'esp_signature_image' ) ); ?>');
						formData.append('post_id', '<?php echo (int) $post->ID; ?>');
						formData.append('field', field);
						formData.append('image', dataUrl);
						<?php if ( $preview_key ) : ?>
						formData.append('preview_key', '<?php echo esc_attr( $preview_key ); ?>');
						<?php endif; ?>
						xhr.onload = function(){
							completed++;
							if(--pending === 0){
								fetchEmailHtml();
							}
						};
						xhr.onerror = function(){
							setStatus('<?php echo esc_js( __( 'Upload failed. Please reload.', 'email-signatures-pro' ) ); ?>');
						};
						xhr.send(formData);
					});
				});
			}

			var headerEl = document.querySelector('.esp-header');
			var ready = [];
			if(document.fonts && document.fonts.ready){
				ready.push(document.fonts.ready);
			}
			ready.push(waitForImages(headerEl));
			Promise.all(ready).then(render);
		});
		</script>
	<?php endif; ?>

	<?php if ( current_user_can( 'read' ) ) : ?>
	<script>
	function ensureImgDimensions(){
		document.querySelectorAll('.signature-card img').forEach(function(img){
			if(img.hasAttribute('width') && img.hasAttribute('height')){ return; }
			var rect = img.getBoundingClientRect();
			if(rect.width && rect.height){
				img.setAttribute('width', Math.round(rect.width));
				img.setAttribute('height', Math.round(rect.height));
			}
		});
	}

	function sanitizeSignatureHtml(html){
		return html.replace(/\sclass="[^"]*"/gi, '');
	}

	function espInitCopyButton(copyBtn){
		copyBtn.addEventListener('click', function(){
			var wrapper = document.querySelector('.signature-card');
			if(!wrapper){ return; }

			if(wrapper.querySelector('.esp-render, div')){
				window.alert('<?php echo esc_js( __( 'Signature is still generating. Please wait.', 'email-signatures-pro' ) ); ?>');
				return;
			}

			var html = sanitizeSignatureHtml(wrapper.innerHTML);

			function success(){
				var original = copyBtn.textContent;
				copyBtn.textContent = '<?php echo esc_js( __( 'Copied!', 'email-signatures-pro' ) ); ?>';
				setTimeout(function(){ copyBtn.textContent = original; }, 2000);
			}

			function fallback(){
				var ta = document.createElement('textarea');
				ta.value = html;
				document.body.appendChild(ta);
				ta.select();
				try { document.execCommand('copy'); } catch(e) {}
				document.body.removeChild(ta);
				success();
			}

			var table = wrapper.querySelector('table');
			if(!table){
				window.alert('<?php echo esc_js( __( 'Signature is not ready to copy.', 'email-signatures-pro' ) ); ?>');
				return;
			}

			function tryExecCommand(){
				var range = document.createRange();
				range.selectNode(table);
				var selection = window.getSelection();
				selection.removeAllRanges();
				selection.addRange(range);

				function onCopy(ev){
					try {
						ev.clipboardData.setData('text/html', html);
						ev.clipboardData.setData('text/plain', html);
						ev.preventDefault();
					} catch(copyErr) {}
				}
				document.addEventListener('copy', onCopy, { once: true });

				var ok = false;
				try { ok = document.execCommand('copy'); } catch(e) { ok = false; }
				selection.removeAllRanges();
				return ok;
			}

			if(tryExecCommand()){
				success();
				return;
			}

			if(navigator.clipboard && window.ClipboardItem){
				var item = new ClipboardItem({
					'text/html': new Blob([html], { type: 'text/html' }),
					'text/plain': new Blob([html], { type: 'text/plain' })
				});
				navigator.clipboard.write([item]).then(success).catch(fallback);
			} else {
				fallback();
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function(){
		var copyBtn = document.getElementById('esp-copy-btn');
		if(copyBtn){
			espInitCopyButton(copyBtn);
			ensureImgDimensions();
		}

		var regenBtn = document.getElementById('esp-regenerate-btn');
		if(regenBtn){
			regenBtn.addEventListener('click', function(){
				if(!confirm('<?php echo esc_js( $regenerate_confirm ); ?>')){ return; }

				regenBtn.disabled = true;
				regenBtn.textContent = '<?php echo esc_js( __( 'Regenerating…', 'email-signatures-pro' ) ); ?>';

				var xhr = new XMLHttpRequest();
				xhr.open('POST', '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>');
				var formData = new FormData();
				formData.append('action', 'esp_regenerate_signature');
				formData.append('nonce', '<?php echo esc_attr( wp_create_nonce( 'esp_regenerate_signature' ) ); ?>');
				formData.append('post_id', '<?php echo (int) $post->ID; ?>');
				<?php if ( $preview_key ) : ?>
				formData.append('preview_key', '<?php echo esc_attr( $preview_key ); ?>');
				<?php endif; ?>
				xhr.onload = function(){ window.location.reload(); };
				xhr.onerror = function(){
					window.alert('<?php echo esc_js( __( 'Error regenerating.', 'email-signatures-pro' ) ); ?>');
					regenBtn.disabled = false;
				};
				xhr.send(formData);
			});
		}
	});
	</script>
	<?php endif; ?>
</body>
</html>
