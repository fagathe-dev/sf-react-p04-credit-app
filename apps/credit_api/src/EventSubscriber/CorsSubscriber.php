<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CORS avec cookies pour les origines de CORS_ALLOW_ORIGIN (credit_tunnel, credit_client, credit_crm).
 * Les requêtes préliminaires OPTIONS sont traitées avant le firewall.
 */
final readonly class CorsSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_METHODS = 'GET, POST, PATCH, PUT, DELETE, OPTIONS';
    private const ALLOWED_HEADERS = 'Content-Type, Accept, X-Requested-With';
    private const MAX_AGE = 3600;

    /** @var list<string> */
    private array $allowedOrigins;

    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(
        #[Autowire(env: 'csv:CORS_ALLOW_ORIGIN')]
        array $allowedOrigins,
    ) {
        $this->allowedOrigins = array_values(array_filter(array_map(
            static fn (mixed $origin): string => rtrim(trim((string) $origin), '/'),
            $allowedOrigins,
        )));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 250],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest() || Request::METHOD_OPTIONS !== $request->getMethod() || !$request->headers->has('Access-Control-Request-Method')) {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        if ($this->isAllowed($request)) {
            $this->addHeaders($request, $response);
            $response->headers->set('Access-Control-Allow-Methods', self::ALLOWED_METHODS);
            $response->headers->set('Access-Control-Allow-Headers', self::ALLOWED_HEADERS);
            $response->headers->set('Access-Control-Max-Age', (string) self::MAX_AGE);
        }

        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() && $this->isAllowed($event->getRequest())) {
            $this->addHeaders($event->getRequest(), $event->getResponse());
        }
    }

    private function isAllowed(Request $request): bool
    {
        $origin = $request->headers->get('Origin');

        return null !== $origin && \in_array(rtrim($origin, '/'), $this->allowedOrigins, true);
    }

    private function addHeaders(Request $request, Response $response): void
    {
        $response->headers->set('Access-Control-Allow-Origin', (string) $request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->setVary(array_unique([...$response->getVary(), 'Origin']));
    }
}
