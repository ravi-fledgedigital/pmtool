<?php

namespace Seoulwebdesign\Kakaopay\Gateway\Command;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderComposite;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Seoulwebdesign\Base\Gateway\Http\Client\AbstractTransaction;

/**
 * Class AuthorizeCommand
 */
class CaptureCommand implements CommandInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HandlerInterface
     */
    private $handler;
    /**
     * @var AbstractTransaction
     */
    private $client;
    /**
     * @var BuilderComposite
     */
    private $requestBuilder;
    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * AuthorizeCommand constructor.
     * @param RequestInterface $request
     * @param ValidatorInterface $validator
     * @param LoggerInterface $logger
     * @param HandlerInterface $handler
     * @param AbstractTransaction $client
     * @param BuilderComposite $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     */
    public function __construct(
        RequestInterface $request,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        HandlerInterface $handler,
        AbstractTransaction $client,
        BuilderComposite $requestBuilder,
        TransferFactoryInterface $transferFactory
    ) {
        $this->handler = $handler;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->request = $request;
        $this->client = $client;
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
    }

    /**
     * @param array $commandSubject
     * @return Command\ResultInterface|void|null
     * @throws CommandException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function execute(array $commandSubject)
    {
        $this->logger->info(__METHOD__);
        // @TODO implement exceptions catching
        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        $response = $this->client->placeRequest($transferO);
        if ($this->validator !== null) {
            $result = $this->validator->validate(
                array_merge($commandSubject, ['response' => $response])
            );
            if (!$result->isValid()) {
                $this->processErrors($result);
            }
        }

        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                $response
            );
        }
    }

    /**
     * @param ResultInterface $result
     * @throws CommandException
     */
    protected function processErrors(ResultInterface $result)
    {
        $message = $result->getFailsDescription();
        $message = $message ? $message : 'Transaction has been declined. Please try again later.';
        throw new CommandException(
            __($message)
        );
    }

    /**
     * @param Phrase[] $fails
     * @return void
     */
    private function logExceptions(array $fails)
    {
        foreach ($fails as $failPhrase) {
            $this->logger->critical((string) $failPhrase);
        }
    }
}
