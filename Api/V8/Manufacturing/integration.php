<?php
/**
 * Manufacturing API Integration with SuiteCRM V8 API
 * 
 * This file integrates the manufacturing API routes with the main SuiteCRM API system.
 * It should be included in the main routes configuration.
 * 
 * @author AI Assistant
 * @version 1.0.0
 */

// Include manufacturing routes in the main V8 API
$app->group('/V8', function () use ($app) {
    // Include manufacturing routes
    include __DIR__ . '/Config/routes.php';
})->add(new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)));
