<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTourpayInvoicesTable extends Migration
{
    public function up()
    {
        Schema::create('bc_tourpay_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_number', 30)->unique();
            $table->string('type', 20)->default('invoice'); // invoice|quotation
            $table->string('status', 30)->default('draft'); // draft|sent|paid|cancelled|accepted|expired

            // Client details (free-typed or from user)
            $table->bigInteger('client_user_id')->nullable(); // if linked to a portal user
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone', 40)->nullable();
            $table->string('client_country', 80)->nullable();
            $table->text('client_address')->nullable();

            // Invoice content
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('currency', 10)->default('ZAR');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_rate', 6, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // Dates
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('valid_days')->nullable(); // quotations

            // Template & branding
            $table->tinyInteger('template')->default(1);
            $table->text('notes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->json('banking_details')->nullable();

            // Public payable link token
            $table->string('pay_token', 64)->unique()->nullable();

            $table->bigInteger('author_id')->nullable();
            $table->bigInteger('create_user')->nullable();
            $table->bigInteger('update_user')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bc_tourpay_invoices');
    }
}
