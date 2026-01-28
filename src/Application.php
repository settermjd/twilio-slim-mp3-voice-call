<?php

declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Twilio\TwiML\VoiceResponse;

use function basename;
use function dirname;
use function file_exists;
use function file_get_contents;
use function is_readable;
use function json_encode;
use function mime_content_type;
use function realpath;
use function sprintf;

/**
 * This class encapsulates the central Slim application,
 * making it easier to create and test.
 */
final class Application
{
    private App $app;

    public function __construct(private readonly ContainerInterface $container)
    {
        AppFactory::setContainer($container);
        $this->app = AppFactory::createFromContainer($container);
    }

    public function setupRoutes(): void
    {
        $this->app->get('/', [$this, 'handleDefaultRoute']);
        $this->app->get('/assets/{filename}', [$this, 'serveFile']);
    }

    /**
     * This function returns TwiML that plays an audio file back to a caller
     *
     * The audio played back to the caller is retrieved from one of three locations:
     *   - From a directory within the application
     *   - From a hosted file
     *   - From a Twilio Asset
     */
    public function handleDefaultRoute(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        //$audioUrl = sprintf("%s/assets/%s", $_ENV['PUBLIC_URL'], $_ENV['AUDIO_FILE']);
        $audioUrl = $_ENV['TWILIO_ASSETS_URL'];

        $voiceResponse = new VoiceResponse();
        $voiceResponse->play($audioUrl);

        $response->getBody()->write($voiceResponse->asXML());
        return $response->withHeader("content-type", "application/xml");
    }

    /**
     * This function returns an audio file from the local filesystem with the applicable content type
     */
    public function serveFile(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        $file = sprintf(__DIR__ . "/../assets/%s", $args['filename']);
        if (! file_exists($file) || ! is_readable($file)) {
            $response->getBody()->write(
                json_encode(
                    [
                        'status' => 'error',
                        'statusCode' => 500,
                        'error' => [
                            'code' => 'FILE_NOT_AVAILABLE',
                            'message' => "The specified file either does not exist or is not readable.",
                            'path' => sprintf(
                                "%s/%s",
                                realpath(dirname($file)),
                                basename($file),
                            ),
                            'suggestion' => 'Please ensure that the file specified is both available and readable.',
                        ],
                    ],
                ),
            );
            return $response->withStatus(500);
        }

        $response->getBody()->write(
            file_get_contents($file),
        );
        return $response->withHeader("content-type", mime_content_type($file));
    }

    public function run(): void
    {
        $this->app->run();
    }
}
