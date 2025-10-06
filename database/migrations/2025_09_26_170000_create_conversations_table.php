<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("conversations", function (Blueprint $t) {
            $t->id();
            $t->string("name")->nullable(); // для групп
            $t->boolean("is_group")->default(false);
            $t->timestamps();
        });

        Schema::create("conversation_user", function (Blueprint $t) {
            $t->unsignedBigInteger("conversation_id");
            $t->unsignedBigInteger("user_id");
            $t->primary(["conversation_id","user_id"]);
            $t->foreign("conversation_id")->references("id")->on("conversations")->onDelete("cascade");
            $t->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
        });

        Schema::create("messages", function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger("conversation_id");
            $t->unsignedBigInteger("user_id");
            $t->text("body");
            $t->timestamps();

            $t->foreign("conversation_id")->references("id")->on("conversations")->onDelete("cascade");
            $t->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $t->index(["conversation_id","created_at"]);
        });
    }
    public function down(): void {
        Schema::dropIfExists("messages");
        Schema::dropIfExists("conversation_user");
        Schema::dropIfExists("conversations");
    }
};