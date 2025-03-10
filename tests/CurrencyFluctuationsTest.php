<?php

namespace AmrShawky\Currency\Tests;

use AmrShawky\Currency\CurrencyFluctuations;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class CurrencyFluctuationsTest extends TestCase
{
    use ClientMock, Throwable;

    protected function fluctuations()
    {
        return (new CurrencyFluctuations('2021-08-10', '2021-08-10', $this->client))
            ->symbols(['USD','CZK', 'EGP', 'GBP'])
            ->amount(30)
            ->when($this->throw, function (CurrencyFluctuations $currencyFluctuations) {
                return $currencyFluctuations->throw($this->throw_callback ?? null);
            })
            ->get();
    }

    /**
     * @param array|null $rates
     *
     * @return \GuzzleHttp\Client
     */
    private function successMock($rates = null)
    {
        $rates = $rates ?? ['2021-06-10' => ['USD' => 6.0]];

        return $this->mock([
            new Response(200, [], json_encode([
                'success'   => true,
                'rates'     => $rates
            ]))
        ]);
    }

    /**
     * @test
     */
    public function it_returns_null_when_it_fails_and_throw_is_false()
    {
        $this->client = $this->mock([
            new RequestException('Error Communicating with Server', new Request('GET', 'test')),
            new Response(500),
            new Response(400)
        ]);

        $this->assertNull($this->fluctuations());
        $this->assertNull($this->fluctuations());
        $this->assertNull($this->fluctuations());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_http_fails_and_throw_is_true()
    {
        $this->expectException(\AmrShawky\Currency\Exceptions\RequestException::class);

        $this->client = $this->mock([
            new Response(500)
        ]);

        $this->throw();
        $this->fluctuations();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_networking_error_occurs_and_throw_is_true()
    {
        $this->expectException(RequestException::class);

        $this->client = $this->mock([
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        $this->throw();
        $this->fluctuations();
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_returned_rates_are_empty()
    {
        $this->client = $this->successMock((object)[]);

        $this->assertNull($this->fluctuations());
    }

    /**
     * @test
     */
    public function dynamic_method_call_adds_to_query_param_if_method_is_available()
    {
        $this->client = $this->successMock();

        $result = $this->fluctuations();

        $this->assertEquals(['2021-06-10' => ['USD' => 6.0]], $result);
    }

    /**
     * @test
     */
    public function dynamic_method_call_fails_if_method_is_not_available()
    {
        $this->expectException(\Exception::class);
        $this->client = $this->successMock();

        (new CurrencyFluctuations('2021-05-04', '2021-05-06', $this->client))
            ->shouldFail()
            ->get();
    }
}