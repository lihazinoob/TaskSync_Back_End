<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // To whom the notification whould be sent
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // For which project the notification is sent
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            // Indicates the notification type
            $table->string('type');
            // The Message of the Notification
            $table->text('message');
            // Indicator if the message has been read or not
            $table->boolean('read')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};
