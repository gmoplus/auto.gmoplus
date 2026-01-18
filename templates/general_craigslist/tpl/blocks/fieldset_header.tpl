<!-- fieldset block -->

<div class="fieldset{if !$id} light{/if}{if $hide} hidden-default{/if}{if $bootstrap} bootstrapped col-12{/if}" {if $id}id="fs_{$id}"{/if}>
	<header class="{if $is_listing_detail}col-12{/if} {if $class}{$class}{/if}">{if $id}<span class="arrow"></span>{/if}{$name}</header>
		
	<div class="body{if $class && $is_listing_detail} {$class}{/if}">
		<div{if $bootstrap} class="row"{/if}>
