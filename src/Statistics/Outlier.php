<?php
namespace MathPHP\Statistics;

use MathPHP\Exception;
use MathPHP\Functions\Map\Single;
use MathPHP\Probability\Distribution\Continuous\StandardNormal;
use MathPHP\Probability\Distribution\Continuous\StudentT;
use MathPHP\Statistics\Average;
use MathPHP\Statistics\Descriptive;

/**
 * Tests for outliers in data
 *  - Grubbs Test
 */
class Outlier
{
    const TWO_SIDED       = 'two';
    const ONE_SIDED_LOWER = 'lower';
    const ONE_SIDED_UPPER = 'upper';

    /**
     * The Grubbs' Statistic (G) of a series of data
     *
     * G is the largest z-score for a set of data
     * The statistic can be calculated, looking at only the maximum value ("upper")
     * the minimum value ("lower"), or the data point with the largest residual ("two")
     *
     * Two-sided Grubbs' test statistic - largest difference from the mean is an outlier
     *
     *     max❘Yᵢ − μ❘
     * G = ----------
     *         σ
     *
     * One-sided Grubbs' test statistic - minimum value is an outlier
     *
     *     μ - Ymin
     * G = --------
     *        σ
     *
     * One-sided Grubbs' test statistic - maximum value is an outlier
     *
     *     Ymax - μ
     * G = --------
     *        σ
     *
     * @param float[] $data
     * @param string  $typeOfTest ("upper" "lower", or "two")
     *
     * @return float
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     * @throws Exception\BadParameterException if the type of test is not valid
     */
    public static function grubbsStatistic(array $data, string $typeOfTest = 'two'): float
    {
        self::validateTestType($typeOfTest);

        $μ = Average::mean($data);
        $σ = Descriptive::standardDeviation($data);

        if ($typeOfTest === self::TWO_SIDED) {
            $max❘Yᵢ − μ❘ = max(Single::abs(Single::subtract($data, $μ)));
            $G = $max❘Yᵢ − μ❘ / $σ;
        }

        if ($typeOfTest === self::ONE_SIDED_LOWER) {
            $yMin = min($data);
            $G = ($μ - $yMin) / $σ;
        }

        if ($typeOfTest === self::ONE_SIDED_UPPER) {
            $yMax = max($data);
            $G = ($yMax - $μ) / $σ;
        }

        return $G;
    }
    
    /**
     * The critical Grubbs Value
     * https://en.wikipedia.org/wiki/Grubbs%27_test_for_outliers
     * https://www.itl.nist.gov/div898/handbook/eda/section3/eda35h1.htm
     *
     * The critical Grubbs' value is used to determine if a value in a set of data is likely to be an outlier.
     *
     *                                ___________
     *                   (n - 1)     /    T²
     * Critical value =  ------- \  / ----------
     *                     √n     \/  n - 2 + T²
     *
     * T = Critical value of the t distribution with (N-2) degrees of freedom and a significance level of α/(2N)
     *     For the one-sided tests, replace α/(2N) with α/N.
     *
     * @param float $𝛼 Significance Level
     * @param int   $n Size of the data set
     * @param int   $tails (1 or 2) one or two-tailed test
     *
     * @return float
     *
     * @throws Exception\BadParameterException
     */
    public static function criticalGrubbs(float $𝛼, int $n, int $tails = 2): float
    {
        if ($tails < 1 || $tails > 2) {
            throw new Exception\BadParameterException('Tails must be 1 or 2');
        }

        $studentT = new StudentT($n - 2);

        $T = $tails === 1
            ? $studentT->inverse($𝛼 / $n)
            : $studentT->inverse($𝛼 / (2 * $n));

        return (($n - 1) / sqrt($n)) * sqrt($T ** 2 / ($n - 2 + $T ** 2));
    }

    /**
     * Validate the type of test is two sided, or one sided lower or upper
     *
     * @param string $typeOfTest
     *
     * @throws Exception\BadParameterException
     */
    private static function validateTestType(string $typeOfTest)
    {
        if (!in_array($typeOfTest, [self::TWO_SIDED, self::ONE_SIDED_LOWER, self::ONE_SIDED_UPPER])) {
            throw new Exception\BadParameterException("{$typeOfTest} is not a valid Grubbs test");
        }
    }
}
