<?php

namespace FlagPal\FlagPal\Support;

class Raffle
{
    private null|int|string $alwaysDraw = null;

    /**
     * @param  array<int|string,int>  $pool
     */
    public function draw(array $pool): int|string
    {
        if (empty($pool)) {
            throw new \InvalidArgumentException('The raffle pool must not be empty');
        }

        return $this->alwaysDraw ?? $this->pickWinner($pool);
    }

    public function alwaysDraw(?string $contestant): self
    {
        $this->alwaysDraw = $contestant;

        return $this;
    }

    protected function pickWinner(array $pool): int|string
    {
        krsort($pool);
        $totalWeight = array_reduce($pool, function ($carry, $item) {
            if ($item <= 0) {
                throw new \InvalidArgumentException('All weights must always be a positive integer');
            }

            return $carry + $item;
        }, 0);

        $selection = random_int(1, $totalWeight);
        $winner = array_key_first($pool);

        $count = 0;
        foreach ($pool as $contestant => $weight) {
            $count += $weight;
            if ($count >= $selection) {
                $winner = $contestant;
                break;
            }
        }

        return $winner;
    }
}
