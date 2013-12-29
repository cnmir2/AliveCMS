<!DOCTYPE html>
<html>
	<head>
		<title>{if $title}{$title}{/if}FusionCMS</title>

		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 

		<link rel="shortcut icon" href="{$url}application/themes/admin/images/favicon.png" />
		<link rel="stylesheet" href="{$url}application/themes/admin/css/main.css" type="text/css" />
        <!--<link rel="stylesheet" href="{$url}application/css/jquery-ui.css" type="text/css" />-->
		{if $extra_css}<link rel="stylesheet" href="{$url}application/{$extra_css}" type="text/css" />{/if}

		<script src="{if $cdn}//html5shiv.googlecode.com/svn/trunk/html5.js{else}{$url}application/js/html5shiv.js{/if}"></script>
        <script type="text/javascript" src="{$url}application/js/libs.js"></script>

		<script type="text/javascript">
			var Config = {
				URL: "{$url}",
				CSRF: getCookie('csrf_cookie_name'),
				isACP: true,
				defaultLanguage: "{$defaultLanguage}",
				languages: [ {foreach from=$languages item=language}"{$language}",{/foreach} ]
			};
		</script>

		<script src="{$url}application/themes/admin/js/router.js" type="text/javascript"></script>
        {if $require_js}
            <script type="text/javascript" src="/application/js/libs/require/require.js" data-main="/application/js/{$require_js}"></script>
        {else}
            <script src="{$url}application/js/libs/require/require.js" type="text/javascript" ></script>
        {/if}

		<script type="text/javascript">

			var scripts = [
				"{$url}application/js/libs/jquery/jquery.placeholder.min.js",
				"{$url}application/js/libs/jquery/jquery.transit.min.js",
				"{$url}application/js/ui.js",
				"{$url}application/js/fusioneditor.js"
				{if $extra_js},"{$url}application/{$extra_js}"{/if}
			];

			require(scripts, function()
			{
				$(document).ready(function()
				{
					UI.initialize();

					{if $extra_css}
						Router.loadedCSS.push("{$extra_css}");
					{/if}

					{if $extra_js}
						Router.loadedJS.push("{$extra_js}");
					{/if}
				});
			});

        </script>

		<!--[if IE]>
			<style type="text/css">
			#main .right h2 img {
				position:relative;
			}
			</style>
		<![endif]-->

		<!--[if LTE IE 7]>
			<style type="text/css">
			#main .right .statistics span {
				width:320px;
			}
			</style>
		<![endif]-->
	</head>

	<body>
		<div id="popup_bg"></div>

		<!-- confirm box -->
		<div id="confirm" class="popup">
			<h1 class="popup_question" id="confirm_question"></h1>

			<div class="popup_links">
				<a href="javascript:void(0)" class="popup_button" id="confirm_button"></a>
				<a href="javascript:void(0)" class="popup_hide" id="confirm_hide" onClick="UI.hidePopup()">
					Cancel
				</a>
				<div style="clear:both;"></div>
			</div>
		</div>

		<!-- alert box -->
		<div id="alert" class="popup">
			<h1 class="popup_message" id="alert_message"></h1>

			<div class="popup_links">
				<a href="javascript:void(0)" class="popup_button" id="alert_button">Okay</a>
				<div style="clear:both;"></div>
			</div>
		</div>

		<!-- Top bar -->
		<header>
			<div class="center_1020">
                <a href="{$url}admin/" class="logo"></a>

                <!-- Top menu -->
				<aside class="right">
					<nav>
                        <a target="_blank" href="{$url}ucp" data-hasevent="1">
                            <div class="icon logout"></div>
							Go back
						</a>

						{if hasPermission("editSystemSettings", "admin")}
							<a href="{$url}admin/settings" {if $current_page == "admin/settings"}class="active"{/if}>
								<div class="icon settings"></div>
								Settings
							</a>
						{/if}

						<a href="{$url}admin/" {if $current_page == "admin/"}class="active"{/if}>
							<div class="icon dashboard"></div>
							Dashboard
						</a>
					</nav>

					<div class="welcome">
						Welcome, <b>{$nickname}</b>
					</div>
				</aside>
			</div>
		</header>

		<!-- Main content -->
		<section id="wrapper">
			<div id="top_spacer"></div>
			<div class="center_1020" id="main">

				<!-- Main Left column -->
				<aside class="left">
					<nav>
						{foreach from=$menu item=group key=text}
							{if count($group.links)}
								<a><div class="icon {$group.icon}"></div> {$text}</a>

								<section class="sub">
									{foreach from=$group.links item=link}
										<a href="{$url}{$link.module}/{$link.controller}" {if isset($link.active)}class="active"{/if}><div class="icon {$link.icon}"></div> {$link.text}</a>
									{/foreach}
								</section>
							{/if}
						{/foreach}
					</nav>

					<article>
						<h1>Welcome to FusionCMS</h1>
						<b>Dear customer</b>, We are happy to introduce you to the next major version of our very own FusionCMS. Years have passed since the initial release and the system has grown better and stronger for every version. The core of this beast is powered by clean, object oriented PHP code, kept in shape by the incredibly powerful CodeIgniter framework. On the front we also make sure to amaze your visitors with more Javascript-powered live interactions than ever before.
						<div class="clear"></div>
					</article>
					<div class="spacer"></div>
				</aside>

				<!-- Main right column -->
				<aside class="right">
					{$page}
				</aside>

				<div class="clear"></div>
			</div>
		</section>

		<!-- Footer -->
		<footer>
			<div class="center_1020">
				<div class="divider2"></div>
				<aside id="logo"><a href="#" class="logo"></a></aside>
				<div class="divider"></div>
				<aside id="links">
					<a href="http://www.fusion-hub.com" target="_blank">FusionHub</a>
					<a href="http://www.fusion-hub.com/modules" target="_blank">Modules</a>
					<a href="http://www.fusion-hub.com/themes" target="_blank">Themes</a>
					<a href="http://www.fusion-hub.com/support" target="_blank">Support</a>
				</aside>
				<div class="divider"></div>
				<aside id="facebook">
					<h1>Like us on Facebook!</h1>
					<div id="fb_icon"></div>
					<a href="http://facebook.com/HeroicForge" target="_blank">HeroicForge</a>
				</aside>
				<div class="divider"></div>
				<aside id="html5">
					<a href="http://www.w3.org/html/logo/" data-tip="This website makes use of the next generation of web technologies">
						<img src="{$url}application/themes/admin/images/html5.png">
					</a>
				</aside>
				<div class="divider"></div>
				<div class="clear"></div>
			</div>
		</footer>

    </body>
</html>