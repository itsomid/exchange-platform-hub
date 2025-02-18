<?php

namespace Database\Seeders;

use App\Enums\TicketTypeEnum;
use App\Models\Admin;
use App\Models\Deposit;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables
        TicketReply::truncate();
        Ticket::truncate();

        // Enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::whereIn('id',[4,5,6])->get();

        foreach ($users as $user) {
            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateTicketNumber(),
                'user_id' => $user->id,
                'subject' => 'مشکل در برداشت وجه',
                'status' => 'open',
                'priority' => 'high',
                'ticketable_id' => 2,
                'ticketable_type' =>  Deposit::class,
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'repliable_id' => $user->id,
                'repliable_type' => User::class,
                'message' => 'سلام، من در برداشت وجه از حساب خود مشکل دارم. لطفاً راهنمایی کنید.',
                'is_private' => false,
                'is_seen' => false,
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'repliable_id' => 2, // Assuming admin ID is 1
                'repliable_type' => Admin::class,
                'message' => 'سلام، لطفاً اطلاعات تراکنش خود را ارسال کنید تا بررسی شود.',
                'is_private' => false,
                'is_seen' => true,
            ]);

            ////////
            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateTicketNumber(),
                'user_id' => $user->id,
                'subject' => 'مشکل در برداشت وجه',
                'status' => 'open',
                'priority' => 'high',
                'ticketable_id' => 1,
                'ticketable_type' =>  Withdrawal::class,
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'repliable_id' => $user->id,
                'repliable_type' => User::class,
                'message' => 'سلام، من در برداشت وجه از حساب خود مشکل دارم. لطفاً راهنمایی کنید.',
                'is_private' => false,
                'is_seen' => false,
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'repliable_id' => 2, // Assuming admin ID is 1
                'repliable_type' => Admin::class,
                'message' => 'سلام، لطفاً اطلاعات تراکنش خود را ارسال کنید تا بررسی شود.',
                'is_private' => false,
                'is_seen' => true,
            ]);




            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateTicketNumber(),
                'user_id' => $user->id,
                'subject' => 'مشکل در برداشت وجه',
                'status' => 'open',
                'priority' => 'high',
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'repliable_id' => $user->id,
                'repliable_type' => User::class,
                'message' => 'سلام، من در برداشت وجه از حساب خود مشکل دارم. لطفاً راهنمایی کنید.',
                'is_private' => false,
                'is_seen' => false,
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'repliable_id' => 2, // Assuming admin ID is 1
                'repliable_type' => Admin::class,
                'message' => 'سلام، لطفاً اطلاعات تراکنش خود را ارسال کنید تا بررسی شود.',
                'is_private' => false,
                'is_seen' => true,
            ]);
        }
    }

}
