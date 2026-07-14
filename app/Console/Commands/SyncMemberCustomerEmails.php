<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\Customer\MemberEmailSync;
use Illuminate\Console\Command;

class SyncMemberCustomerEmails extends Command
{
    protected $signature = 'customers:sync-member-emails
                            {--dry-run : 更新せず不一致件数だけ表示する}';

    protected $description = '会員（user_id 付き）の customers.email を users.email に揃える';

    public function handle(MemberEmailSync $memberEmailSync): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $mismatched = Customer::query()
            ->whereNotNull('user_id')
            ->whereHas('user')
            ->with('user')
            ->get()
            ->filter(function (Customer $customer) use ($memberEmailSync): bool {
                $userEmail = $memberEmailSync->normalize((string) $customer->user->email);
                $customerEmail = $memberEmailSync->normalize((string) $customer->email);

                return $customerEmail !== $userEmail;
            });

        $count = $mismatched->count();

        if ($count === 0) {
            $this->info('不一致はありません。');

            return self::SUCCESS;
        }

        $this->info("不一致: {$count} 件");

        if ($dryRun) {
            foreach ($mismatched as $customer) {
                $this->line(sprintf(
                    '  customer_id=%d user_id=%d customers.email=%s users.email=%s',
                    $customer->id,
                    $customer->user_id,
                    $customer->email,
                    $customer->user->email,
                ));
            }

            $this->comment('dry-run のため更新していません。');

            return self::SUCCESS;
        }

        $updated = 0;

        foreach ($mismatched as $customer) {
            $memberEmailSync->ensureLinkedCustomer($customer->user);
            $updated++;
        }

        $this->info("更新した顧客: {$updated} 件");

        return self::SUCCESS;
    }
}
