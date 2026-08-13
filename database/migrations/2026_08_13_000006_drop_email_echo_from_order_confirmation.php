<?php

use App\Support\OrderEmailTemplates;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The confirmation used to read "...and sent a confirmation to <address>", which
     * only tells customers what they already know. Shortened to "We have received
     * your order." Templates reworded in the admin panel are left as they are.
     */
    public function up(): void
    {
        OrderEmailTemplates::syncUnmodified();
    }

    public function down(): void
    {
        // syncUnmodified() is forward-only; the previous wording is recoverable
        // by editing the template in the admin panel.
    }
};
