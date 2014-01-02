
{if count($realms) > 0}
    {foreach from=$realms item=realm}
	<div class="realm">
		<div class="realm_online">
			{if $realm->isOnline()}
				{$realm->getOnline()} / {$realm->getCap()}
			{else}
				{lang("offline")}
			{/if}
		</div>
		{$realm->getName()}
		
		<div class="realm_bar">
			{if $realm->isOnline()}
				<div class="realm_bar_fill" style="width:{$realm->getPercentage()}%"></div>
			{/if}
		</div>

		<!--
			Other values, for designers:

			$realm->getOnline("horde")
			$realm->getPercentage("horde")

			$realm->getOnline("alliance")
			$realm->getPercentage("alliance")

		-->

	</div>
    {/foreach}
{/if}

<div id="realmlist">set realmlist {$realmlist}</div>