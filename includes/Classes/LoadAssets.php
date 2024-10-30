<?php
namespace IMGPLTMain\Classes;

class LoadAssets
{
    public function enqueueAssets()
    {
        
            wp_enqueue_script('IMGPLT-script-boot', IMGPLT_URL . 'assets/js/start.js', array('jquery'), IMGPLT_VERSION, false);
            wp_enqueue_style('IMGPLT-global-styling', IMGPLT_URL . 'assets/css/start.css', array(), IMGPLT_VERSION);
        
    }

}
