<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Stub;

use GuzzleHttp\Psr7\Response;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;
use Symfony\Component\Yaml\Yaml;

class MailChimpClientStub extends MailChimpClient
{
    /**
     * Loads data from fixtures by originId
     *
     */
    public function export($methodName, array $parameters): Response
    {
        $fileName = $parameters['id'] . '.yml';
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . $fileName;

        $response = Yaml::parse(file_get_contents($filePath));

        if (!is_array($response)) {
            throw new \InvalidArgumentException(
                sprintf('Fixture "%s" not found', $fileName)
            );
        }

        return new Response($response['code'], $response['headers'], json_encode($response['body']));
    }
}
