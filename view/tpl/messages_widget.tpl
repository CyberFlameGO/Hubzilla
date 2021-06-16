<ul class="nav nav-tabs nav-fill">
	<li class="nav-item">
		<a class="nav-link active messages-type" href="#" title="{{$strings.messages_title}}" data-messages_type="">
			<i class="fa fa-fw fa-comment-o"></i>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link messages-type" href="#" title="{{$strings.direct_messages_title}}" data-messages_type="direct">
			<i class="fa fa-fw fa-envelope-o"></i>
		</a>
	</li>
	{{if $feature_star}}
	<li class="nav-item">
		<a class="nav-link messages-type" href="#" title="{{$strings.starred_messages_title}}" data-messages_type="starred">
			<i class="fa fa-fw fa-star"></i>
		</a>
	</li>
	{{/if}}
</ul>
<div id="messages-widget" class="border border-top-0 overflow-auto mb-3" style="max-height: 70vh;">
	<div id="messages-template" rel="template" class="d-none">
		<a href="{6}" class="list-group-item list-group-item-action direct-message" data-b64mid="{0}">
			<div class="d-flex w-100 justify-content-between">
				<div class="mb-1 text-truncate" title="{5}">
					{7}
					<strong>{4}</strong>
				</div>
				<small class="messages-timeago text-nowrap" title="{1}"></small>
			</div>
			<div class="mb-1">
				<div class="text-break">{2}</div>
			</div>
			<small>{3}</small>
		</a>
	</div>
	<div id="dm-container" class="list-group list-group-flush" data-offset="10">
		{{foreach $entries as $e}}
		<a href="{{$e.href}}" class="list-group-item list-group-item-action direct-message" data-b64mid="{{$e.b64mid}}">
			<div class="d-flex w-100 justify-content-between">
				<div class="mb-1 text-truncate" title="{{$e.author_addr}}">
					{{$e.icon}}
					<strong>{{$e.author_name}}</strong>
				</div>
				<small class="messages-timeago text-nowrap" title="{{$e.created}}"></small>
			</div>
			<div class="mb-1">
				<div class="text-break">{{$e.summary}}</div>
			</div>
			<small>{{$e.info}}</small>
		</a>
		{{/foreach}}
		<div id="messages-loading" class="list-group-item" style="display: none;">
			{{$strings.loading}}<span class="jumping-dots"><span class="dot-1">.</span><span class="dot-2">.</span><span class="dot-3">.</span></span>
		</div>
	</div>
</div>
<script>
	var messages_offset = {{$offset}};
	var get_messages_page_active = false;
	var messages_type;

	$('#messages-widget').on('scroll', function() {
		if(this.scrollTop > this.scrollHeight - this.clientHeight - (this.scrollHeight/7)) {
			get_messages_page('hq');
		}
	});

	$(document).on('click', '.messages-type', function(e) {
		e.preventDefault();
		$('.messages-type').removeClass('active');
		$(this).addClass('active');
		messages_offset = 0;
		messages_type = $(this).data('messages_type');
		$('#dm-container .direct-message').remove();
		get_messages_page();
	});

	$('.messages-timeago').timeago();
	$('.direct-message[data-b64mid=\'' + bParam_mid + '\']').addClass('active');

	function get_messages_page() {
		if (get_messages_page_active)
			return;

		if (messages_offset === -1)
			return;

		get_messages_page_active = true;
		$('#messages-loading').show();
		$.ajax({
			type: 'post',
			url: 'hq',
			data: {
				offset: messages_offset,
				type: messages_type
			}
		}).done(function(obj) {
			get_messages_page_active = false;
			messages_offset = obj.offset;
			console.log(obj);
			let html;
			let tpl = $('#messages-template[rel=template]').html();
			obj.entries.forEach(function(e) {
				html = tpl.format(
					e.b64mid,
					e.created,
					e.summary,
					e.info,
					e.author_name,
					e.author_addr,
					e.href,
					e.icon
				);
				$('#messages-loading').before(html);
			});
			$('.direct-message[data-b64mid=\'' + bParam_mid + '\']').addClass('active');
			$('#messages-loading').hide();
			$('.messages-timeago').timeago();

		});
	}

</script>
