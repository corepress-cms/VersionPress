<?php

namespace VersionPress\Tests\End2End\BlockEditor;

use VersionPress\Tests\End2End\Utils\WpCliWorker;

class BlockEditorTestWpCliWorker extends WpCliWorker implements IBlockEditorTestWorker
{
    private $globalStylesId;

    public function createTemplate()
    {
        $this->wpAutomation->createPost([
            'post_type' => 'wp_template',
            'post_title' => 'VersionPress test template',
            'post_name' => 'vp-test-template',
            'post_status' => 'publish',
            'post_content' => '<!-- wp:paragraph --><p>Template</p><!-- /wp:paragraph -->',
        ]);
    }

    public function prepare_updateGlobalStyles()
    {
        $this->globalStylesId = $this->wpAutomation->createPost([
            'post_type' => 'wp_global_styles',
            'post_title' => 'Custom Styles',
            'post_name' => 'wp-global-styles-vp-test',
            'post_status' => 'publish',
            'post_content' => '{"version":3,"isGlobalStylesUserThemeJSON":true}',
        ]);
    }

    public function updateGlobalStyles()
    {
        $this->wpAutomation->editPost($this->globalStylesId, [
            'post_content' => '{"version":3,"isGlobalStylesUserThemeJSON":true,"styles":{"color":{"text":"#111"}}}',
        ]);
    }

    public function updateRuntimeOptions()
    {
        $this->wpAutomation->editOption('wp_calendar_block_has_published_posts', '1');
        $this->wpAutomation->editOption('user_count', (string)random_int(2, 1000));
    }

    public function createOembedCache()
    {
        $this->wpAutomation->createPost([
            'post_type' => 'oembed_cache',
            'post_title' => '',
            'post_name' => md5(uniqid()),
            'post_status' => 'publish',
            'post_content' => '{}',
        ]);
    }
}
