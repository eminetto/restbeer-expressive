<?php

namespace RestBeer;

use Zend\Stratigility\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Zend\Expressive\Template\Twig;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\HtmlResponse;

class Format implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $content = explode(',', $response->getBody());
        $accept = $request->getHeader('accept');
        switch ($accept[0]) {
            case 'text/json':
                return new JsonResponse($content, $response->getStatusCode());
                break;
            default:
                return new HtmlResponse($this->formatHtml($content));
                break;
        }
    }

    private function formatHtml($content)
    {
        $twig = new Twig();
        $twig->addPath('views');
        return $twig->render('content.twig', ['content' => $content]);
    }

}