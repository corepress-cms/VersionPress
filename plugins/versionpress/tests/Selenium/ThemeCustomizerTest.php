<?php

namespace VersionPress\Tests\Selenium;

use PHPUnit\Framework\Attributes\Test;

class ThemeCustomizerTest // extends SeleniumTestCase // temporarily disabled
{
    /**
     * @testdox Every change made in customizer creates 'theme/customize' action
     */
    #[Test]
    public function everyChangeMadeInCustomizerCreatesThemeCustomizeAction()
    {
        $this->url(self::$wpAdminPath . '/customize.php');
        $this->byCssSelector('#accordion-section-title_tagline .accordion-section-title')->click();
        $this->setValue('#customize-control-blogname input', 'Some name');
        $this->byId('save')->click();
        $this->waitForAjax();

        $this->setValue('#customize-control-blogname input', 'Blogname from customizer');
        $this->byId('save')->click();
        $this->waitForAjax();

        $lastCommit = $this->gitRepository->getCommit($this->gitRepository->getLastCommitHash());
        $this->assertContains('option/update', $lastCommit->getMessage()->getBody());
    }
}
