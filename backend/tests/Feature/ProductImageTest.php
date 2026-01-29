<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = explode(',', config('app.api_keys'))[0];
        $this->headers = ['X-API-Key' => $apiKey];

        $this->user = User::factory()->create();
        $token = $this->user->createToken('test')->plainTextToken;
        $this->headers['Authorization'] = 'Bearer ' . $token;

        Storage::fake('public');
    }

    public function test_can_upload_product_image()
    {
        $file = UploadedFile::fake()->image('product.jpg');

        $payload = [
            'name' => 'Image Product',
            'code' => 'IMG-001',
            'type' => 'finished',
            'tracking' => 'none',
            'uom' => 'unit',
            'image' => $file,
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/products', $payload);

        $response->assertStatus(201);

        $productId = $response->json('data.id');
        $product = Product::find($productId);

        $this->assertNotNull($product->image_url);
        $this->assertTrue(Storage::disk('public')->exists($product->image_url));
    }

    public function test_can_update_product_image()
    {
        $product = Product::create([
            'name' => 'Original Product',
            'code' => 'ORIG-001',
            'type' => 'raw',
            'uom' => 'kg',
            'organization_id' => $this->user->organization_id
        ]);

        $newFile = UploadedFile::fake()->image('new-image.jpg');

        $response = $this->withHeaders($this->headers)->postJson("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'image' => $newFile,
            'name' => 'Updated Product',
        ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertNotNull($product->image_url);
        $this->assertTrue(Storage::disk('public')->exists($product->image_url));
    }

    public function test_validates_image_file()
    {
        $payload = [
            'name' => 'Bad File Product',
            'code' => 'BAD-001',
            'type' => 'finished',
            'tracking' => 'none',
            'uom' => 'unit',
            'image' => 'not-an-image-string',
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/products', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }
}
