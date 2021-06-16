/* global wpforo */

$wpf.ajaxSetup({
    url: wpforo.ajax_url,
    data:{
        referer: window.location.origin + window.location.pathname
    }
});

function wpforo_post_url_fixer(hash) {
    var postid = 0;
    var match = hash.match(/^#post-(\d+)$/);
    if ( match && (postid = match[1]) ) {
        if (!$wpf(hash).length && $wpf.active === 0) {
            $wpf.ajax({
                type: 'POST',
                data: {
                    postid: postid,
                    action: 'wpforo_post_url_fixer'
                }
            }).done(function (response) {
                if( /^https?:\/\/[^\r\n\t\s\0'"]+$/.test(response) ){
                    window.location.assign(response);
                }
            });
        }
    }
}

$wpf(document).ready(function ($) {
	var wpforo_wrap = $('#wpforo-wrap');

    //location hash ajax redirect fix
    setTimeout(function(){
        wpforo_post_url_fixer(window.location.hash);
    }, 500);
    window.onhashchange = function(){
        wpforo_post_url_fixer(window.location.hash);
    };

//	Like
    wpforo_wrap.on('click', '.wpforo-like:not(.wpf-processing)', function () {
        wpforo_load_show();
        var postid = $(this).data('postid'),
            $this = $(this);
        $this.addClass('wpf-processing');
        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                likestatus: 1,
                action: 'wpforo_like_ajax'
            }
        }).done(function (response) {
            if (response.success) {
                $this.find('.wpforo-like-ico').removeClass('far').addClass('fas');
                $this.find('.wpforo-like-txt').text(' ' + wpforo_phrase('Unlike'));
                $this.parents('.wpforo-post').find('.bleft').html(response.data.likers);
                $this.removeClass('wpforo-like').addClass('wpforo-unlike');
                if( $this.children(".wpf-like-icon").is("[wpf-tooltip]") ) {
                    $this.children(".wpf-like-icon").attr("wpf-tooltip", wpforo_phrase('Unlike') );
                }else{
                    $this.find('.wpforo-like-ico').removeClass('fa-thumbs-up').addClass('fa-thumbs-down');
                }
                $this.children(".wpf-like-count").text(response.data.count);
            }
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            $this.removeClass('wpf-processing');
        });
    });
// unlike
    wpforo_wrap.on('click', '.wpforo-unlike:not(.wpf-processing)', function () {
        wpforo_load_show();
        var postid = $(this).data('postid'),
            $this = $(this);
        $this.addClass('wpf-processing');
        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                likestatus: 0,
                action: 'wpforo_like_ajax'
            }
        }).done(function (response) {
            if (response.success) {
                $this.find('.wpforo-like-ico').removeClass('fas').addClass('far');
                $this.find('.wpforo-like-txt').text(' ' + wpforo_phrase('Like'));
                $this.parents('.wpforo-post').find('.bleft').html(response.data.likers);
                $this.removeClass('wpforo-unlike').addClass('wpforo-like');
                if( $this.children(".wpf-like-icon").is("[wpf-tooltip]") ) {
                    $this.children(".wpf-like-icon").attr("wpf-tooltip", wpforo_phrase('Like') );
                }else{
                    $this.find('.wpforo-like-ico').removeClass('fa-thumbs-down').addClass('fa-thumbs-up');
                }
                $this.children(".wpf-like-count").text(response.data.count);
            }
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            $this.removeClass('wpf-processing');
        });
    });


//	Vote
    wpforo_wrap.on('click', '.wpforo-voteup:not(.wpf-processing)', function () {
        wpforo_load_show();
        var type = $(this).data('type'),
            postid = $(this).data('postid'),
            $this = $(this);
        var buttons = $('.wpforo-voteup, .wpforo-votedown', $this.closest('.wpforo-post-voting'));
        buttons.addClass('wpf-processing');
        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                itemtype: type,
                postid: postid,
                votestatus: 'up',
                action: 'wpforo_vote_ajax'
            }
        }).done(function (response) {
            if( response.success ) $this.parents('.post-wrap').find('.wpfvote-num').text(response.data.votes).fadeIn();
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            buttons.removeClass('wpf-processing');
        });
    });

    wpforo_wrap.on('click', '.wpforo-votedown:not(.wpf-processing)', function () {
        wpforo_load_show();
        var type = $(this).data('type'),
            postid = $(this).data('postid'),
            $this = $(this);
        var buttons = $('.wpforo-voteup, .wpforo-votedown', $this.closest('.wpforo-post-voting'));
        buttons.addClass('wpf-processing');
        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                itemtype: type,
                postid: postid,
                votestatus: 'down',
                action: 'wpforo_vote_ajax'
            }
        }).done(function (response) {
            if( response.success ) $this.parents('.post-wrap').find('.wpfvote-num').text(response.data.votes).fadeIn();
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            buttons.removeClass('wpf-processing');
        });
    });


//	Answer
    wpforo_wrap.on('click', '.wpf-toggle-answer:not(.wpf-processing)', function () {
        wpforo_load_show();
        var postid = $(this).data('postid'),
            $this = $(this);
        $this.addClass('wpf-processing');
        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                answerstatus: 0,
                action: 'wpforo_answer_ajax'
            }
        }).done(function (response) {
            if (response.success) {
                $this.removeClass('wpf-toggle-answer').addClass('wpf-toggle-not-answer');
                setTimeout(function () {
                    window.location.reload();
                }, 300);
            }
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            $this.removeClass('wpf-processing');
        });
    });

    wpforo_wrap.on('click', '.wpf-toggle-not-answer:not(.wpf-processing)', function () {
        wpforo_load_show();
        var postid = $(this).data('postid'),
            $this = $(this);
        $this.addClass('wpf-processing');
        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                answerstatus: 1,
                action: 'wpforo_answer_ajax'
            }
        }).done(function (response) {
            if (response.success) {
                $this.removeClass('wpf-toggle-not-answer').addClass('wpf-toggle-answer');
                setTimeout(function () {
                    window.location.reload();
                }, 300);
            }
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            $this.removeClass('wpf-processing');
        });
    });


//	Quote
    wpforo_wrap.on('click', '.wpforo-quote:not(.wpf-processing)', function () {
        wpforo_load_show();

        var $this = $(this);
        $this.addClass('wpf-processing');

        var main_form = $('form.wpforo-main-form[data-textareaid]');
        var wrap = main_form.closest('.wpf-form-wrapper');
        wrap.show();

        var post_wrap = $(this).closest('[id^=post-][data-postid]');
        var postid = post_wrap.data('postid');
        if( !postid ) postid = 0;
        $(".wpf-form-post-parentid").val( postid );
        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                action: 'wpforo_quote_ajax'
            }
        }).done(function (response) {
            var phrase = wpforo_phrase('Reply with quote');
            phrase = phrase.charAt(0).toUpperCase() + phrase.slice(1);
            $(".wpf-reply-form-title").html(phrase);
            $(".wpf-form-postid", main_form).val(0);

            wpforo_editor.set_content(response.data, wpforo_editor.get_main());

            $('html, body').animate({scrollTop: wrap.offset().top}, 1000);
            wpforo_load_hide();
            $this.removeClass('wpf-processing');
        });
    });

//	Report
    wpforo_wrap.on('click', '.wpforo-report', function(){
        wpforo_load_show();
        var form = $("form#wpforo-report");
		$("#wpforo-report-postid", form).val( $(this).data('postid') );
        wpforo_dialog_show('', form, '45%', '295px');
        $("#wpforo-report-content", form).trigger("focus");
        wpforo_load_hide();
	});

    $(document).on('click', '#wpforo-report-send:not(.wpf-processing)', wpforo_report_send);
    $(document).on('keydown', 'form#wpforo-report', function (e) {
        if ( (e.ctrlKey || e.metaKey) && ( e.code === 'Enter' || e.code === 'NumpadEnter' ) ) {
            $('#wpforo-report-send').trigger('click');
        }
    });

    function wpforo_report_send(){
        wpforo_load_show();
        var $this = $(this);
        $this.addClass('wpf-processing');

        var postid = $('#wpforo-report-postid').val();
        var messagecontent = $('#wpforo-report-content').val();

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                reportmsg: messagecontent,
                action: 'wpforo_report_ajax'
            }
        }).done(function (response) {
            wpforo_dialog_hide();
            $('#wpforo-report-content').val('');
            wpforo_load_hide();
            wpforo_notice_show(response.data);
            $this.removeClass('wpf-processing');
        });
    }

//	Sticky
    wpforo_wrap.on('click', '.wpforo-sticky:not(.wpf-processing)', function () {
        wpforo_load_show();
        var topicid = $(this).data('topicid'),
            $this = $(this);

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                topicid: topicid,
                status: 'sticky',
                action: 'wpforo_sticky_ajax'
            }
        }).done(function (response) {
            if (response.success) {
                $this.find('.wpforo-sticky-txt').text(' ' + wpforo_phrase('Unsticky'));
                $this.removeClass('wpforo-sticky').addClass('wpforo-unsticky');
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Unsticky'));
                }
            }
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            $this.removeClass('wpf-processing');
        });
    });


    wpforo_wrap.on('click', '.wpforo-unsticky:not(.wpf-processing)', function () {
        wpforo_load_show();
        var topicid = $(this).data('topicid'),
            $this = $(this);

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                topicid: topicid,
                status: 'unsticky',
                action: 'wpforo_sticky_ajax'
            }
        }).done(function (response) {
            if (response.success) {
                $this.find('.wpforo-sticky-txt').text(' ' + wpforo_phrase('Sticky'));
                $this.removeClass('wpforo-unsticky').addClass('wpforo-sticky');
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Sticky'));
                }
            }
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            $this.removeClass('wpf-processing');
        });
    });

//	Approve
    wpforo_wrap.on('click','.wpforo-approve:not(.wpf-processing)', function(){
        wpforo_load_show();
        var postid_value = $(this).attr('id'),
            $this = $(this);
        var postid = postid_value.replace("wpfapprove", "");

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                status: 'approve',
                action: 'wpforo_approve_ajax'
            }
        }).done(function (response) {
            if (response.success) {
                $("#" + postid_value).removeClass('wpforo-approve').addClass('wpforo-unapprove');
                $("#approveicon" + postid).removeClass('fa-check').addClass('fa-exclamation-circle');
                $("#wpforo-wrap #post-" + postid + " .wpf-mod-message").hide();
                $("#wpforo-wrap .wpf-status-title").hide();
                $("#approvetext" + postid).text(' ' + wpforo_phrase('Unapprove'));
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Unapprove'));
                }
            }
            wpforo_load_hide();
            window.location.reload();
            $this.removeClass('wpf-processing');
        });
    });

//	Unapprove
    wpforo_wrap.on('click','.wpforo-unapprove:not(.wpf-processing)', function(){
        wpforo_load_show();
        var postid_value = $(this).attr('id'),
            $this = $(this);
        var postid = postid_value.replace("wpfapprove", "");

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                status: 'unapprove',
                action: 'wpforo_approve_ajax'
            }
        }).done(function (response) {
            if( response.success ){
                $("#" + postid_value).removeClass('wpforo-unapprove').addClass('wpforo-approve');
                $("#approveicon" + postid).removeClass('fa-exclamation-circle').addClass('fa-check');
                $('#wpforo-wrap #post-' + postid + ' .wpf-mod-message').visible();
                $('#wpforo-wrap .wpf-status-title').visible();
                $("#approvetext" + postid).text(' ' + wpforo_phrase('Approve'));
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Approve'));
                }
            }
            wpforo_load_hide();
            window.location.reload();
            $this.removeClass('wpf-processing');
        });
    });


//	Private
	wpforo_wrap.on('click','.wpforo-private:not(.wpf-processing)', function(){
        wpforo_load_show();
		var postid_value = $(this).attr('id'),
            $this = $(this);
		var topicid = postid_value.replace("wpfprivate", "");

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                topicid: topicid,
                status: 'private',
                action: 'wpforo_private_ajax'
            }
        }).done(function (response) {
            if( response.success ){
                $("#" + postid_value).removeClass('wpforo-private').addClass('wpforo-public');
                $("#privateicon" + topicid).removeClass('fa-eye-slash').addClass('fa-eye');
                $("#privatetext" + topicid).text(' ' + wpforo_phrase('Public'));
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Public'));
                }
            }
            wpforo_load_hide();
            $this.removeClass('wpf-processing');
        });
	});
	
	wpforo_wrap.on('click','.wpforo-public:not(.wpf-processing)', function(){
        wpforo_load_show();
		var postid_value = $(this).attr('id'),
            $this = $(this);
		var topicid = postid_value.replace("wpfprivate", "");

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                topicid: topicid,
                status: 'public',
                action: 'wpforo_private_ajax'
            }
        }).done(function (response) {
            if( response.success ){
                $("#" + postid_value).removeClass('wpforo-public').addClass('wpforo-private');
                $("#privateicon" + topicid).removeClass('fa-eye').addClass('fa-eye-slash');
                $("#privatetext" + topicid).text(' ' + wpforo_phrase('Private'));
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Private'));
                }
            }
            wpforo_load_hide();
            $this.removeClass('wpf-processing');
        });
	});
	
//	Solved
	wpforo_wrap.on('click','.wpforo-solved:not(.wpf-processing)', function(){
        wpforo_load_show();
		var postid_value = $(this).attr('id'),
            $this = $(this);
		var postid = postid_value.replace("wpfsolved", "");

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                status: 'solved',
                action: 'wpforo_solved_ajax'
            }
        }).done(function (response) {
            if( response.success ) {
                $("#" + postid_value).removeClass('wpforo-solved').addClass('wpforo-unsolved');
                $("#solvedtext" + postid).text(' ' + wpforo_phrase('Unsolved'));
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Unsolved'));
                }
            }
            wpforo_load_hide();
            $this.removeClass('wpf-processing');
        });
	});
	
	wpforo_wrap.on('click','.wpforo-unsolved:not(.wpf-processing)', function(){
        wpforo_load_show();
		var postid_value = $(this).attr('id'),
            $this = $(this);
		var postid = postid_value.replace("wpfsolved", "");

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                postid: postid,
                status: 'unsolved',
                action: 'wpforo_solved_ajax'
            }
        }).done(function (response) {
            if( response.success ){
                $("#" + postid_value).removeClass('wpforo-unsolved').addClass('wpforo-solved');
                $("#solvedtext" + postid).text(' ' + wpforo_phrase('Solved'));
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Solved'));
                }
            }
            wpforo_load_hide();
            $this.removeClass('wpf-processing');
        });
	});
	
	
//	Close
	wpforo_wrap.on('click','.wpforo-close:not(.wpf-processing)', function(){
        wpforo_load_show();
		var postid_value = $(this).attr('id'),
            $this = $(this);
		var topicid = postid_value.replace("wpfclose", "");

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                topicid: topicid,
                status: 'close',
                action: 'wpforo_close_ajax'
            }
        }).done(function (response) {
            if( response.success ){
                $("#" + postid_value).removeClass('wpforo-close').addClass('wpforo-open');
                $("#closeicon" + topicid).removeClass('fa-lock').addClass('fa-unlock');
                $("#closetext" + topicid).text(' ' + wpforo_phrase('Open'));
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Open'));
                }
                $(".wpf-form-wrapper").remove();
                $(".wpforo-reply").remove();
                $(".wpforo-quote").remove();
                $(".wpforo-edit").remove();
                $(".wpf-answer-button").remove();
                $(".wpf-add-comment-button").remove();
            }
            wpforo_load_hide();
            $this.removeClass('wpf-processing');
        });
	});
	
	wpforo_wrap.on('click','.wpforo-open:not(.wpf-processing)', function(){
        wpforo_load_show();
		var postid_value = $(this).attr('id'),
            $this = $(this);
		var topicid = postid_value.replace("wpfclose", "");

        $this.addClass('wpf-processing');

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                topicid: topicid,
                status: 'closed',
                action: 'wpforo_close_ajax'
            }
        }).done(function (response) {
            if( response.success ){
                if ($this.is("[wpf-tooltip]")) {
                    $this.attr("wpf-tooltip", wpforo_phrase('Close'));
                }
                window.location.reload();
            }
            wpforo_load_hide();
            $this.removeClass('wpf-processing');
        });
	});

	// Edit post
    wpforo_wrap.on('click','.wpforo-edit:not(.wpf-processing)', function(){
        var $this = $(this);
        var wrap = $(this).closest('[id^=post-][data-postid]');
        if( wrap.length ){
            $this.addClass('wpf-processing');
            var children = wrap.contents().not(":hidden");
            var loading = $('<div class="wpforo-loading-portable"><i class="fas fa-3x fa-circle-notch fa-spin"></i></div>');
            wrap.append(loading);

            var show_form = function(form, init){
                loading.remove();
                children.fadeOut('fast');
                var f = $(form);
                f.on('click', '.wpf-edit-post-cancel', function(){
                    f.fadeOut('fast');
                    children.fadeIn('slow');
                    $( 'html, body' ).animate({scrollTop: (wrap.offset().top - 80)}, 415);
                });
                if( init ){
                    f.appendTo(wrap);
                    wpforo_trigger_custom_event(document, 'wpforo_topic_portable_form', f);
                }
                f.fadeIn('slow');
                $( 'html, body' ).animate({scrollTop: (wrap.offset().top - 80)}, 415);
                $this.removeClass('wpf-processing');
            }

            var f = $('.wpf-form-wrapper', wrap);
            if( f.length ){
                show_form(f);
            }else{
                $.ajax({
                    type: 'POST',
                    url: wpforo.ajax_url,
                    data: {
                        postid: parseInt(wrap.data('postid')),
                        action: 'wpforo_post_edit'
                    }
                }).done(function(response){
                    if( response.success ) show_form(response.data.html, true);
                });
            }
        }
    });
	
//	Delete
	wpforo_wrap.on('click', '.wpforo-delete:not(.wpf-processing)', function(){
		if( confirm(wpforo_ucwords( wpforo_phrase('are you sure you want to delete?') )) ){
            wpforo_load_show();
            var $this = $(this);
		    $this.addClass('wpf-processing');

			var postid_value = $(this).attr('id');
			var is_topic = postid_value.indexOf("topic");

            var postid, status_value;
			if(is_topic === -1){
				postid = postid_value.replace("wpfreplydelete", "");
				status_value = 'reply';
			}else{
				postid = postid_value.replace("wpftopicdelete", "");
				status_value = 'topic';
			}
			
			var forumid = 0;
			var wpf_forumid = $("input[type='hidden'].wpf-form-forumid");
			if( wpf_forumid.length ) forumid = wpf_forumid.val();

            $.ajax({
		   		type: 'POST',
		   		url: wpforo.ajax_url,
		   		data:{
		   			forumid: forumid,
		   			postid: postid,
		   			status: status_value,
		   			action: 'wpforo_delete_ajax'
		   		}
		   	}).done(function(response){
		   		if( response.success ){
					if(is_topic === -1){
					    var to_be_removed = $('#post-' + response.data.postid);
					    if( to_be_removed.hasClass('wpf-answer-wrap') ){
                            var qa_item_wrap = to_be_removed.parents('.wpforo-qa-item-wrap');
                            if( qa_item_wrap.length ) to_be_removed = qa_item_wrap;
                        }
                        to_be_removed.remove().delay(200);
                        $('#wpf-post-replies-'+response.data.postid).remove().delay(100);
                        $('#wpf-ttgg-'+response.data.root+' .wpf-post-replies-count').text( response.data.root_count );
					}else{
						window.location.assign(response.data.location);
					}
				}
                wpforo_load_hide();
				wpforo_notice_show(response.data.notice);
                $this.removeClass('wpf-processing');
		   	});
		}
	});
	
	
//	Subscribe
	wpforo_wrap.on('click','.wpf-subscribe-forum:not(.wpf-processing), .wpf-subscribe-topic:not(.wpf-processing)', function(){
        wpforo_load_show();

        var $this = $(this);
        $this.addClass('wpf-processing');

		var type = '';
		var clases = $(this).attr('class');
		
		if( clases.indexOf("wpf-subscribe-forum") > -1 ){
	    	type = 'forum';
		}
		if( clases.indexOf("wpf-subscribe-topic") > -1 ){
			type = 'topic';
		}
		
		var postid_value = $(this).attr('id');
		var itemid = postid_value.replace("wpfsubscribe-", "");

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                itemid: itemid,
                type: type,
                status: 'subscribe',
                action: 'wpforo_subscribe_ajax'
            }
        }).done(function (response) {
            if( response.success ){
                $("#wpfsubscribe-" + itemid).removeClass('wpf-subscribe-' + type).addClass('wpf-unsubscribe-' + type).text(' ' + wpforo_phrase('Unsubscribe'));
            }
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            $this.removeClass('wpf-processing');
        });
	});
	
	wpforo_wrap.on('click','.wpf-unsubscribe-forum:not(.wpf-processing), .wpf-unsubscribe-topic:not(.wpf-processing)', function(){
        wpforo_load_show();

        var $this = $(this);
        $this.addClass('wpf-processing');

		var type = '';
		var button_phrase = '';
		var clases = $(this).attr('class');
		if( clases.indexOf("wpf-unsubscribe-forum") > -1 ){
	    	type = 'forum';
	    	button_phrase = wpforo_ucwords( wpforo_phrase('Subscribe for new topics') );
		}
		if( clases.indexOf("wpf-unsubscribe-topic") > -1 ){
			type = 'topic';
			button_phrase = wpforo_ucwords( wpforo_phrase('Subscribe for new replies') );
		}
		var postid_value = $(this).attr('id');
		var itemid = postid_value.replace("wpfsubscribe-", "");

        $.ajax({
            type: 'POST',
            url: wpforo.ajax_url,
            data: {
                itemid: itemid,
                type: type,
                status: 'unsubscribe',
                action: 'wpforo_subscribe_ajax'
            }
        }).done(function (response) {
            if( response.success ){
                $("#wpfsubscribe-" + itemid).removeClass('wpf-unsubscribe-' + type).addClass('wpf-subscribe-' + type).text(' ' + button_phrase);
            }
            wpforo_load_hide();
            wpforo_notice_show(response.data.notice);
            $this.removeClass('wpf-processing');
        });
	});

    wpforo_wrap.on('change', '.wpf-topic-form-forumid', function () {
        var $this = $(this);
        var form_wrap = $this.closest('.wpf-topic-form-extra-wrap');
        var form_ajax_wrap = $('.wpf-topic-form-ajax-wrap', form_wrap);
        var l = $('<i class="fas fa-spinner fa-spin wpf-icon-spinner"></i>');
        form_ajax_wrap.empty();
        l.appendTo(form_ajax_wrap);
        $this.attr('disabled', true);
        $('.wpf-topic-form-no-selected-forum', form_wrap).hide();

        var forumid = parseInt( $this.val() );
        if( forumid ){
            $.ajax({
                type: 'POST',
                data: {
                    forumid: forumid,
                    action: 'wpforo_topic_portable_form'
                }
            }).done(function (response) {
                l.remove();
                if( response.success ){
                    var f = $(response.data);
                    form_ajax_wrap.empty();
                    f.appendTo(form_ajax_wrap);
                    f.fadeIn('slow');
                    wpforo_trigger_custom_event(document, 'wpforo_topic_portable_form', f);
                }
                $this.attr('disabled', false);
            })
        }
    });

	wpforo_wrap.on('click', '.wpforo-tools', function () {
	    var tools = $('#wpf_moderation_tools');
	    if( tools.is(':visible') ){
            tools.slideUp(250, 'linear');
        }else{
            wpforo_load_show();
            tools.find('.wpf-tool-tabs .wpf-tool-tab').removeClass('wpf-tt-active');
            tools.find('.wpf-tool-tabs .wpf-tool-tab:first-child').addClass('wpf-tt-active');
			wpforo_topic_tools_tab_load();
        }
    });

	wpforo_wrap.on('click', '#wpf_moderation_tools .wpf-tool-tabs .wpf-tool-tab:not(.wpf-tt-active)', function () {
        wpforo_notice_hide();
        $(this).siblings('.wpf-tool-tab').removeClass('wpf-tt-active');
        if( !$(this).hasClass('wpf-tt-active') ) $(this).addClass('wpf-tt-active');
		wpforo_topic_tools_tab_load();
    });

    wpforo_topic_tools_tab_load();

    wpforo_wrap.on('click', 'div.wpfl-4:not(.wpf-processing) .wpf-load-threads a.wpf-threads-filter', function () {
        var wrap = $(this).parents('div.wpfl-4');
        var topics_list = $('.wpf-thread-list', wrap);
        topics_list.data('paged', 0);
        topics_list.data('filter', $(this).data('filter'));
        $('.wpf-more-topics > a', wrap).trigger("click");
        $(this).siblings('a.wpf-threads-filter').removeClass('wpf-active');
        $(this).addClass('wpf-active');
    });

    wpforo_wrap.on('click', 'div.wpfl-4:not(.wpf-processing) .wpf-more-topics > a', function () {
        var $this = $(this);
        var wrap = $this.parents('div.wpfl-4');
        wrap.addClass('wpf-processing');
        var topics_list = $('.wpf-thread-list', wrap);
        var filter = topics_list.data('filter');
        var forumid = topics_list.data('forumid');
        var paged = topics_list.data('paged');
        var append = paged !== 0;
        topics_list.data('paged', ++paged);

        var load_msg = wpforo_phrase('Loading Topics');

        wpforo_load_show(load_msg);

        var i = $('.wpf-load-threads a.wpf-threads-filter[data-filter="' + filter + '"] i', wrap);
        var i_class = i.attr('class');
        var i_spin_class = 'fas fa-circle-notch fa-spin';
        var i_toggle_class = i_class + ' ' + i_spin_class;

        var i2 = $('i', $this);
        var i2_class = i2.attr('class');
        var i2_toggle_class = i2_class + ' ' + i_spin_class;

        wpforo_notice_hide();

        i.toggleClass(i_toggle_class);
        if(append) i2.toggleClass(i2_toggle_class);

        $.ajax({
            type: 'POST',
            data: {
                forumid: forumid,
                filter: filter,
                paged: paged,
                action: 'wpforo_layout4_loadmore'
            }
        }).done(function (response) {
            if (response.success) {
                if (append) {
                    topics_list.append(response.data.output_html);
                } else {
                    topics_list.html(response.data.output_html);
                    $this.show();
                }
            } else {
                if (!append) {
                    topics_list.html('<span class="wpf-no-thread">' + wpforo_phrase('No threads found') + '</span>');
                }
            }

            if (response.data.no_more) {
                $this.hide();
            }

            i.toggleClass(i_toggle_class);
            if (append) i2.toggleClass(i2_toggle_class);
            wpforo_load_hide();
            wrap.removeClass('wpf-processing');
        });
    });

    wpforo_wrap.on('click', '.wpforo-qa-show-rest-comments:not(.wpf-processing)', function () {
        wpforo_load_show();
        var $this = $(this);
        $this.addClass('wpf-processing');
        var wrap = $this.parents('.wpforo-qa-item-wrap');
        var root_wrap = wrap.children('.post-wrap');
        var comments_list = $('.wpforo-qa-comments', wrap);
        var parentid = root_wrap.data('postid');
        $.ajax({
            type: 'POST',
            data: {
                parentid: parentid,
                action: 'wpforo_qa_comment_loadrest'
            }
        }).done(function (response) {
            if (response.success) {
                comments_list.append(response.data.output_html);
                $this.remove();
                wpforo_load_hide();
            }
            $this.removeClass('wpf-processing');
        });
    });

    wpforo_wrap.on('click', 'form[data-textareaid] .wpforo_post_preview:not(.wpf-disabled):not(.wpf-processing)', function(){
        var $this = $(this);
        var ico = $('.wpf-rev-preview-ico', $this);
        var form = $this.closest('form[data-textareaid]');

        //$('.wpforo_save_revision', form).trigger("click");

        var textareaid = form.data('textareaid');
        var postid = $( 'input.wpf-form-postid', form ).val();
        var body = wpforo_editor.get_content('raw');
        var body_info = wpforo_editor.get_stats();

        if( textareaid && body_info.has_content){
            $this.addClass('wpf-processing');
            wpforo_load_show();
            ico.toggleClass('fa-eye fa-circle-notch fa-spin');
            $.ajax({
                type: 'POST',
                data: {
                    textareaid: textareaid,
                    postid: postid,
                    body: body,
                    action: 'wpforo_post_preview'
                }
            }).done(function (response) {
                if( response.success ) {
                    $('.wpforo-revisions-action-buttons .wpforo-revision-action-button', form).removeClass('wpf-rev-button-active');
                    $this.addClass('wpf-rev-button-active');
                    $('.wpforo-revisions-preview-wrap', form).html(response.data);
                }
            }).always(function(){
                wpforo_load_hide();
                ico.toggleClass('fa-eye fa-circle-notch fa-spin');
                $this.removeClass('wpf-processing');
            });
        }

    });

    wpforo_wrap.on('click', 'form[data-textareaid] .wpforo_save_revision:not(.wpf-processing)', function () {
        var $this = $(this);
        if( $this.is(':visible') ){
            var ico = $('.wpf-rev-save-ico', $this);
            var form = $this.closest('form[data-textareaid]');
            var textareaid = form.data('textareaid');
            var postid = $( 'input.wpf-form-postid', form ).val();
            var body = wpforo_editor.get_content('raw');
            var body_info = wpforo_editor.get_stats();
            if( textareaid && body_info.has_content ){
                $this.addClass('wpf-processing');
                wpforo_load_show('Saving Draft');
                ico.toggleClass('fa-save fa-circle-notch fa-spin');
                $.ajax({
                    type: 'POST',
                    data: {
                        textareaid: textareaid,
                        postid: postid,
                        body: body,
                        action: 'wpforo_save_revision'
                    }
                }).done(function (response) {
                    if( response.success ) {
                        wpforo_deactivate_revision_action_buttons(form);
                        $('.wpf-rev-history-count', form).text(response.data.revisions_count);
                        if( response.data.revisionhtml && $('.wpforo_revisions_history', form).hasClass('wpf-rev-button-active') ){
                            var revisions_preview_wrap = $('.wpforo-revisions-preview-wrap', form);
                            revisions_preview_wrap.prepend(response.data.revisionhtml);
                            var wpforo_revision = $('.wpforo-revision', revisions_preview_wrap);
                            if( wpforo_revision.length >= wpforo.revision_options.max_drafts_per_page ){
                                wpforo_revision.each(function (i) {
                                    if( i >= wpforo.revision_options.max_drafts_per_page ) $(this).remove();
                                });
                            }
                        }
                    }
                }).always(function(){
                    wpforo_load_hide();
                    ico.toggleClass('fa-save fa-circle-notch fa-spin');
                    $this.removeClass('wpf-processing');
                });
            }
        }
    });

    wpforo_wrap.on('click', 'form[data-textareaid] .wpforo_revisions_history:not(.wpf-processing)', function(){
        var $this = $(this);
        var ico = $('.wpf-rev-ico', $this);
        var form = $this.closest('form[data-textareaid]');
        var textareaid = form.data('textareaid');
        var postid = $( 'input.wpf-form-postid', form ).val();

        if( textareaid ){
            $this.addClass('wpf-processing');
            wpforo_load_show();
            ico.toggleClass('fa-history fa-circle-notch fa-spin');
            $.ajax({
                type: 'POST',
                data: {
                    textareaid: textareaid,
                    postid: postid,
                    action: 'wpforo_get_revisions_history'
                }
            }).done(function (response) {
                if( response.success ) {
                    $('.wpf-rev-history-count', form).text(response.data.revisions_count);
                    $('.wpforo-revisions-action-buttons .wpforo-revision-action-button', form).removeClass('wpf-rev-button-active');
                    $this.addClass('wpf-rev-button-active');
                    $('.wpforo-revisions-preview-wrap', form).html(response.data.revisionhtml);
                }
            }).always(function(){
                wpforo_load_hide();
                ico.toggleClass('fa-history fa-circle-notch fa-spin');
                $this.removeClass('wpf-processing');
            });
        }
    });

    wpforo_wrap.on('click', 'form[data-textareaid] .wpforo-revision-action-restore:not(.wpf-processing)', function(){
        var $this = $(this);
        var ico = $('.wpf-rev-ico', $this);
        var form = $this.closest('form[data-textareaid]');
        var rev_wrap = $this.closest('.wpforo-revision[data-revisionid]');
        if( rev_wrap.length ){
            $this.addClass('wpf-processing');
            wpforo_load_show('Restore Revision');
            ico.toggleClass('fa-history fa-circle-notch fa-spin');
            var revisionid = rev_wrap.data('revisionid');
            $.ajax({
                type: 'POST',
                data: {
                    revisionid: revisionid,
                    action: 'wpforo_get_revision'
                }
            }).done(function (response) {
                if( response.success ){
                    wpforo_editor.set_content(response.data.body);
                    $('html, body').animate({ scrollTop: form.offset().top }, 500);
                }
            }).always(function(){
                wpforo_load_hide();
                ico.toggleClass('fa-history fa-circle-notch fa-spin');
                $this.removeClass('wpf-processing');
            });
        }
    });

    wpforo_wrap.on('click', 'form[data-textareaid] .wpforo-revision-action-delete:not(.wpf-processing)', function(){
        var $this = $(this);
        var ico = $('.wpf-rev-ico', $this);
        var form = $this.closest('form[data-textareaid]');
        var rev_wrap = $this.closest('.wpforo-revision[data-revisionid]');
        if( rev_wrap.length ){
            $this.addClass('wpf-processing');
            wpforo_load_show('Deleting Revision');
            ico.toggleClass('fa-trash fa-circle-notch fa-spin');
            var revisionid = rev_wrap.data('revisionid');
            $.ajax({
                type: 'POST',
                data: {
                    revisionid: revisionid,
                    action: 'wpforo_delete_revision'
                }
            }).done(function (response) {
               if( response.success ){
                   rev_wrap.fadeOut(500, function(){
                       rev_wrap.remove();
                       $('.wpf-rev-history-count', form).text(response.data.revisions_count);
                   });
               }
            }).always(function(){
                wpforo_load_hide();
                ico.toggleClass('fa-trash fa-circle-notch fa-spin');
                $this.removeClass('wpf-processing');
            });
        }
    });

    function wpforo_activate_revision_action_buttons(form){
        var rev_saved = $('.wpforo_revision_saved', form);
        if( rev_saved.is(':visible') ){
            rev_saved.fadeOut(1000, function(){
                var save_revision = $('.wpforo_save_revision', form);
                save_revision.show();

                if( parseInt(wpforo.revision_options.is_draft_on) && parseInt(wpforo.revision_options.auto_draft_interval) && !save_revision.data('auto_draft') ){
                    setInterval(function(){
                        save_revision.trigger("click");
                    }, wpforo.revision_options.auto_draft_interval);
                    save_revision.data('auto_draft', true);
                }
            });
        }
    }

    function wpforo_deactivate_revision_action_buttons(form){
        $('.wpforo_revision_saved', form).show();
        $('.wpforo_save_revision', form).hide();
    }

    function wpforo_content_changed(){
        var form = $('form[data-textareaid="'+ wpforo_editor.active_textareaid +'"]');
        if( wpforo_editor.get_stats().has_content ){
            wpforo_activate_revision_action_buttons(form);
            $('.wpforo_post_preview', form).removeClass('wpf-disabled');
        }else{
            wpforo_deactivate_revision_action_buttons(form);
            $('.wpforo_post_preview', form).addClass('wpf-disabled');
        }
    }

    function wpforo_content_ctrl_s(){
        $('form[data-textareaid="'+ wpforo_editor.active_textareaid +'"] .wpforo_save_revision').trigger("click");
    }

    wpforo_wrap.on('change input propertychange', 'form[data-textareaid] textarea', function (e) {
        wpforo_trigger_custom_event(document,'wpforo_textarea_content_changed', e);
    });

    document.addEventListener('wpforo_tinymce_content_changed', wpforo_content_changed);
    document.addEventListener('wpforo_textarea_content_changed', wpforo_content_changed);
    document.addEventListener('wpforo_tinymce_ctrl_s', wpforo_content_ctrl_s);
    document.addEventListener('wpforo_textarea_ctrl_s', wpforo_content_ctrl_s);

    wpforo_tags_suggest();
    document.addEventListener('wpforo_topic_portable_form', function(e){
        wpforo_tags_suggest();
        window.wpforo_fix_form_data_attributes();
        var f = e.detail;
        if( f && f.length ){
            var t = $('[type="text"][required]', f);
            if( t.length && t.val().length ){
                wpforo_tinymce_initializeIt('.wp-editor-area');
            }else{
                wpforo_tinymce_initializeIt('.wp-editor-area', true);
                t.trigger("focus");
            }
        }
    });

    wpforo_wrap.on('click', '.wpforo-rcn-wrap .wpforo-rcn-dismiss-button:not(.wpf-processing)', function () {
        var $this = $(this);
        $this.addClass('wpf-processing');
        wpforo_load_show();
        var wrap = $(this).closest('.wpforo-rcn-wrap');
        $.ajax({
            type: 'POST',
            data: {
                backend: 0,
                action: 'wpforo_dissmiss_recaptcha_note'
            }
        }).done(function (response) {
            if( response.success ) {
                wrap.remove();
                wpforo_notice_show('done', 'success');
            }
        }).always(function () {
            wpforo_load_hide();
            $this.removeClass('wpf-processing');
        });
    });

    wpforo_wrap.on('click', '.wpf-admincp .wpf-acp-toggle:not(.wpf-processing)', function(){
        var $this = $(this);
        $this.addClass('wpf-processing');
        var wrap = $this.closest('.wpf-admincp');
        $('.wpf-acp-body', wrap).slideToggle(function(){
            $('.fas', $this).toggleClass('fa-minus-square fa-plus-square');
            var toggle_status = $(this).is(':visible') ? 'open' : 'close';
            $.ajax({
                type: 'POST',
                data:{
                    toggle_status: toggle_status,
                    action: 'wpforo_acp_toggle'
                }
            }).always(function(){
                $this.removeClass('wpf-processing');
            });
        });
    });
});

function wpforo_init_phrases(){
    if( $wpf.active === 0 ) {
        $wpf.ajax({
            url: wpforo.ajax_url,
            type: 'POST',
            dataType: 'json',
            async: false,
            data: {
                action: 'wpforo_get_phrases'
            }
        }).done(function (r) {
            window.wpforo_phrases = r;
        });
    }
}

function wpforo_ucwords (str) {
    return (str + '').replace(/^([a-z])|\s+([a-z])/, function ($1) {
        return $1.toUpperCase();
    });
}

function wpforo_topic_tools_tab_load() {
    var active_tab = $wpf('#wpf_moderation_tools').find('.wpf-tool-tab.wpf-tt-active');
    if( active_tab.length ){
        var active_tab_id = active_tab.attr('id');
        if( active_tab_id && $wpf.active === 0 ){
            wpforo_notice_hide();
            $wpf('#wpf_tool_tab_content_wrap').html('<i class="fas fa-spinner fa-spin wpf-icon-spinner"></i>');
            $wpf.ajax({
                type: 'POST',
                data: {
                    active_tab_id: active_tab_id,
                    action: 'wpforo_active_tab_content_ajax'
                }
            }).done(function(response){
                if( response ){
                    $wpf('#wpf_tool_tab_content_wrap').html(response);
                    $wpf('#wpf_moderation_tools').slideDown(400, 'linear');
                }
                wpforo_load_hide();
            });
        }
    }
}

function wpforo_tags_suggest(){
    var wpf_tags = $wpf('.wpf-tags');
    wpf_tags.suggest(
        wpforo.ajax_url + "?action=wpforo_tag_search",
        {   multiple:true,
            multipleSep: ",",
            resultsClass: 'wpf_ac_results',
            selectClass: 'wpf_ac_over',
            matchClass: 'wpf_ac_match',
            onSelect: function() {}
        }
    );
    $wpf('.wpf_ac_results').on("blur", function() {
        wpf_tags.removeClass( 'wpf-ac-loading' );
    });
    wpf_tags.on("blur", function() {
        $wpf(this).removeClass( 'wpf-ac-loading' );
    });
    wpf_tags.on("keydown",
        function ( e ) {
            var tags = wpf_tags.val();
            if( tags.length >= 1 ){
                switch(e.code) {
                    case 'ArrowUp':  // up
                    case 'ArrowDown':  // down
                    case 'Backspace':   // backspace
                    case 'Tab':   // tab
                    case 'Enter':  // return
                    case 'NumpadEnter':  // return
                    case 'Escape':  // escape
                    case 'Space':  // space
                    case 'Comma': // comma
                        $wpf(this).removeClass( 'wpf-ac-loading' ); break;
                    default:
                        $wpf(this).addClass( 'wpf-ac-loading' );
                }
            }
            setTimeout(function() { wpf_tags.removeClass( 'wpf-ac-loading' ); }, 1000);
        }
    );
}