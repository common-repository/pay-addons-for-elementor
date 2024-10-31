<?php
$elements = Elementor_Pay_Addons\Core\Config::$elements;
$is_elementor_pro = Elementor_Pay_Addons\Shared\Security_Utils::is_elementor_pro(); 
?>

<div id="elements" class="epa-tab-panel p-8">
  <div class="flex justify-between items-center pb-5 border-b border-gray-300">
    <div>
      <h4>Widgets</h4>
      <span class="text-gray-400">Use the Toggle Button to Activate or Deactivate all the Elements at once.</span>
    </div>
    <label class="relative inline-flex items-center mr-5 cursor-pointer">
      <input id="chkEnableAll" type="checkbox" value="" class="sr-only peer" checked>
      <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
      <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Enable All</span>
    </label>
  </div>
  <div class="py-4">
    <div class="grid gap-4 grid-cols-3 grid-rows-2">
      <?php
      foreach ($elements as $key => $val) { ?>
        <div class="epa-element-card">
          <?php if ($val['plan'] === 'pro') { ?><span class="absolute right-1 top-1 bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-yellow-300 border border-yellow-300">Pro</span><?php } ?>
          <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"><?php echo esc_html($val['name']) ?></h5>
          <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
            <?php echo esc_html($val['desc']) ?>
          </p>
          <div class="absolute bottom-2 right-2">
            <?php if ($val['category'] == 'widget') { ?>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="<?php echo esc_html($key) ?>" value="<?php echo esc_html($key) ?>" class="sr-only peer" checked>
                <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
              </label>
            <?php } ?>
            <?php if (isset($val['warning'])) { ?>
             <div class="flex text-sm <?php echo $is_elementor_pro ? 'text-green-800': 'text-red-800'; ?>" role="alert">
              <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
              <span class="sr-only">Info</span>
              <div><?php echo $is_elementor_pro ? 'You are free to use.' : esc_html($val['warning']); ?></div>
            </div>
            <?php } ?>
          </div>
        </div>
      <?php  } ?>
    </div>
  </div>
  <div class="flex justify-end">
    <button type="button" class="btn-save-element epa-button-primary">Save changes</button>
  </div>
  <div id="alert"></div>
</div>