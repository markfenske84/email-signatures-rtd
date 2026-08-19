(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		if (typeof espAdmin === 'undefined') {
			return;
		}

		var btn = document.getElementById('esp-preview-signature-btn');
		var titleEl = document.getElementById('title');
		var jobTitleEl = document.getElementById('esp_job_title');
		var phoneEl = document.getElementById('esp_phone_number');
		var thumbEl = document.getElementById('_thumbnail_id');
		var postForm = document.getElementById('post');
		var saveConfirmed = false;

		function thumbnailId() {
			var id = thumbEl ? parseInt(thumbEl.value, 10) : 0;
			return id > 0 ? String(id) : '0';
		}

		function signatureChanged() {
			var initial = espAdmin.initialValues || {};

			return (titleEl ? titleEl.value : '') !== (initial.title || '')
				|| (jobTitleEl ? jobTitleEl.value : '') !== (initial.jobTitle || '')
				|| (phoneEl ? phoneEl.value : '') !== (initial.phoneNumber || '')
				|| thumbnailId() !== String(initial.thumbnailId || '0');
		}

		if (postForm) {
			postForm.addEventListener('submit', function (event) {
				if (!espAdmin.hasGeneratedImages || saveConfirmed || !signatureChanged()) {
					return;
				}

				if (!window.confirm(espAdmin.replaceWarning)) {
					event.preventDefault();
					event.stopImmediatePropagation();
					return;
				}

				saveConfirmed = true;
			}, true);
		}

		if (!btn) {
			return;
		}

		btn.addEventListener('click', function (event) {
			event.preventDefault();

			if (btn.getAttribute('aria-disabled') === 'true') {
				return;
			}

			var postId = btn.getAttribute('data-post-id');

			btn.setAttribute('aria-disabled', 'true');
			btn.style.pointerEvents = 'none';

			var formData = new FormData();
			formData.append('action', 'esp_stage_preview');
			formData.append('nonce', espAdmin.nonce);
			formData.append('post_id', postId);
			formData.append('title', titleEl ? titleEl.value : '');
			formData.append('job_title', jobTitleEl ? jobTitleEl.value : '');
			formData.append('phone_number', phoneEl ? phoneEl.value : '');
			formData.append('thumbnail_id', thumbnailId());

			fetch(espAdmin.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (!payload.success || !payload.data || !payload.data.url) {
						throw new Error('Preview failed');
					}
					window.open(payload.data.url, '_blank', 'noopener');
				})
				.catch(function () {
					window.alert('Could not open preview. Please save and try again.');
				})
				.finally(function () {
					btn.removeAttribute('aria-disabled');
					btn.style.pointerEvents = '';
				});
		});
	});
})();
