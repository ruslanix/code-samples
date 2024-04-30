<?php

namespace App\EventListener;

use App\Exception\QuizException;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AsEventListener(event: 'kernel.exception', method: 'onKernelException')]
class QuizExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof QuizException) {
            return;
        }

        $attributes = [
            '_controller' => 'App\Controller\QuizController::exception',
            'exception' => $exception
        ];
        $request = $event->getRequest()->duplicate(null, null, $attributes);
        $request->setMethod('GET');

        $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);
        $event->setResponse($response);
    }
}