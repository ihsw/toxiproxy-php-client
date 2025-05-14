<?php

declare(strict_types=1);

namespace Ihsw\Toxiproxy\Tests;

use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\ToxicExistsException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;
use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\StreamDirections;
use Ihsw\Toxiproxy\Tests\Test\BaseTestCase;
use Ihsw\Toxiproxy\Toxic;
use Ihsw\Toxiproxy\ToxicTypes;

class ProxyTest extends BaseTestCase
{
    public function testCreate(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $attr = [
            'latency' => 1000,
            'jitter' => 50,
        ];
        $toxic = $proxy->create(ToxicTypes::LATENCY->value, StreamDirections::UPSTREAM->value, 1.0, $attr);
        self::assertEquals(ToxicTypes::LATENCY->value, $toxic->getType());
        self::assertEquals(StreamDirections::UPSTREAM->value, $toxic->getStream());
        self::assertEquals(1.0, $toxic->getToxicity());
        self::assertEquals($attr, $toxic->getAttributes());
    }

    public function testCreateDuplicate(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $this->createToxic(
            $proxy,
            ToxicTypes::LATENCY->value,
            [
                'latency' => 1000,
                'jitter' => 500,
            ],
        );

        $this->expectException(ToxicExistsException::class);
        $this->createToxic(
            $proxy,
            ToxicTypes::LATENCY->value,
            [
                'latency' => 1000,
                'jitter' => 500,
            ],
        );
    }

    public function testCreatedUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy(
            [
                self::httpResponseFactory(418, ''),
            ],
        );

        $this->expectException(UnexpectedStatusCodeException::class);
        $this->createToxic(new Proxy($toxiproxy, ''), '', []);
    }

    public function testGetAll(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic(
            $proxy,
            ToxicTypes::LATENCY->value,
            [
                'latency' => 1000,
                'jitter' => 500,
            ],
        );

        $toxics = $proxy->getAll();
        self::assertEquals($toxic, $toxics[0]);
    }

    public function testGetAllUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy(
            [
                self::httpResponseFactory(418, ''),
            ],
        );

        $this->expectException(UnexpectedStatusCodeException::class);
        $proxy = new Proxy($toxiproxy, '');
        $proxy->getAll();
    }

    public function testGet(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic(
            $proxy,
            ToxicTypes::LATENCY->value,
            [
                'latency' => 1000,
                'jitter' => 500,
            ],
        );

        self::assertEquals($toxic, $proxy->get($toxic->getName()));
    }

    public function testGetNotFound(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $toxic = $proxy->get('non-existent');
        self::assertNull($toxic);
    }

    public function testGetUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy(
            [
                self::httpResponseFactory(418, ''),
            ],
        );

        $this->expectException(UnexpectedStatusCodeException::class);
        $proxy = new Proxy($toxiproxy, '');
        $proxy->get('');
    }

    public function testUpdate(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic(
            $proxy,
            ToxicTypes::LATENCY->value,
            [
                'latency' => 1000,
                'jitter' => 500,
            ],
        );

        $toxic->setAttributes(['latency' => 2000, 'jitter' => 1000])
            ->setToxicity(0.5);
        $updatedToxic = $proxy->update($toxic);
        self::assertEquals($updatedToxic, $toxic);
    }

    public function testUpdateNotFound(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic(
            $proxy,
            ToxicTypes::LATENCY->value,
            [
                'latency' => 1000,
                'jitter' => 500,
            ],
        );

        $proxy->delete($toxic);
        $this->expectException(NotFoundException::class);
        $proxy->update($toxic);
    }

    public function testUpdateUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy(
            [
                self::httpResponseFactory(418, ''),
            ],
        );

        $this->expectException(UnexpectedStatusCodeException::class);
        $proxy = new Proxy($toxiproxy, '');
        $proxy->update(new Toxic($proxy, '', '', ''));
    }

    public function testDelete(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic(
            $proxy,
            ToxicTypes::LATENCY->value,
            [
                'latency' => 1000,
                'jitter' => 500,
            ],
        );
        self::assertCount(1, $toxiproxy->getAll());
        self::assertCount(1, $proxy->getAll());
        $proxy->delete($toxic);
        self::assertCount(1, $toxiproxy->getAll());
        self::assertCount(0, $proxy->getAll());
    }

    public function testDeleteNotFound(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic(
            $proxy,
            ToxicTypes::LATENCY->value,
            [
                'latency' => 1000,
                'jitter' => 500,
            ],
        );

        $proxy->delete($toxic);
        $this->expectException(NotFoundException::class);
        $proxy->delete($toxic);
    }

    public function testDeleteUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy(
            [
                self::httpResponseFactory(418, ''),
            ],
        );

        $this->expectException(UnexpectedStatusCodeException::class);
        $proxy = new Proxy($toxiproxy, '');
        $proxy->delete(new Toxic($proxy, '', '', ''));
    }
}
