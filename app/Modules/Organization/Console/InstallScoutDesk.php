<?php

namespace App\Modules\Organization\Console;

use App\Modules\Audit\Actions\RecordAudit;
use App\Modules\Identity\Actions\IssueAccountToken;
use App\Modules\Identity\Enums\AccountTokenPurpose;
use App\Modules\Identity\Models\AccountToken;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Enums\Role;
use App\Modules\Organization\Models\Group;
use App\Modules\Organization\Models\Membership;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InstallScoutDesk extends Command
{
    protected $signature = 'scoutdesk:install
                            {--group= : Name of the scout group (asked for if no group exists yet)}
                            {--email= : Email address of the first group secretary}';

    protected $description = 'First-run setup: create the group and print a one-time setup link for the group secretary';

    public function handle(IssueAccountToken $issueToken, RecordAudit $audit): int
    {
        $activeSecretaryExists = Membership::query()
            ->where('role', Role::GroupSecretary->value)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->exists();

        if ($activeSecretaryExists) {
            $this->error('Setup is already complete: an active group secretary exists. Nothing was changed.');

            return self::FAILURE;
        }

        if (Group::query()->count() > 1) {
            $this->error('More than one group exists. This command only handles first-run setup. Nothing was changed.');

            return self::FAILURE;
        }

        $group = Group::query()->first();

        if ($group !== null && $this->option('group') !== null) {
            $this->line("Using the existing group \"{$group->name}\"; --group is ignored.");
        }

        $groupName = $group !== null
            ? $group->name
            : trim((string) ($this->option('group') ?? $this->ask('Group name')));

        $email = Str::lower(trim((string) ($this->option('email') ?? $this->ask('Secretary email'))));

        $validator = Validator::make(
            ['group' => $groupName, 'email' => $email],
            [
                'group' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }
            $this->error('Nothing was changed.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('A user with this email address already exists. Nothing was changed.');

            return self::FAILURE;
        }

        [$group, $issued, $reissued] = DB::transaction(function () use ($group, $groupName, $email, $issueToken, $audit) {
            $groupCreated = false;

            if ($group === null) {
                $group = Group::create(['name' => $groupName]);
                $groupCreated = true;
            }

            // Only one pending secretary invitation may exist at a time.
            $reissued = false;
            $pending = AccountToken::query()
                ->usable()
                ->where('purpose', AccountTokenPurpose::Invitation->value)
                ->where('role', Role::GroupSecretary->value)
                ->where('group_id', $group->id)
                ->get();

            foreach ($pending as $oldToken) {
                $oldToken->revoke();
                $reissued = true;
            }

            $issued = $issueToken->handle(
                AccountTokenPurpose::Invitation,
                $email,
                role: Role::GroupSecretary->value,
                groupId: $group->id,
            );

            $audit->handle(
                'setup.install_run',
                subject: $group,
                groupId: $group->id,
                metadata: ['group_created' => $groupCreated, 'reissued' => $reissued],
            );

            return [$group, $issued, $reissued];
        });

        // Task 2.7 registers this route: GET /invitations/{token}
        $link = url('/invitations/'.$issued->plainToken);

        $this->newLine();
        $this->info('ScoutDesk setup');
        $this->line("Group: {$group->name}");
        $this->line("Secretary: {$email}");
        if ($reissued) {
            $this->line('The previous setup link was revoked.');
        }
        $this->newLine();
        $this->line('One-time setup link (valid for '.config('scoutdesk.invitation_lifetime_hours').' hours, shown only once):');
        $this->line($link);
        $this->newLine();
        $this->warn('Send this link only to the secretary. If it is lost, run this command again to issue a new one.');

        return self::SUCCESS;
    }
}