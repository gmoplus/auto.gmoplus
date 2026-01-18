{if $config.booking_colors }
    {assign var='unColors' value="|"|explode:$config.booking_colors}

    {if $unColors.0 && $unColors.1 && $unColors.2}
        <style type="text/css">
            .booked,
            .closed,
            .checkin,
            .checkout,
            .partially-booked {literal}{{/literal}
                background: rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1});
            {literal}}{/literal}
            .pr_checkin:not(.booked):not(.checkout):not(.daySelect):not(.current-request),
            .checkin:not(.booked):not(.pr_checkout):not(.daySelect):not(.current-request) {literal}{{/literal}
                background: linear-gradient(
                    to bottom right, 
                    transparent 0%, 
                    transparent 49%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 51%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 100%
                );
            {literal}}{/literal}
            .pr_checkout:not(.booked):not(.checkin):not(.daySelect):not(.current-request),
            .checkout:not(.booked):not(.pr_checkin):not(.daySelect):not(.current-request) {literal}{{/literal}
                background: linear-gradient(
                    to top left, transparent 0%, 
                    transparent 49%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 51%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 100%
                );
            {literal}}{/literal}
            .pr_checkin.daySelect,
            .checkin.daySelect,
            .pr_checkin.current-request,
            .checkin.current-request {literal}{{/literal}
                background: linear-gradient(
                    to top left, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 0%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 49%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 51%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 100%
                );
            {literal}}{/literal}
            .pr_checkout.daySelect,
            .checkout.daySelect,
            .pr_checkout.current-request,
            .checkout.current-request {literal}{{/literal}
                background: linear-gradient(
                    to bottom right, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 0%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 49%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 51%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 100%
                );
            {literal}}{/literal}
            .daySelect.start:not(.checkin):not(.pr_checkin):not(.checkout):not(.pr_checkout):not(.booked),
            .current-request.start:not(.checkin):not(.pr_checkin):not(.checkout):not(.pr_checkout):not(.booked) {literal}{{/literal}
                background: linear-gradient(
                    to bottom right, 
                    transparent 0%, 
                    transparent 49%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 51%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 100%
                );
            {literal}}{/literal}
            .daySelect.end:not(.checkin):not(.pr_checkin):not(.checkout):not(.pr_checkout):not(.booked),
            .current-request.end:not(.checkin):not(.pr_checkin):not(.checkout):not(.pr_checkout):not(.booked) {literal}{{/literal}
                background: linear-gradient(
                    to top left, transparent 0%, 
                    transparent 49%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 51%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 100%
                );
            {literal}}{/literal}
            .daySelect:not(.pr_checkin):not(.pr_checkout):not(.checkin):not(.checkout):not(.start):not(.end),
            .current-request:not(.pr_checkin):not(.checkin):not(.checkout):not(.pr_checkout) {literal}{{/literal}
                background: rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0});
            {literal}}{/literal}
            .calendar_td:hover .today:not(.booked), 
            .calendar_td:hover .available:not(.booked):not(.closed):not(.pr_checkout.checkin) {literal}{{/literal}
                box-shadow: inset 0 0 0 2px rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0});
            {literal}}{/literal}
            .calendar_td .closed {literal}{{/literal}
                background: rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1});
            {literal}}{/literal}
            .calendar_td .today {literal}{{/literal}
                color: rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0});
            {literal}}{/literal}

            /* highlight own pending requests */
            .calendar_td .own-request:not(.daySelect):not(.pr_checkin_own):not(.pr_checkout_own),
            .calendar_td .pr_checkin_own.pr_checkout_own {literal}{{/literal}
                background: rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2});
            {literal}}{/literal}
            .calendar_td .pr_checkin_own:not(.pr_checkout_own):not(.daySelect):not(.checkout):not(.pr_checkout) {literal}{{/literal}
                background: linear-gradient(
                    to bottom right, 
                    transparent 0%, 
                    transparent 49%, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 51%, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 100%
                );
            {literal}}{/literal}
            .calendar_td .pr_checkout_own:not(.pr_checkin_own):not(.daySelect):not(.checkin):not(.pr_checkin) {literal}{{/literal}
                background: linear-gradient(
                    to top left, transparent 0%, 
                    transparent 49%, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 51%, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 100%
                );
            {literal}}{/literal}
            .calendar_td .pr_checkout_own.pr_checkin:not(.pr_checkin_own),
            .calendar_td .pr_checkout_own.checkin {literal}{{/literal}
                background: linear-gradient(
                    to bottom right, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 0%, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 49%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 51%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 100%
                );
            {literal}}{/literal}
            .calendar_td .pr_checkin_own.pr_checkout:not(.pr_checkout_own),
            .calendar_td .pr_checkin_own.checkout {literal}{{/literal}
                background: linear-gradient(
                    to bottom right, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 0%, 
                    rgb{if $unColors.1|strpos:'.'}a{/if}({$unColors.1}) 49%, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 51%, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 100%
                );
            {literal}}{/literal}
            .calendar_td .pr_checkin_own.daySelect {literal}{{/literal}
                background: linear-gradient(
                    to bottom right, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 0%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 49%,
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 51%,
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 100%
                );
            {literal}}{/literal}
            .calendar_td .pr_checkout_own.daySelect {literal}{{/literal}
                background: linear-gradient(
                    to bottom right, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 0%, 
                    rgb{if $unColors.2|strpos:'.'}a{/if}({$unColors.2}) 49%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 51%, 
                    rgb{if $unColors.0|strpos:'.'}a{/if}({$unColors.0}) 100%
                );
            {literal}}{/literal}
        </style>
    {/if}
{/if}
