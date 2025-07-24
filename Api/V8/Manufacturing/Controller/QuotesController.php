<?php
namespace Api\V8\Manufacturing\Controller;

use Slim\Http\Request;
use Slim\Http\Response as HttpResponse;

class QuotesController extends BaseManufacturingController
{
    public function getQuotes(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        return $this->generateSuccessResponse($response, []);
    }

    public function getQuote(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        return $this->generateSuccessResponse($response, []);
    }

    public function createQuote(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        return $this->generateSuccessResponse($response, [], self::HTTP_STATUS['CREATED']);
    }

    public function updateQuote(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        return $this->generateSuccessResponse($response, []);
    }

    public function generateQuotePDF(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        return $this->generateSuccessResponse($response, []);
    }

    public function acceptQuote(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        return $this->generateSuccessResponse($response, []);
    }

    public function emailQuote(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        return $this->generateSuccessResponse($response, []);
    }
}
