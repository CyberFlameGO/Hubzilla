<div id="edit-modal" class="modal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<div id="edit-modal-title" class="modal-title w-75">
					<div class="placeholder-wave">
						<span class="placeholder placeholder-lg" style="width: 200px;"></span>
					</div>
				</div>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div id="edit-modal-body" class="modal-body">
				<div class="placeholder-wave">
					<span class="placeholder placeholder-lg w-100 mb-4"></span>
					<span class="placeholder placeholder-lg w-100 mb-4"></span>
					<span class="placeholder placeholder-lg w-100 mb-4"></span>
				</div>
			</div>
			<div class="modal-footer">
				<div id="edit-modal-tools" class="me-auto"></div>
				<button id="contact-save" type="button" class="btn btn-primary"></button>
			</div>
		</div>
	</div>
</div>
<script>
	let poi;
	let section = 'roles';
	let sub_section;


	$('#edit-modal').on('hidden.bs.modal', function (e) {
		if (window.location.hash) {
			history.replaceState(null, '', 'connections');
		}
	})

	if (window.location.hash) {
		poi = window.location.hash.substr(1);
		init_contact_edit(poi);
	}

	window.onhashchange = function() {
		if (window.location.hash) {
			poi = window.location.hash.substr(1);
			init_contact_edit(poi);
		}
	};


	$(document).on('click', '.contact-edit', function (e) {
		e.preventDefault();
		poi = this.dataset.id
		init_contact_edit(poi);
	});

	$(document).on('click', '#contact-save', function () {
		let form_data = $('#contact-edit-form').serialize() + '&section=' + section + '&sub_section=' + sub_section;

		$.post('contactedit/' + poi, form_data, function(data) {
			if (!data.success) {
				$.jGrowl(data.message, {sticky: false, theme: 'notice', life: 10000});
				return;
			}
			activate(data);
			$.jGrowl(data.message, {sticky: false, theme: ((data.success) ? 'info' : 'notice'), life: ((data.success) ? 3000 : 10000)});
			// $('#edit-modal').modal('hide');
		});

	});

	$(document).on('click', '.contact-tool', function (e) {
		e.preventDefault();
		let cmd = this.dataset.cmd;

		$.get('contactedit/' + poi + '/' + cmd, function(data) {
			$('#edit-modal-tools').html(data.tools);
			$.jGrowl(data.message, {sticky: false, theme: ((data.success) ? 'info' : 'notice'), life: ((data.success) ? 3000 : 10000)});
			if (cmd === 'drop') {
				if ($('#contact-entry-wrapper-' + poi).length) {
					$('#contact-entry-wrapper-' + poi).fadeOut();
				}
				$('#edit-modal').modal('hide');
			}
		});
	});

	$(document).on('click', '.section', function () {
		section = this.dataset.section;
		sub_section = '';
	});

	$(document).on('click', '.sub_section', function () {
		if ($(this).hasClass('sub_section_active')) {
			$(this).removeClass('sub_section_active');
			sub_section = '';
		}
		else {
			$(this).addClass('sub_section_active');
			sub_section = this.dataset.section;
		}
	});

	function init_contact_edit(poi) {
		if (!poi)
			return
		$.get('contactedit/' + poi, function(data) {
			if (!data.success) {
				$.jGrowl(data.message, {sticky: false, theme: 'notice', life: 10000});
				return;
			}
			$('#edit-modal').modal('show');
			activate(data);
		});
	}

	function activate(data) {
		$('#edit-modal-title').css('filter', 'blur(0px)');
		$('#edit-modal-body').css('filter', 'blur(0px)');
		$('#contact-save').removeClass('disabled');
		$('#contact-tools').removeClass('disabled');

		if (data.title) {
			$('#edit-modal-title').html(data.title);
		}

		if (data.body) {
			$('#edit-modal-body').html(data.body);
		}

		if (data.tools) {
			$('#edit-modal-tools').html(data.tools);
		}

		if (data.submit) {
			$('#contact-save').html(data.submit);
		}

		if (data.role && $('#contact-role-' + poi).length) {
			$('#contact-role-' + poi).html(data.role);
		}

		if (data.pending) {
			$('#contact-save').removeClass('btn-primary');
			$('#contact-save').addClass('btn-success');
		}
		else {
			$('#contact-save').addClass('btn-primary');
			$('#contact-save').removeClass('btn-success');
		}
	}
</script>
