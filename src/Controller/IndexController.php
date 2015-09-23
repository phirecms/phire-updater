<?php

namespace Phire\Updater\Controller;

use Pop\Controller\AbstractController;
use Pop\Ftp\Ftp;
use Pop\Http\Request;
use Pop\Http\Response;

class IndexController extends AbstractController
{

    /**
     * Request object
     * @var Request
     */
    protected $request  = null;

    /**
     * Response object
     * @var Response
     */
    protected $response = null;

    public function __construct()
    {
        $this->request  = new Request();
        $this->response = new Response([
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public function index()
    {
        if ($this->isValidRequest()) {
            $this->response->setBody(file_get_contents(__DIR__ . '/../../data/updates.json'));
            $this->response->send();
        } else {
            $this->error();
        }
    }

    public function latest($resource)
    {
        if ($this->isValidRequest()) {
            $updates = json_decode(file_get_contents(__DIR__ . '/../../data/updates.json'), true);
            $json    = [];
            $code    = 200;

            switch ($resource) {
                case 'phirecms':
                    $json['resource'] = $resource;
                    $json['version']  = $updates['phirecms'];
                    break;
                default:
                    if (isset($updates['modules'][$resource])) {
                        $json['resource'] = $resource;
                        $json['version']  = $updates['modules'][$resource];
                    } else {
                        $json['error'] = 'Resource not found.';
                        $code = 404;
                    }
            }

            $this->response->setBody(json_encode($json, JSON_PRETTY_PRINT));
            $this->response->send($code);
        } else {
            $this->error();
        }
    }

    public function fetch($resource)
    {
        if ($this->isValidPostData($this->request->getPost())) {
            $data = $this->parsePostData();
            try {
                $ftp = new Ftp($data['address'], $data['username'], $data['password'], $data['ssl']);
                $ftp->pasv($data['pasv']);

                if (!empty($data['root'])) {
                    $root = (strpos($data['root'], '/') !== false) ?
                        explode('/', $data['root']) : [$data['root']];
                    foreach ($root as $r) {
                        $ftp->chdir($r);
                    }
                }
                if (!empty($data['base_path'])) {
                    $base = (strpos($data['base_path'], '/') !== false) ?
                        explode('/', $data['base_path']) : [$data['base_path']];
                    foreach ($base as $b) {
                        $ftp->chdir($b);
                    }
                }
                if (!empty($data['content_path'])) {
                    $content = (strpos($data['content_path'], '/') !== false) ?
                        explode('/', $data['content_path']) : [$data['content_path']];
                    foreach ($content as $c) {
                        $ftp->chdir($c);
                    }
                }

                switch ($resource) {
                    case 'phirecms':
                        if (null !== $this->request->getQuery('move')) {
                            $ftp->rename(
                                '/' . (!empty($data['root']) ? $data['root'] . '/' : '') . (!empty($data['base_path']) ? $data['base_path'] . '/' : '') . $data['content_path'] . '/phire-cms-new',
                                '/' . (!empty($data['root']) ? $data['root'] . '/' : '') . (!empty($data['base_path']) ? $data['base_path'] . '/' : '') . '/phire-cms-new'
                            );
                        } else {
                            $ftp->put('phirecms.zip', __DIR__ . '/../../public/releases/phire/phirecms.zip');
                            $ftp->chmod('phirecms.zip', 0777);
                        }
                        break;
                    default:
                        if (file_exists(__DIR__ . '/../../public/releases/modules/' . $resource . '.zip')) {
                            $ftp->put($resource . '.zip', __DIR__ . '/../../public/releases/modules/' . $resource . '.zip');
                            $ftp->chmod($resource . '.zip', 0777);
                        }
                }
                $this->response->setBody(json_encode(['message' => 'Successful transfer.'], JSON_PRETTY_PRINT));
                $this->response->send(200);
            } catch (\Exception $e) {
                $this->response->setBody(json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT));
                $this->response->send(401);
            }
        } else {
            $this->error();
        }
    }

    public function test()
    {
        if ($this->isValidPostData($this->request->getPost())) {
            $data = $this->parsePostData();
            try {
                $ftp = new Ftp($data['address'], $data['username'], $data['password'], $data['ssl']);
                $this->response->setBody(json_encode(['message' => 'Successful test to the FTP server.'], JSON_PRETTY_PRINT));
                $this->response->send(200);
            } catch (\Exception $e) {
                $this->response->setBody(json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT));
                $this->response->send(401);
            }
        } else {
            $this->error();
        }
    }

    public function error()
    {
        $this->response->setBody(json_encode(['error' => 'Resource not found'], JSON_PRETTY_PRINT));
        $this->response->send(404);
    }

    private function isValidRequest()
    {
        $result = false;
        if ((null !== $this->request->getHeader('Authorization')) &&
            (null !== $this->request->getHeader('User-Agent'))) {
            $token = base64_decode($this->request->getHeader('Authorization'));
            $ua    = $this->request->getHeader('User-Agent');
            if (stripos($ua, 'curl') === false) {
                if (substr($token, 0, 14) == 'phire-updater-') {
                    if (is_numeric(substr($token, 15))) {
                        $result = true;
                    }
                }
            }
        }

        return $result;
    }

    private function isValidPostData($post)
    {
        return (isset($post['ftp_address']) && isset($post['ftp_username']) && isset($post['ftp_password']) &&
            isset($post['base_path']) && isset($post['content_path']));
    }

    private function parsePostData()
    {
        $data = [
            'address'      => $this->request->getPost('ftp_address'),
            'username'     => $this->request->getPost('ftp_username'),
            'password'     => $this->request->getPost('ftp_password'),
            'root'         => $this->request->getPost('ftp_root'),
            'pasv'         => (bool)$this->request->getPost('use_pasv'),
            'ssl'          => (bool)$this->request->getPost('protocol'),
            'base_path'    => $this->request->getPost('base_path'),
            'content_path' => $this->request->getPost('content_path')
        ];

        if (substr($data['root'], 0, 1) == '/') {
            $data['root'] = substr($data['root'], 1);
        }

        if (substr($data['base_path'], 0, 1) == '/') {
            $data['base_path'] = substr($data['base_path'], 1);
        }

        if (substr($data['content_path'], 0, 1) == '/') {
            $data['content_path'] = substr($data['content_path'], 1);
        }

        return $data;
    }

}

