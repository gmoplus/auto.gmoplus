<!-- fieldset block -->

<div class="fieldset{if !$id} light{/if}{if $hide} hidden-default{/if}" {if $id}id="fs_{$id}"{/if}>
	<header{if $line} accesskey="{$name}"{/if}{if $class} class="{$class}"{/if}>{if $id}<span class="arrow"></span>{/if}{if !$line}{$name}{/if}</header>
	<div class="body">
		<div>