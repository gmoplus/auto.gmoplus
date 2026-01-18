
    <div id="cs-container" class="{$config.cs_input_type}">
        <h1>{$lang.cs_step_title}</h1>
        <div class="content-padding">
            <div class="info">{$lang.cs_step_hint}</div>
            <input placeholder="{if $config.cs_input_type !='vinaudit'}{$lang.cs_regnum_placeholder}{else}{$lang.cs_vinaudit_placeholder}{/if}" size="12" name="reg-number" type="text" />
            {if $config.cs_input_type !='vinaudit'}
                <input placeholder="{$lang.cs_odo_placeholder}" size="12" name="odometr" type="text" class="numeric" />
            {/if}
            <div class="form-buttons">
                <a id="reg_next" class="button" href="javascript:void(0)" class="button">{$lang.cs_create_ad}</a>
            </div>

            <div class="or-divider"><div class="left hborder"></div><b>{$lang.or}</b><div class="right hborder"></div></div>
        </div>
    </div>

    <script class="fl-js-dynamic">
    var file_url = rlConfig['ajax_url'] ? rlConfig['ajax_url'] : "{$smarty.const.RL_PLUGINS_URL}carSpecs/request.php";
    var next_step_path = "{$rlBase}add-listing/[category]/{$steps.plan.path}.html";
    var cs_create_ad = '{$lang.cs_create_ad}';
    {literal}
        $(document).ready(function(){
            var carSpecs  = new CarSpecsClass();
            carSpecs.enableClickHandlers();

            $.ajaxSetup({ cache: false });
            
            $('#controller_area > h1').before($('#cs-container'));
        });
    {/literal}
    </script>

