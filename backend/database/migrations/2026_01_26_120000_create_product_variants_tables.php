<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Attributes Table (Global definitions like "Color", "Size")
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Color"
            $table->string('code')->nullable(); // e.g., "color"
            $table->string('type')->default('select'); // select, radio, color
            $table->timestamps();
        });

        // 2. Attribute Values (Specific possibilities like "Red", "Small")
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value'); // e.g., "Red"
            $table->string('code')->nullable(); // e.g., "red"
            $table->string('color_hex')->nullable(); // Only for color type
            $table->timestamps();
        });

        // 3. Product Templates (The "Parent" product)
        Schema::create('product_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('uom', 20)->default('pcs');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Update Products Table to link to Template
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_template_id')->nullable()->after('id')->constrained('product_templates')->nullOnDelete();
            // Note: existing simple products will have null template_id
        });

        // 5. Template Attributes Lines (Defining "This Shirt has Size and Color")
        // This links a Template to an Attribute.
        Schema::create('product_template_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // 6. Template Attribute Values (Defining "This Shirt's Size allows S and M")
        // Connects the Line (Template+Attribute) to specific allowed Values
        Schema::create('product_template_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_template_attribute_id')->constrained('product_template_attributes')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // 7. Variant Attributes (Defining "This specific SKU #123 is size S")
        // Links the concrete Product (SKU) to the Value.
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // The SKU
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete(); // The Value (e.g., Red)
            // We might also want to link back to the template_attribute_line for stricter integrity, but value_id is enough for now.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_attributes');
        Schema::dropIfExists('product_template_attribute_values');
        Schema::dropIfExists('product_template_attributes');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_template_id']);
            $table->dropColumn('product_template_id');
        });

        Schema::dropIfExists('product_templates');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};
