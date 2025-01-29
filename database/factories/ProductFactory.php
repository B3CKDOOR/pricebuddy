<?php

namespace Database\Factories;

use App\Enums\Statuses;
use App\Models\Product;
use App\Models\Store;
use App\Models\Url;
use App\Models\User;
use App\Services\ScrapeUrl;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'image' => $this->faker->imageUrl(),
            'status' => Statuses::Published->value,
            'notify_price' => $this->faker->randomFloat(2, 10, 100),
            'notify_percent' => $this->faker->randomFloat(2, 10, 100),
            'favourite' => $this->faker->boolean,
            'only_official' => $this->faker->boolean,
            'price_cache' => [],
            'user_id' => User::factory(),
        ];
    }

    public function addUrlWithPrices(string $url, array $prices): self
    {
        return $this->afterCreating(function (Product $product) use ($url, $prices) {
            $store = ScrapeUrl::new($url)->getStore() ?? Store::factory()->forUrl($url)->createOne();

            /** @var Url $url */
            $url = $product->urls()->create([
                'url' => $url,
                'store_id' => $store->id,
            ]);

            foreach ($prices as $idx => $price) {
                $url->prices()->create([
                    'price' => $price,
                    'store_id' => $store->id,
                    'created_at' => Carbon::now()->subDays(count($prices) - $idx)->setTime(6, 0)->toDateTimeString(),
                ]);
            }

            $product->updatePriceCache();
        });
    }

    public function addUrlsAndPrices(int $urlCount = 3, int $priceCount = 3): self
    {
        return $this->afterCreating(function (Product $product) use ($urlCount, $priceCount) {
            $price = $this->faker->randomFloat(2, 90, 150);

            // Create Urls.
            for ($u = 0; $u < $urlCount; $u++) {
                $store = Store::factory()->create();

                /** @var Url $url */
                $url = $product->urls()->create([
                    'url' => $this->faker->url,
                    'store_id' => $store->id,
                ]);

                // Create prices.
                $mutableDate = Carbon::now()->toMutable();
                for ($p = 0; $p < $priceCount; $p++) {
                    $url->prices()->create([
                        'price' => self::generateRandomPriceVariation($price),
                        'store_id' => $store->id,
                        'created_at' => $mutableDate->subDay()->toDateTimeString(),
                    ]);
                }
            }
        });
    }

    public static function generateRandomPriceVariation(float $price, float $variationMax = 20): float
    {
        return $price + rand(($variationMax * -1), $variationMax);
    }

    public function setStatus(Statuses $status): self
    {
        return $this->state(fn () => ['status' => $status->value]);
    }
}
