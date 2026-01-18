<!-- OpenAI javascript on settings page -->

<script>
{literal}

(function(){
    var $provider  = $('select[name="post_config[openai_provider][value]"]');
    var $openaiRows = $('[name^="post_config[openai_openai_"]').closest('tr');
    var $yandexRows = $('[name^="post_config[openai_yandex_"]').closest('tr');
    var $deepseekRows = $('[name^="post_config[openai_deepseek_"]').closest('tr');

    var openAIHandler = function(){
        switch ($provider.val()) {
            case 'openai':
                $yandexRows.hide();
                $deepseekRows.hide();
                $openaiRows.show();
                break;

            case 'yandex':
                $yandexRows.show();
                $openaiRows.hide();
                $deepseekRows.hide();
                break;

            case 'deepseek':
                $yandexRows.hide();
                $openaiRows.hide();
                $deepseekRows.show();
                break;
        }
    }

    $provider.change(function(){
        openAIHandler();
    });

    openAIHandler();
})();

{/literal}
</script>

<!-- OpenAI javascript on settings page end -->
