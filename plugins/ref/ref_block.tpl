<!-- referent number look up block -->

<form name="refnumber_lookup" action="" method="post" class="d-flex flex-wrap" style="gap: 14px">
    <div class="flex-fill"><input class="w-100" type="text" id="ref_input" value="{$lang.ref_label}" /></div>
    <input class="flex-fill" type="submit" value="{$lang.search}" />
</form>

<script class="fl-js-dynamic">
var refnumber_input_default = "{$lang.ref_label}";
var search_phrase = "{$lang.search}";

{literal}
    $(document).ready(function () {
        var refNumber = new refNumberClass();

        $('form[name=refnumber_lookup] input[type=text]').focus(function () {
            if ($(this).val() == refnumber_input_default) {
                $(this).val('');
            }
        }).blur(function () {
            if ($(this).val() == '') {
                $(this).val(refnumber_input_default);
            }
        });
        $('form[name=refnumber_lookup]').submit(function () {
            if ($('#ref_input').val()) {
                var $searchButton = $('form[name=refnumber_lookup] input[type=submit]');

                $searchButton.val(lang['loading']);
                refNumber.refSearch($('form[name=refnumber_lookup] input[type=text]').val());
                $searchButton.val(search_phrase);
            }

            return false;
        });
    });
{/literal}
</script>

<!-- referent number look up block end -->
