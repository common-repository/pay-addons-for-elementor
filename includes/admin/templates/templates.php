<?php
$templates = \Elementor_Pay_Addons\Core\Config_Service::get_templates() ?? [];

$isPremium = Elementor_Pay_Addons\Shared\Security_Utils::is_pro();
?>

<div id="templates" class="epa-tab-panel p-8">
  <div class="mx-auto max-w-2xl py-4 px-4 sm:px-6 lg:max-w-7xl lg:px-8">
    <h2 class="text-2xl font-bold tracking-tight text-gray-900">Templates For Elementor</h2>
    <div class="mt-6 grid grid-cols-1 gap-y-10 gap-x-6 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
      <?php foreach ($templates as $key => $template) { ?>
        <div class="relative">
          <div class="relative">
            <?php if($template['plan'] === 'pro') { ?><span class="z-10 absolute right-1 top-1 bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-yellow-300 border border-yellow-300">Pro</span><?php } ?>
            <div class="flex items-center min-h-40 w-full overflow-hidden rounded-md hover:opacity-75 bg-gray-200 lg:h-40">
              <img src="<?php echo esc_url($template['preview']) ?>" alt="" class="p-2 h-full w-full object-center">
            </div>
          </div>
          <h3 class="mt-4 w-full font-bold text-center text-sm text-gray-700"><?php echo esc_html($template['name']) ?></h3>
          <div class="mt-2">
            <p class="mt-1 text-sm text-gray-500 h-16 line-clamp-3" title="<?php echo esc_html($template['desc']) ?>"><?php echo esc_html($template['desc']) ?></p>
          </div>
          <div class="mt-4">
            <div class="flex justify-between">
              <a href="<?php echo esc_url($template['doc_url']) ?>" target="_blank" class="inline-flex items-center h-6 px-3 py-2 text-sm font-medium text-center text-gray-500 border border-gray-300 hover:text-gray-700 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-full p-2.5">
                Docs
              </a>
              <?php if($isPremium || $template['plan'] === 'free') { ?>
              <a href="<?php echo esc_url($template['download_url']) ?>" data-url="<?php echo esc_url($template['download_url']) ?>" target="_blank" class="link-template-download inline-flex items-center h-6 px-3 py-2 text-sm font-medium text-center text-gray-500 border border-gray-300 hover:text-gray-700 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-full p-2.5">
                Insert
                <svg aria-hidden="true" class="ml-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path clip-rule="evenodd" fill-rule="evenodd" d="M5.5 17a4.5 4.5 0 01-1.44-8.765 4.5 4.5 0 018.302-3.046 3.5 3.5 0 014.504 4.272A4 4 0 0115 17H5.5zm5.25-9.25a.75.75 0 00-1.5 0v4.59l-1.95-2.1a.75.75 0 10-1.1 1.02l3.25 3.5a.75.75 0 001.1 0l3.25-3.5a.75.75 0 10-1.1-1.02l-1.95 2.1V7.75z"></path>
                </svg>
              </a>
              <?php } ?>
            </div>
          </div>
        </div>
      <?php  } ?>
    </div>
  </div>
  <div id="alert"></div>
</div>