<?php

namespace App\Services;

use App\Models\CustomerReview;

class CustomerReviewSentimentClassifier
{
    private const POSITIVE_KEYWORDS = [
        'excellent',
        'great',
        'relaxing',
        'clean',
        'friendly',
        'professional',
        'satisfied',
        'recommend',
        'amazing',
        'good',
        'smooth',
        'comfortable',
    ];

    private const NEGATIVE_KEYWORDS = [
        'bad',
        'poor',
        'late',
        'dirty',
        'rude',
        'painful',
        'disappointed',
        'expensive',
        'unprofessional',
        'not satisfied',
        'not good',
        'uncomfortable',
    ];

    public function classify(int $rating, ?string $comment): string
    {
        $normalizedComment = preg_replace('/\s+/u', ' ', mb_strtolower(trim($comment ?? ''))) ?? '';
        $positiveSignals = $this->countSignals($normalizedComment, self::POSITIVE_KEYWORDS, true);
        $negativeSignals = $this->countSignals($normalizedComment, self::NEGATIVE_KEYWORDS);

        if ($rating >= 4) {
            return $negativeSignals > 0
                ? CustomerReview::SENTIMENT_NEUTRAL
                : CustomerReview::SENTIMENT_POSITIVE;
        }

        if ($rating <= 2) {
            return $positiveSignals > 0
                ? CustomerReview::SENTIMENT_NEUTRAL
                : CustomerReview::SENTIMENT_NEGATIVE;
        }

        if ($positiveSignals > $negativeSignals) {
            return CustomerReview::SENTIMENT_POSITIVE;
        }

        if ($negativeSignals > $positiveSignals) {
            return CustomerReview::SENTIMENT_NEGATIVE;
        }

        return CustomerReview::SENTIMENT_NEUTRAL;
    }

    /** @param list<string> $keywords */
    private function countSignals(string $comment, array $keywords, bool $ignoreNegated = false): int
    {
        return count(array_filter($keywords, function (string $keyword) use ($comment, $ignoreNegated): bool {
            if ($ignoreNegated && str_contains($comment, 'not '.$keyword)) {
                return false;
            }

            return preg_match('/(?<![\pL\pN])'.preg_quote($keyword, '/').'(?![\pL\pN])/u', $comment) === 1;
        }));
    }
}
