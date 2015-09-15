<?php

namespace Phire\Updater\Controller;

use Pop\Controller\AbstractController;
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

    public function fetch()
    {
        if ($this->isValidRequest()) {
            $this->response->setBody(json_encode(['message' => 'Feature coming soon.'], JSON_PRETTY_PRINT));
            $this->response->send();
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

}

