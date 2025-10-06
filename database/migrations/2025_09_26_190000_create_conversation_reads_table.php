<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create("conversation_reads", function (Blueprint $t) {
            $t->unsignedBigInteger("conversation_id");
            $t->unsignedBigInteger("user_id");
            $t->unsignedBigInteger("last_read_message_id")->nullable();
            $t->timestamp("read_at")->nullable();
            $t->primary(["conversation_id","user_id"]);
            $t->foreign("conversation_id")->references("id")->on("conversations")->onDelete("cascade");
            $t->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $t->foreign("last_read_message_id")->references("id")->on("messages")->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists("conversation_reads");
    }
};