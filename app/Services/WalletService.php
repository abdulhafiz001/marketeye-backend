<?php

namespace App\Services;

use App\Mail\AirtimeClaimAdminMail;
use App\Models\Admin;
use App\Models\AirtimeClaim;
use App\Models\PriceSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function awardForVerifiedSubmission(PriceSubmission $submission): void
    {
        if ($submission->status !== PriceSubmission::STATUS_APPROVED) {
            return;
        }

        if (! $submission->user_id) {
            return;
        }

        DB::transaction(function () use ($submission): void {
            $locked = PriceSubmission::query()->whereKey($submission->id)->lockForUpdate()->first();
            if (! $locked || $locked->wallet_rewarded) {
                return;
            }

            User::query()->whereKey($locked->user_id)->increment('wallet_balance', 1);
            $locked->wallet_rewarded = true;
            $locked->save();
        });
    }

    /**
     * Claim the full wallet balance (must be >= 200). Balance resets to 0 until reject refunds.
     */
    public function claim(User $user, string $phone): AirtimeClaim
    {
        return DB::transaction(function () use ($user, $phone) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $balance = (int) $locked->wallet_balance;

            if ($balance < AirtimeClaim::MIN_CLAIM_AMOUNT) {
                throw ValidationException::withMessages([
                    'wallet' => 'You need at least ₦'.AirtimeClaim::MIN_CLAIM_AMOUNT.' to claim airtime.',
                ]);
            }

            $pending = AirtimeClaim::query()
                ->where('user_id', $locked->id)
                ->where('status', AirtimeClaim::STATUS_PENDING)
                ->exists();

            if ($pending) {
                throw ValidationException::withMessages([
                    'wallet' => 'You already have a pending airtime claim.',
                ]);
            }

            $amount = $balance;
            $locked->wallet_balance = 0;
            $locked->save();

            $claim = AirtimeClaim::query()->create([
                'user_id' => $locked->id,
                'amount' => $amount,
                'phone' => $phone,
                'status' => AirtimeClaim::STATUS_PENDING,
                'claimed_at' => now(),
            ]);

            $this->notifyAdmins($claim->load('user'));

            return $claim;
        });
    }

    public function markPaid(AirtimeClaim $claim, Admin|User $actor, ?string $note = null): AirtimeClaim
    {
        if ($claim->status !== AirtimeClaim::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'claim' => 'Only pending claims can be marked paid.',
            ]);
        }

        $paidBy = $actor instanceof User ? $actor->id : null;
        $suffix = $actor instanceof Admin ? ' (admin: '.$actor->email.')' : '';

        $claim->update([
            'status' => AirtimeClaim::STATUS_PAID,
            'paid_at' => now(),
            'paid_by' => $paidBy,
            'admin_note' => ($note ?? 'Airtime sent.').$suffix,
        ]);

        return $claim->fresh(['user', 'payer']);
    }

    public function reject(AirtimeClaim $claim, Admin|User $actor, ?string $note = null): AirtimeClaim
    {
        if ($claim->status !== AirtimeClaim::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'claim' => 'Only pending claims can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($claim, $actor, $note) {
            $locked = AirtimeClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== AirtimeClaim::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'claim' => 'Only pending claims can be rejected.',
                ]);
            }

            User::query()->whereKey($locked->user_id)->increment('wallet_balance', (int) $locked->amount);

            $paidBy = $actor instanceof User ? $actor->id : null;
            $suffix = $actor instanceof Admin ? ' (admin: '.$actor->email.')' : '';

            $locked->update([
                'status' => AirtimeClaim::STATUS_REJECTED,
                'paid_by' => $paidBy,
                'admin_note' => ($note ?? 'Rejected by admin.').$suffix,
            ]);

            return $locked->fresh(['user', 'payer']);
        });
    }

    private function notifyAdmins(AirtimeClaim $claim): void
    {
        $admins = Admin::query()->whereNotNull('email')->get();

        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send(new AirtimeClaimAdminMail($claim));
            } catch (\Throwable) {
                // Mail may be misconfigured in local/demo; claim still created.
            }
        }
    }
}
