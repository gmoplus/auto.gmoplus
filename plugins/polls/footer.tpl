<!-- polls footer tpl -->

<script class="fl-js-dynamic">
var poll_lang = Array();
poll_lang['vote'] = "{$lang.vote}";
poll_lang['notice_vote_accepted'] = "{phrase key='notice_vote_accepted' db_check=true}";

{literal}
$(document).ready(function() {
    // open results
    $('div.poll-nav span.link').click(function(){
        $(this).closest('.poll-inquirer').slideUp();
        $(this).closest('.poll-inquirer').next().slideDown();
    });

    // hide results
    $('div.poll-results span.link').click(function(){
        $(this).closest('.poll-results').slideUp();
        $(this).closest('.poll-results').prev().slideDown();
    });

    // submit handler
    $('div.poll-nav input').click(function(){
        var parent = $(this).closest('div.poll-container');
        var poll_id = parent.attr('id').split('_')[2];
        var vote_id = parent.find('ul.poll-answer input:checked').val();

        if (vote_id > 0 && poll_id > 0) {
            $(this).val(lang.loading).prop('disabled', true).addClass('disabled');

            flUtil.ajax({mode: 'poll_vote', item: poll_id, vote: vote_id}, function (response) {
                $(this).val(poll_lang.vote).prop('disabled', false).removeClass('disabled');

                if (response && response.status && response.status === 'OK') {
                    printMessage('notice', poll_lang.notice_vote_accepted);

                    // append content
                    parent.find('ul.poll-votes').empty();
                    $('#pollItem').tmpl(response.results.items).appendTo(parent.find('ul.poll-votes'));
                    parent.find('div.poll-results div.poll-total b').text(response.results.total);

                    // animate
                    parent.find('div.poll-results-nav').hide();
                    parent.find('div.poll-nav span.link').trigger('click');
                    parent.find('div.poll-nav').slideUp();
                } else {
                    printMessage('error', lang.system_error);
                }
            });
        }
    });
});

{/literal}
</script>

<!-- polls footer tpl end -->
