<?php

namespace Phire\Updater\Controller;

use Pop\Controller\AbstractController;
use Pop\Http\Request;
use Pop\Http\Response;

class IndexController extends AbstractController
{

    protected $request  = null;
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
            $updates = file_get_contents(__DIR__ . '/../../data/updates.json');
            $json    = json_decode($updates, true);
            if (null !== $this->request->getPost('phire')) {
                $this->response->setBody(json_encode(['version' => $json['phire']['latest']], JSON_PRETTY_PRINT));
                $this->response->send();
            } else if (null !== $this->request->getPost('module')) {
                $module = $this->request->getPost('module');
                if (isset($json['modules'][$module])) {
                    $this->response->setBody(json_encode(['version' => $json['modules'][$module]['latest']], JSON_PRETTY_PRINT));
                    $this->response->send();
                } else {
                    $this->response->setBody(json_encode(['error' => 'Module not found.'], JSON_PRETTY_PRINT));
                    $this->response->send(404);
                }
            } else {
                $this->response->setBody($updates);
                $this->response->send();
            }
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
        if ((null !== $this->request->getHeader('Authorization')) && (null !== $this->request->getHeader('User-Agent'))) {
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

