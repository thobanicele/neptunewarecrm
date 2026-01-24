<?php
// database/migrations/xxxx_xx_xx_create_company_addresses_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('company_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->string('type', 20)->default('billing'); // billing|shipping|other
            $table->string('label')->nullable();            // "Head Office", "Warehouse"

            $table->string('attention')->nullable();
            $table->string('phone')->nullable();

            $table->string('line1')->nullable();
            $table->string('line2')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();

            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('subdivision_id')->nullable()->constrained('country_subdivisions')->nullOnDelete();

            // fallback text if subdivisions are not available for a country
            $table->string('subdivision_text')->nullable();

            $table->boolean('is_default_billing')->default(false);
            $table->boolean('is_default_shipping')->default(false);

            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('company_addresses');
    }
};

