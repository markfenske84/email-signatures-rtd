(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('esp-preview-signature-btn');
		if (!btn || typeof espAdmin === 'undefined') {
			return;
		}

		btn.addEventListener('click', function () {
			var postId = btn.getAttribute('data-post-id');
			var titleEl = document.getElementById('title');
			var jobTitleEl = document.getElementById('esp_job_title');
			var phoneEl = document.getElementById('esp_phone_number');
			var thumbEl = document.getElementById('_thumbnail_id');

			btn.disabled = true;

			var formData = new FormData();
			formData.append('action', 'esp_stage_preview');
			formData.append('nonce', espAdmin.nonce);
			formData.append('post_id', postId);
			formData.append('title', titleEl ? titleEl.value : '');
			formData.append('job_title', jobTitleEl ? jobTitleEl.value : '');
			formData.append('phone_number', phoneEl ? phoneEl.value : '');
			formData.append('thumbnail_id', thumbEl ? thumbEl.value : '');

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
					btn.disabled = false;
				});
		});
	});
})();
