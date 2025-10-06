<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("message_attachments", function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger("message_id");
            $t->string("key");               // s3 object key
            $t->string("mime")->nullable();
            $t->unsignedBigInteger("size")->nullable();
            $t->string("name")->nullable();  // original file name
            $t->unsignedInteger("width")->nullable();
            $t->unsignedInteger("height")->nullable();
            $t->timestamps();

            $t->foreign("message_id")->references("id")->on("messages")->onDelete("cascade");
            $t->index(["message_id"]);
        });
    }

    public function down(): void {
        Schema::dropIfExists("message_attachments");
    }
};