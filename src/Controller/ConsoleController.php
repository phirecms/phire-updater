<?php
/**
 * Phire Updater Application
 *
 * @link       https://github.com/phirecms/phire-updater
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Phire\Updater\Controller;

use Pop\Controller\AbstractController;
use Pop\Console\Console;
use Pop\Http\Client\Curl;

/**
 * Updater Console Controller class
 *
 * @category   Phire\Updater
 * @package    Phire\Updater
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
class ConsoleController extends AbstractController
{

    /**
     * Console object
     * @var Console
     */
    protected $console = null;

    /**
     * Parse modules
     * @var array
     */
    protected $modules = [];

    /**
     * Current version data
     * @var array
     */
    protected $current = [
        'phirecms' => '',
        'modules'  => [],
        'themes'   => []
    ];

    /**
     * JSON data
     * @var array
     */
    protected $json = [
        'phirecms' => '',
        'modules'  => [],
        'themes'   => []
    ];

    /**
     * Options for cURL
     * @var array
     */
    protected $options = [
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:40.0) Gecko/20100101 Firefox/40.0'
        ]
    ];

    /**
     * GitHub URLs
     * @var array
     */
    protected $urls = [
        'phirecms' => 'https://api.github.com/repos/phirecms/phirecms/releases/latest',
        'module'   => 'https://api.github.com/repos/phirecms/[{module}]/releases/latest',
        'theme'    => 'https://api.github.com/repos/phirecms/[{theme}]/releases/latest',
        'file'     => 'https://github.com/phirecms/[{file}]/archive/[{tag_name}].zip'
    ];

    /**
     * Client ID
     * @var string
     */
    protected $clientId = 'xxxx';

    /**
     * Client Secret
     * @var string
     */
    protected $clientSecret = 'yyyy';

    public function __construct()
    {
        $this->console = new Console();
        $this->modules = json_decode(file_get_contents(__DIR__ . '/../../data/modules.json'), true);
        $this->themes  = json_decode(file_get_contents(__DIR__ . '/../../data/themes.json'), true);
        if (file_exists(__DIR__ . '/../../data/updates.json')) {
            $this->current = json_decode(file_get_contents(__DIR__ . '/../../data/updates.json'), true);
        }
    }

    public function index()
    {
        $this->console->write();
        $this->console->write(
            '    Fetching: ' . $this->console->colorize('phirecms/phirecms', Console::BOLD_CYAN) . '...', false
        );

        // Get latest version of phirecms
        $url =  $this->urls['phirecms'] . '?client_id=' . $this->clientId .'&client_secret=' . $this->clientSecret;
        $curl = new Curl($url, $this->options);
        $curl->send();

        if ($curl->getCode() == 200) {
            $body = json_decode($curl->getBody(), true);
            $this->json['phirecms'] = (isset($body['tag_name'])) ? $body['tag_name'] : $this->current['phirecms'];
            $this->console->write();
        } else {
            $this->console->write(' ' . $this->console->colorize('Error', Console::BOLD_RED) . '.');
        }

        // Get latest versions of modules
        foreach ($this->modules as $module) {
            // Get version
            $this->console->write(
                '    Fetching: ' . $this->console->colorize($module, Console::BOLD_CYAN) . '...', false
            );

            $url  = str_replace('[{module}]', $module, $this->urls['module']) .
                '?client_id=' . $this->clientId .'&client_secret=' . $this->clientSecret;
            $curl = new Curl($url, $this->options);
            $curl->send();

            if ($curl->getCode() == 200) {
                $body = json_decode($curl->getBody(), true);
                if (isset($body['tag_name'])) {
                    $this->json['modules'][$module] = $body['tag_name'];

                    // Get file
                    if (!isset($this->current['modules'][$module]) || (isset($this->current['modules'][$module]) &&
                        (version_compare($this->current['modules'][$module], $this->json['modules'][$module]) < 0))) {
                        $this->console->write(' Downloading...', false);

                        if (file_exists(__DIR__ . '/../../public/releases/modules/' . $module . '.zip')) {
                            unlink(__DIR__ . '/../../public/releases/modules/' . $module . '.zip');
                        }
                        $file = str_replace(['[{file}]', '[{tag_name}]'], [$module, $body['tag_name']], $this->urls['file']);
                        file_put_contents(
                            __DIR__ . '/../../public/releases/modules/' . $module . '-' . $body['tag_name'] . '.zip',
                            file_get_contents($file)
                        );
                    }
                    $this->console->write(' Complete.');
                }
            } else {
                $this->console->write(' ' . $this->console->colorize('Error', Console::BOLD_RED) . '.');
            }
        }

        $this->console->write();

        // Get latest versions of themes
        foreach ($this->themes as $theme) {
            // Get version
            $this->console->write(
                '    Fetching: ' . $this->console->colorize($theme, Console::BOLD_CYAN) . '...', false
            );

            $url  = str_replace('[{theme}]', $theme, $this->urls['theme']) .
                '?client_id=' . $this->clientId .'&client_secret=' . $this->clientSecret;
            $curl = new Curl($url, $this->options);
            $curl->send();

            if ($curl->getCode() == 200) {
                $body = json_decode($curl->getBody(), true);
                if (isset($body['tag_name'])) {
                    $this->json['themes'][$theme] = $body['tag_name'];

                    // Get file
                    if (!isset($this->current['themes'][$theme]) || (isset($this->current['themes'][$theme]) &&
                            (version_compare($this->current['themes'][$theme], $this->json['themes'][$theme]) < 0))) {
                        $this->console->write(' Downloading...', false);

                        if (file_exists(__DIR__ . '/../../public/releases/themes/' . $theme . '.zip')) {
                            unlink(__DIR__ . '/../../public/releases/themes/' . $theme . '.zip');
                        }
                        $file = str_replace(['[{file}]', '[{tag_name}]'], [$theme, $body['tag_name']], $this->urls['file']);
                        file_put_contents(
                            __DIR__ . '/../../public/releases/themes/' . $theme . '-' . $body['tag_name'] . '.zip',
                            file_get_contents($file)
                        );
                    }
                    $this->console->write(' Complete.');
                }
            } else {
                $this->console->write(' ' . $this->console->colorize('Error', Console::BOLD_RED) . '.');
            }
        }

        // Write new 'updates.json' file
        if (file_exists(__DIR__ . '/../../data/updates.json')) {
            unlink(__DIR__ . '/../../data/updates.json');
        }
        file_put_contents(__DIR__ . '/../../data/updates.json', json_encode($this->json, JSON_PRETTY_PRINT));

        $this->console->write();
        $this->console->write('    Done!');
        $this->console->write();
    }

}