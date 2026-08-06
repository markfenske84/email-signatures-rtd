<?php
/**
 * Template for single Signature post.
 *
 * This template is loaded automatically by Email Signatures Pro plugin.
 * It prints a minimal markup for an email signature preview.
 * Access is already restricted to logged-in users in plugin core.
 *
 * @var WP_Post $post
 */

global $post;

// Retrieve meta values.
$job_title    = get_post_meta( $post->ID, '_esp_job_title', true );
$phone_number = get_post_meta( $post->ID, '_esp_phone_number', true );
$avatar_url   = get_the_post_thumbnail_url( $post, 'large' );
if ( ! $avatar_url ) {
	$avatar_url = get_the_post_thumbnail_url( $post, 'full' );
}

// Fixed RTD brand design. This signature is one locked layout for a single company,
// so the palette, type and company details are intentionally not configurable.
$primary   = '#384E89'; // name banner
$secondary = '#54A6DB'; // job title
$neutral   = '#42454C'; // phone and website
$tertiary  = '#EB3546'; // accent rule

$fonts_url   = 'https://fonts.googleapis.com/css2?family=Outfit:wght@500;700&family=Red+Hat+Mono:wght@300&display=swap';
$heading_css = "'Outfit', Arial, sans-serif";
$body_css    = "'Red Hat Mono', 'Courier New', monospace";

$company_logo     = plugins_url( 'assets/imgs/rtd-logo@2x.png', dirname( __DIR__ ) . '/email-signatures-rtd.php' );
$company_logo_w   = 138;
$company_logo_h   = 45;
$avatar_display   = 86;
$site_url_raw = 'https://rtdlogistics.com/';
$site_domain  = 'rtdlogistics.com';

// Sanitize phone number for tel link (digits only) and prepare display version with dot separators.
$phone_digits = preg_replace( '/\D+/', '', $phone_number ); // keep digits only

// Default to the raw digits if we cannot determine a sensible grouping.
$phone_display = $phone_digits;

// Format common phone lengths with dot separators (e.g., 123.456.7890).
if ( 10 === strlen( $phone_digits ) ) {
    $phone_display = substr( $phone_digits, 0, 3 ) . '.' . substr( $phone_digits, 3, 3 ) . '.' . substr( $phone_digits, 6 );
} elseif ( 11 === strlen( $phone_digits ) && '1' === $phone_digits[0] ) { // North-American 1+ number
    $phone_display = substr( $phone_digits, 1, 3 ) . '.' . substr( $phone_digits, 4, 3 ) . '.' . substr( $phone_digits, 7 );
} elseif ( 7 === strlen( $phone_digits ) ) {
    $phone_display = substr( $phone_digits, 0, 3 ) . '.' . substr( $phone_digits, 3 );
}

/*
 * Generated text images. Each is rendered by html2canvas at 2x and displayed at
 * half its intrinsic size so it stays sharp on high-DPI screens.
 */
$generated = array();
foreach ( array( 'header', 'phone', 'site' ) as $esp_field ) {
    $esp_attachment_id = get_post_meta( $post->ID, '_esp_signature_image_' . $esp_field, true );
    $esp_src           = $esp_attachment_id ? wp_get_attachment_image_src( $esp_attachment_id, 'full' ) : false;

    $generated[ $esp_field ] = $esp_src ? array(
        'url'    => $esp_src[0],
        'width'  => (int) round( $esp_src[1] / 2 ),
        'height' => (int) round( $esp_src[2] / 2 ),
    ) : null;
}

// Until every image exists the page shows live markup that relies on this template's
// stylesheet, which would not survive a paste into an email client.
$need_render = ( ! $generated['header'] || ! $generated['site'] || ( $phone_display && ! $generated['phone'] ) );

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( get_the_title() ); ?> – Signature</title>
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
        .esp-preview{background:#fff;max-width:380px;padding:24px;border:1px solid #dcdcde;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,.04);}
        .esp-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px;}
        .esp-btn{display:inline-block;padding:10px 18px;font-family:inherit;font-size:14px;font-weight:500;line-height:1.4;color:#fff;border:none;border-radius:4px;cursor:pointer;transition:opacity .15s ease;}
        .esp-btn:hover{opacity:.9;}
        .esp-btn:disabled{opacity:.65;cursor:not-allowed;}
        .esp-btn--primary{background:<?php echo esc_html( $primary ); ?>;}
        .esp-btn--secondary{background:<?php echo esc_html( $neutral ); ?>;}
        #esp-copy-btn{display:none;}
        .esp-back{margin:20px 0 0;padding:0;}
        .esp-back a{font-size:14px;color:<?php echo esc_html( $primary ); ?>;text-decoration:none;}
        .esp-back a:hover{text-decoration:underline;}
        .esp-header{position:relative;width:380px;height:86px;overflow:hidden;}
        .esp-header-bar{position:absolute;left:40px;top:19px;width:330px;height:40px;background:<?php echo esc_html( $primary ); ?>;z-index:1;}
        .esp-header-slant{position:absolute;left:370px;top:19px;width:0;height:0;border-top:40px solid <?php echo esc_html( $primary ); ?>;border-right:10px solid transparent;z-index:1;}
        .esp-header-avatar{position:absolute;left:0;top:0;width:<?php echo (int) $avatar_display; ?>px;height:<?php echo (int) $avatar_display; ?>px;border-radius:50%;overflow:hidden;background-color:#e4e5e7;z-index:2;}
        .esp-header-avatar-img{display:block;width:<?php echo (int) $avatar_display; ?>px;height:<?php echo (int) $avatar_display; ?>px;object-fit:cover;border:0;}
        .esp-header-name{position:absolute;left:102px;top:21px;height:40px;line-height:40px;z-index:3;font-family:<?php echo $heading_css; ?>;font-size:26px;font-weight:700;color:#ffffff;white-space:nowrap;}
        .esp-header-title{position:absolute;left:102px;top:62px;line-height:22px;z-index:3;font-family:<?php echo $heading_css; ?>;font-size:17.5px;font-weight:500;color:<?php echo esc_html( $secondary ); ?>;white-space:nowrap;}
        .esp-mono{font-family:<?php echo $body_css; ?>;font-size:17.5px;font-weight:300;line-height:20px;color:<?php echo esc_html( $neutral ); ?>;white-space:nowrap;}
        <?php /* phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
    </style>
</head>
<body>
    <div class="esp-page">
        <header class="esp-page-header">
            <h1 class="esp-page-title"><?php echo esc_html( get_the_title() ); ?></h1>
            <p class="esp-page-subtitle"><?php esc_html_e( 'Email signature preview', 'email-signatures-pro' ); ?></p>
        </header>

        <div class="esp-preview">
        <table class="signature-card" width="380" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;width:380px;">
            <!-- Avatar + name banner + job title -->
            <tr>
                <td style="padding:0;font-size:0;line-height:0;">
                    <?php if ( $generated['header'] ) : ?>
                        <img src="<?php echo esc_url( $generated['header']['url'] ); ?>" width="<?php echo esc_attr( $generated['header']['width'] ); ?>" height="<?php echo esc_attr( $generated['header']['height'] ); ?>" alt="<?php echo esc_attr( trim( get_the_title() . ( $job_title ? ', ' . $job_title : '' ) ) ); ?>" style="display:block;border:0;" />
                    <?php else : ?>
                        <div class="esp-header esp-render" data-field="header">
                            <div class="esp-header-bar"></div>
                            <div class="esp-header-slant"></div>
                            <div class="esp-header-avatar">
                                <?php if ( $avatar_url ) : ?>
                                    <img class="esp-header-avatar-img" src="<?php echo esc_url( $avatar_url ); ?>" width="<?php echo (int) $avatar_display; ?>" height="<?php echo (int) $avatar_display; ?>" alt="" />
                                <?php endif; ?>
                            </div>
                            <div class="esp-header-name"><?php echo esc_html( get_the_title() ); ?></div>
                            <?php if ( $job_title ) : ?>
                                <div class="esp-header-title"><?php echo esc_html( $job_title ); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Company logo + contact details -->
            <tr>
                <td style="padding:15px 0 0 0;">
                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                        <tr>
                            <td valign="middle" style="padding:0 14px 0 6px;font-size:0;line-height:0;">
                                <a href="<?php echo esc_url( $site_url_raw ); ?>" target="_blank" rel="noopener" style="display:block;">
                                    <img src="<?php echo esc_url( $company_logo ); ?>" width="<?php echo (int) $company_logo_w; ?>" height="<?php echo (int) $company_logo_h; ?>" alt="<?php echo esc_attr( $site_domain ); ?>" style="display:block;border:0;" />
                                </a>
                            </td>
                            <td valign="middle" style="padding:0;">
                                <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                                    <?php if ( $phone_display ) : ?>
                                        <tr>
                                            <td height="20" valign="middle" style="height:20px;padding:0;font-size:0;line-height:0;">
                                                <a href="tel:<?php echo esc_attr( $phone_digits ); ?>" style="display:block;text-decoration:none;">
                                                    <?php if ( $generated['phone'] ) : ?>
                                                        <img src="<?php echo esc_url( $generated['phone']['url'] ); ?>" width="<?php echo esc_attr( $generated['phone']['width'] ); ?>" height="<?php echo esc_attr( $generated['phone']['height'] ); ?>" alt="<?php echo esc_attr( $phone_display ); ?>" style="display:block;border:0;" />
                                                    <?php else : ?>
                                                        <span class="esp-mono esp-render" data-field="phone"><?php echo esc_html( $phone_display ); ?></span>
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td height="20" valign="middle" style="height:20px;padding:0;font-size:0;line-height:0;">
                                            <a href="<?php echo esc_url( $site_url_raw ); ?>" target="_blank" rel="noopener" style="display:block;text-decoration:none;">
                                                <?php if ( $generated['site'] ) : ?>
                                                    <img src="<?php echo esc_url( $generated['site']['url'] ); ?>" width="<?php echo esc_attr( $generated['site']['width'] ); ?>" height="<?php echo esc_attr( $generated['site']['height'] ); ?>" alt="<?php echo esc_attr( $site_domain ); ?>" style="display:block;border:0;" />
                                                <?php else : ?>
                                                    <span class="esp-mono esp-render" data-field="site"><?php echo esc_html( $site_domain ); ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Accent rule -->
            <tr>
                <td style="padding:17px 0 0 0;">
                    <table width="321" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;width:321px;">
                        <tr>
                            <td height="1" style="height:1px;padding:0;font-size:1px;line-height:1px;background-color:<?php echo esc_html( $tertiary ); ?>;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        </div>

        <div class="esp-actions">
            <?php if ( current_user_can( 'read' ) && ! $need_render ) : ?>
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
    </div>
    <?php if ( current_user_can( 'edit_post', $post->ID ) && $need_render ) : ?>
        <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript, PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- html2canvas library required for signature image generation, loaded only for editors */ ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script>
            window.addEventListener('load', function(){
                var renders = document.querySelectorAll('.esp-render');
                if(!renders.length){return;}

                var pending = renders.length;

                // Utility: trim transparent whitespace from a canvas so resulting PNG has no extra padding.
                function trimCanvas(canvas){
                    var ctx = canvas.getContext('2d');
                    var width = canvas.width;
                    var height = canvas.height;

                    // Get pixel data (alpha channel only) and determine bounding box of non-transparent pixels.
                    var imgData = ctx.getImageData(0, 0, width, height).data;
                    var top = height, left = width, right = 0, bottom = 0;

                    for(var y = 0; y < height; y++){
                        for(var x = 0; x < width; x++){
                            var alpha = imgData[(y * width + x) * 4 + 3]; // alpha channel
                            if(alpha !== 0){
                                if(x < left) { left = x; }
                                if(x > right){ right = x; }
                                if(y < top)  { top = y; }
                                if(y > bottom){ bottom = y; }
                            }
                        }
                    }

                    // If nothing found, return original canvas.
                    if(right - left <= 0 || bottom - top <= 0){
                        return canvas;
                    }

                    var trimmedWidth  = right - left + 1;
                    var trimmedHeight = bottom - top + 1;
                    var trimmed = document.createElement('canvas');
                    trimmed.width  = trimmedWidth;
                    trimmed.height = trimmedHeight;
                    trimmed.getContext('2d').drawImage(canvas, left, top, trimmedWidth, trimmedHeight, 0, 0, trimmedWidth, trimmedHeight);
                    return trimmed;
                }

                // Shrink the name until it fits the banner, so long names are never clipped.
                function fitName(){
                    var name = document.querySelector('.esp-header-name');
                    if(!name){return;}
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

                function render(){
                    fitName();

                    renders.forEach(function(el){
                        const field = el.dataset.field;
                        // Render at 2x so the generated PNG stays sharp on high-DPI screens.
                        // The template halves these dimensions when printing the <img>.
                        var scale = 2;
                        var options = {backgroundColor: null, scale: scale, useCORS: true};
                        html2canvas(el, options).then(function(canvas){
                            // Remove any transparent whitespace before encoding.
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
                            xhr.onload = function(){
                                if(--pending === 0){ location.reload(); }
                            };
                            xhr.send(formData);
                        });
                    });
                }

                // Wait for fonts and header photos before rasterizing.
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
        document.addEventListener('DOMContentLoaded', function(){
            var copyBtn = document.getElementById('esp-copy-btn');
            if(!copyBtn){return;}

            copyBtn.addEventListener('click', function(){
                var sig = document.querySelector('.signature-card');
                if(!sig){return;}
                var html = sig.outerHTML;

                function success(){
                    var original = copyBtn.textContent;
                    copyBtn.textContent = 'Copied!';
                    setTimeout(function(){ copyBtn.textContent = original; }, 2000);
                }

                function fallback(){
                    var ta = document.createElement('textarea');
                    ta.value = html;
                    document.body.appendChild(ta);
                    ta.select();
                    try{ document.execCommand('copy'); } catch(e){}
                    document.body.removeChild(ta);
                    success();
                }

                // Step 1: Try the synchronous execCommand path first (keeps user gesture).
                if (tryExecCommand()) {
                    success();
                    return; // done
                }

                // Step 2: Try modern Clipboard API (may require secure context).
                if (navigator.clipboard && window.ClipboardItem) {
                    const item = new ClipboardItem({
                        'text/html': new Blob([html], { type: 'text/html' }),
                        'text/plain': new Blob([html], { type: 'text/plain' })
                    });

                    navigator.clipboard.write([item]).then(success).catch(function(err){
                        console.error('ClipboardItem error:', err);
                        fallback();
                    });
                } else {
                    // Step 3: Plain-text fallback.
                    fallback();
                }

                // --- helper functions ---
                function tryExecCommand(){
                    var range = document.createRange();
                    range.selectNode(sig);
                    var selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);

                    // Install a one-time copy listener that injects both html and plain text.
                    function onCopy(ev){
                        try {
                            ev.clipboardData.setData('text/html', html);
                            ev.clipboardData.setData('text/plain', html);
                            ev.preventDefault();
                        } catch(copyErr){
                            console.error('clipboardData.setData error:', copyErr);
                        }
                    }
                    document.addEventListener('copy', onCopy, { once: true });

                    var ok = false;
                    try {
                        ok = document.execCommand('copy');
                        if (!ok) {
                            console.warn('execCommand returned false');
                        }
                    } catch (e) {
                        console.error('execCommand error:', e);
                        ok = false;
                    }

                    // Clean up.
                    selection.removeAllRanges();
                    return ok;
                }

            });

            // Reveal copy button once all assets are fully loaded.
            window.addEventListener('load', function(){
                copyBtn.style.display = 'inline-block';

                // ------------------------------------------------------------------
                // Ensure all <img> elements inside the signature have explicit width
                // and height attributes so that email clients (which often strip
                // inline styles) render them at the correct size instead of their
                // full natural resolution.
                // ------------------------------------------------------------------
                var imgs = document.querySelectorAll('.signature-card img');
                imgs.forEach(function(img){
                    // Skip if attributes already present.
                    if(img.hasAttribute('width') || img.hasAttribute('height')){ return; }

                    // Use the element's rendered size as the desired dimension.
                    var rect = img.getBoundingClientRect();
                    if(rect.width && rect.height){
                        img.setAttribute('width',  Math.round(rect.width));
                        img.setAttribute('height', Math.round(rect.height));
                    }
                });
            });

            // Regenerate button logic.
            (function(){
                var regenBtn = document.getElementById('esp-regenerate-btn');
                if(!regenBtn){return;}

                regenBtn.addEventListener('click', function(){
                    if(!confirm('This will clear cached images and regenerate them. Continue?')){return;}

                    regenBtn.disabled = true;
                    regenBtn.textContent = 'Regenerating…';

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>');
                var formData = new FormData();
                formData.append('action', 'esp_regenerate_signature');
                formData.append('nonce', '<?php echo esc_attr( wp_create_nonce( 'esp_regenerate_signature' ) ); ?>');
                formData.append('post_id', '<?php echo (int) $post->ID; ?>');
                    xhr.onload = function(){
                        location.reload();
                    };
                    xhr.onerror = function(){
                        alert('Error regenerating.');
                        regenBtn.disabled = false;
                    };
                    xhr.send(formData);
                });
            })();
        });
    </script>
    <?php endif; ?>
</body>
</html>
