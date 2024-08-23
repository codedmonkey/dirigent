<?php

namespace CodedMonkey\Conductor\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

abstract class AbstractDownloads
{
    private ?array $data = null;

    /**
     * @var array<int|numeric-string, int> Data is keyed by date in form of YYYYMMDD and as such the keys are technically seen as ints by PHP
     */
    #[ORM\Column(type: 'json')]
    protected array $historicalData = [];

    /**
     * @var array<int|numeric-string, int> Data is keyed by date in form of YYYYMMDD and as such the keys are technically seen as ints by PHP
     */
    #[ORM\Column(type: 'json')]
    protected array $recentData = [];

    #[ORM\Column(type: 'integer')]
    protected int $total = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $mergedAt = null;

    /**
     * @param array<int|numeric-string, int> $data
     */
    public function setData(array $data): void
    {
        $this->historicalData = $data;
        $this->recentData = [];

        $this->mergedAt = new \DateTimeImmutable();

        $this->data = null;
    }

    /**
     * @param numeric-string $key
     */
    public function setDataPoint(string $key, int $value): void
    {
        $this->recentData[$key] = $value;

        $this->data = null;
    }

    /**
     * @return array<int, int> Key is "YYYYMMDD" which means it always gets converted to an int by php
     */
    public function getData(): array
    {
        if (null === $this->data) {
            $this->data = $this->doMergeData();
        }

        return $this->data;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getMergedAt(): ?\DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function mergeData(): void
    {
        $data = $this->doMergeData();

        $this->historicalData = $data;
        $this->recentData = [];

        $this->mergedAt = new \DateTimeImmutable();

        $this->data = null;
    }

    protected function doMergeData(): array
    {
        $data = $this->historicalData;

        foreach ($this->recentData as $dataKey => $dataPoint) {
            $data[$dataKey] ??= 0;
            $data[$dataKey] += $dataPoint;
        }

        return $data;
    }

    /**
     * @param numeric-string $key
     */
    public function increase(string $key): void
    {
        $this->recentData[$key] ??= 0;
        $this->recentData[$key]++;

        $this->total++;

        $this->data = null;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
