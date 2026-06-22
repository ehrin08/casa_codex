<?php

namespace Tests\Unit;

use App\Models\CustomerReview;
use App\Services\CustomerReviewSentimentClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CustomerReviewSentimentClassifierTest extends TestCase
{
    private CustomerReviewSentimentClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classifier = new CustomerReviewSentimentClassifier;
    }

    #[DataProvider('ratingProvider')]
    public function test_rating_provides_the_base_sentiment(int $rating, string $expected): void
    {
        $this->assertSame($expected, $this->classifier->classify($rating, null));
    }

    public static function ratingProvider(): array
    {
        return [
            'high rating' => [5, CustomerReview::SENTIMENT_POSITIVE],
            'middle rating' => [3, CustomerReview::SENTIMENT_NEUTRAL],
            'low rating' => [1, CustomerReview::SENTIMENT_NEGATIVE],
        ];
    }

    public function test_positive_keywords_strengthen_a_middle_rating(): void
    {
        $this->assertSame(
            CustomerReview::SENTIMENT_POSITIVE,
            $this->classifier->classify(3, 'The service was excellent, relaxing, and friendly.'),
        );
    }

    public function test_negative_keywords_strengthen_a_middle_rating(): void
    {
        $this->assertSame(
            CustomerReview::SENTIMENT_NEGATIVE,
            $this->classifier->classify(3, 'The room was dirty and the appointment was late.'),
        );
    }

    public function test_high_rating_with_negative_language_is_neutral(): void
    {
        $this->assertSame(
            CustomerReview::SENTIMENT_NEUTRAL,
            $this->classifier->classify(5, 'The session started late.'),
        );
    }

    public function test_low_rating_with_positive_language_is_neutral(): void
    {
        $this->assertSame(
            CustomerReview::SENTIMENT_NEUTRAL,
            $this->classifier->classify(1, 'The therapist was friendly.'),
        );
    }

    public function test_negated_positive_phrase_is_not_counted_as_positive(): void
    {
        $this->assertSame(
            CustomerReview::SENTIMENT_NEGATIVE,
            $this->classifier->classify(3, 'I was not satisfied and the service was not good.'),
        );
    }
}
