<?php

namespace App\Services\Prediksi;

class HoltWinters
{
    /**
     * @param  array<int, float|int>  $series
     * @return array{forecast:float,lower:float,upper:float,mape:float,alpha:float,beta:float,gamma:float,seasonal:bool,trend:float,sigma:float}
     */
    public function forecast(array $series, int $horizon = 1, int $seasonLength = 12, float $phi = 0.9): array
    {
        $series = array_values(array_map('floatval', $series));
        $n = count($series);

        if ($n === 0) {
            return $this->emptyResult();
        }

        if ($n < 3) {
            $mean = $this->mean($series);

            return [
                'forecast' => round($mean, 2),
                'lower' => round($mean, 2),
                'upper' => round($mean, 2),
                'mape' => 0.0,
                'alpha' => 0.0,
                'beta' => 0.0,
                'gamma' => 0.0,
                'seasonal' => false,
                'trend' => 0.0,
                'sigma' => 0.0,
            ];
        }

        $seasonal = $n >= $seasonLength * 2;
        $best = null;

        foreach ([0.1, 0.3, 0.5, 0.7, 0.9] as $alpha) {
            foreach ([0.1, 0.3, 0.5, 0.7] as $beta) {
                foreach ($seasonal ? [0.1, 0.3, 0.5] : [0.0] as $gamma) {
                    $fit = $this->fit($series, $alpha, $beta, $gamma, $seasonal, $seasonLength);

                    if ($best === null || $fit['mape'] < $best['mape']) {
                        $best = $fit + ['alpha' => $alpha, 'beta' => $beta, 'gamma' => $gamma];
                    }
                }
            }
        }

        $damped = 0.0;
        $power = 1.0;
        for ($i = 1; $i <= $horizon; $i++) {
            $power *= $phi;
            $damped += $power;
        }

        $season = $seasonal ? $best['seasonals'][$n % $seasonLength] : 0.0;
        $forecast = $best['level'] + $damped * $best['trend'] + $season;
        $sigma = $this->stddev($best['residuals']);
        $margin = 1.96 * $sigma * sqrt($horizon);
        $mape = $best['mape'];

        return [
            'forecast' => round(max(0, $forecast), 2),
            'lower' => round(max(0, $forecast - $margin), 2),
            'upper' => round($forecast + $margin, 2),
            'mape' => round($mape, 2),
            'alpha' => $best['alpha'],
            'beta' => $best['beta'],
            'gamma' => $best['gamma'],
            'seasonal' => $seasonal,
            'trend' => round($best['trend'], 4),
            'sigma' => round($sigma, 4),
        ];
    }

    private function fit(array $series, float $alpha, float $beta, float $gamma, bool $seasonal, int $m): array
    {
        $n = count($series);
        $level = $series[0];
        $trend = $n > 1 ? $series[1] - $series[0] : 0.0;
        $seasonals = array_fill(0, $m, 0.0);

        if ($seasonal) {
            for ($i = 0; $i < min($m, $n); $i++) {
                $seasonals[$i] = $series[$i] - $level;
            }
        }

        $residuals = [];
        $actuals = [];
        $predictions = [];

        for ($i = 0; $i < $n; $i++) {
            $index = $i % $m;
            $season = $seasonal ? $seasonals[$index] : 0.0;
            $prediction = $level + $trend + $season;

            if ($i > 0) {
                $actuals[] = $series[$i];
                $predictions[] = $prediction;
                $residuals[] = $series[$i] - $prediction;
            }

            $previousLevel = $level;
            $level = $alpha * ($series[$i] - $season) + (1 - $alpha) * ($level + $trend);
            $trend = $beta * ($level - $previousLevel) + (1 - $beta) * $trend;

            if ($seasonal) {
                $seasonals[$index] = $gamma * ($series[$i] - $level) + (1 - $gamma) * $season;
            }
        }

        return [
            'level' => $level,
            'trend' => $trend,
            'seasonals' => $seasonals,
            'residuals' => $residuals,
            'mape' => $this->mape($actuals, $predictions),
        ];
    }

    private function mape(array $actuals, array $predictions): float
    {
        $sum = 0.0;
        $count = 0;

        foreach ($actuals as $index => $actual) {
            if ($actual == 0.0) {
                continue;
            }

            $sum += abs($actual - $predictions[$index]) / abs($actual);
            $count++;
        }

        return $count ? ($sum / $count) * 100 : 0.0;
    }

    private function mean(array $values): float
    {
        return count($values) ? array_sum($values) / count($values) : 0.0;
    }

    private function stddev(array $values): float
    {
        $count = count($values);

        if ($count < 2) {
            return 0.0;
        }

        $mean = $this->mean($values);
        $sum = 0.0;

        foreach ($values as $value) {
            $sum += ($value - $mean) ** 2;
        }

        return sqrt($sum / ($count - 1));
    }

    private function emptyResult(): array
    {
        return [
            'forecast' => 0.0,
            'lower' => 0.0,
            'upper' => 0.0,
            'mape' => 0.0,
            'alpha' => 0.0,
            'beta' => 0.0,
            'gamma' => 0.0,
            'seasonal' => false,
            'trend' => 0.0,
            'sigma' => 0.0,
        ];
    }
}
