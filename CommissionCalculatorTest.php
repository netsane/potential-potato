<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;

class CommissionCalculatorTest extends TestCase
{
    private $calculator;
    private $clientMock;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->calculator = new CommissionCalculator($this->clientMock);
    }

    public function testCalculateCommissions()
    {
        // Mocking BIN list response
        $binResponse = new Response(200, [], json_encode(['country' => ['alpha2' => 'DE']]));
        $this->clientMock->method('get')->willReturnOnConsecutiveCalls(
            $binResponse,
            new Response(200, [], json_encode(['rates' => ['USD' => 1.2, 'JPY' => 130, 'GBP' => 0.9]]))
        );

        $input = <<<EOD
{"bin":"45717360","amount":"100.00","currency":"EUR"}
{"bin":"516793","amount":"50.00","currency":"USD"}
{"bin":"45417360","amount":"10000.00","currency":"JPY"}
{"bin":"41417360","amount":"130.00","currency":"USD"}
{"bin":"4745030","amount":"2000.00","currency":"GBP"}
EOD;

        $expectedOutput = "1\n0.47\n0.77\n1.2\n22.22\n";
        $this->expectOutputString($expectedOutput);

        // Write input to a temporary file
        $inputFile = tempnam(sys_get_temp_dir(), 'input');
        file_put_contents($inputFile, $input);

        $this->calculator->calculateCommissions($inputFile);

        unlink($inputFile);
    }
}
