<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            // Optional linking
            $table->unsignedBigInteger('deal_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('contact_id')->nullable()->index();

            $table->unsignedBigInteger('owner_user_id')->nullable()->index();

            $table->string('quote_number', 50);
            $table->string('status', 20)->default('draft'); // draft|sent|accepted|declined|expired

            $table->date('issued_at')->nullable();
            $table->date('valid_until')->nullable();

            $table->string('currency', 10)->default('ZAR');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(15); // SA default
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'quote_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};


