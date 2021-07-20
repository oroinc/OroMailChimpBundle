<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Mailchimp API client's dedicated bad response exception class.
 */
class BadResponseException extends \RuntimeException implements MailChimpTransportException
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Factory method to create a new response exception based on the response code.
     *
     * @param string            $url
     * @param string            $parameters
     * @param ResponseInterface $response Response received
     * @param string|null       $label
     * @return BadResponseException
     */
    public static function factory($url, $parameters, ResponseInterface $response, string $label = null): self
    {
        if (!$label) {
            $label = $response->getReasonPhrase();
        }

        $message = $label.PHP_EOL.implode(PHP_EOL, [
                    '[status code] '.$response->getStatusCode(),
                    '[API error code] '.$response->getHeaderLine('X-MailChimp-API-Error-Code'),
                    '[reason phrase] '.$response->getReasonPhrase(),
                    '[url] '.$url,
                    '[request parameters]'.$parameters,
                    '[content type] '.$response->getHeaderLine('Content-Type'),
                    '[response body] '.(string)$response->getBody(),
                ]);

        $result = new static($message);
        $result->setResponse($response);

        return $result;
    }

    /**
     * Set the response that caused the exception
     *
     * @param ResponseInterface $response Response to set
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Get the response that caused the exception
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
