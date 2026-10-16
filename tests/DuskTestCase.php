<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions);

        $possibleBinaries = [
            base_path('vendor/laravel/dusk/bin/chrome-win64/chrome.exe'),
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        ];

        foreach ($possibleBinaries as $binary) {
            if (file_exists($binary)) {
                $options->setBinary($binary);
                break;
            }
        }

        $headless = env('DUSK_HEADLESS', true);
        if (isset($_ENV['DUSK_HEADLESS'])) {
            $headless = filter_var($_ENV['DUSK_HEADLESS'], FILTER_VALIDATE_BOOLEAN);
        }
        if ($this->hasHeadlessDisabled()) {
            $headless = false;
        }

        $arguments = collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--ignore-certificate-errors',
            '--allow-insecure-localhost',
            '--no-sandbox',
        ]);

        if ($headless) {
            $arguments = $arguments->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        }

        $options->addArguments($arguments->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://127.0.0.1:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
