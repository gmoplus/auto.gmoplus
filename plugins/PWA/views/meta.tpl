<!-- PWA meta data tpl -->
{if $files.icons.180_180}
<link rel="apple-touch-icon" sizes="180x180" href="{$files.icons.180_180}" />
{/if}
{if $files.icons.32_32}
<link rel="icon" type="image/png" sizes="32x32" href="{$files.icons.32_32}" />
{/if}
{if $files.icons.16_16}
<link rel="icon" type="image/png" sizes="16x16" href="{$files.icons.16_16}" />
{/if}
{if $files.manifest}
<link rel="manifest" href="{$files.manifest}">
{/if}
<meta name="msapplication-TileColor" content="#da532c">
{if $options.bgColor}
    <meta name="theme-color" content="#{$options.bgColor}">
{/if}

<meta name="mobile-web-app-capable" content="yes" />

{if $files.icons.1125_2436}
<!-- iPhone X (1125px x 2436px) -->
<link href="{$files.icons.1125_2436}"
      media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.750_1334}
<!-- iPhone 8, 7, 6s, 6 (750px x 1334px) -->
<link href="{$files.icons.750_1334}"
      media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.828_1792}
<!-- iPhone Xr (828px x 1792px) -->
<link href="{$files.icons.828_1792}"
      media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.1242_2688}
<!-- iPhone Xs Max (1242px x 2688px) -->
<link href="{$files.icons.1242_2688}"
      media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.1668_2388}
<!-- 11" iPad Pro (1668px x 2388px) -->
<link href="{$files.icons.1668_2388}"
      media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.1242_2208}
<!-- iPhone 8 Plus, 7 Plus, 6s Plus, 6 Plus (1242px x 2208px) -->
<link href="{$files.icons.1242_2208}"
      media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.640_1136}
<!-- iPhone 5, SE (640px x 1136px) -->
<link href="{$files.icons.640_1136}"
      media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.1536_2048}
<!-- iPad Mini, Air (1536px x 2048px) -->
<link href="{$files.icons.1536_2048}"
      media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.1668_2224}
<!-- iPad Pro 10.5" (1668px x 2224px) -->
<link href="{$files.icons.1668_2224}"
      media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2)"
      rel="apple-touch-startup-image"
/>
{/if}
{if $files.icons.2048_2732}
<!-- iPad Pro 12.9" (2048px x 2732px) -->
<link href="{$files.icons.2048_2732}"
      media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)"
      rel="apple-touch-startup-image"
/>
{/if}
<!-- PWA meta data tpl end -->
