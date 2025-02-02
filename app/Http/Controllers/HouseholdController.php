<?php

namespace App\Http\Controllers;

use App\Mail\InvitationToHouseholds;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\User;
use App\Services\UBMS_Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use App\Mail\InvitationStatusNotification;

class HouseholdController extends Controller
{

    private UBMS_Helper $ubms_helper;

    function __construct(UBMS_Helper $ubms_helper)
    {
        $this->ubms_helper = $ubms_helper;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $household = new Household();
        $user_id = \Auth::id();
        $household->name = $request->name;
        try {
            $household->save();
            $household->users()->attach([
                $user_id => ['created_at' => now()],
            ]);

            /*DB::table('household_user')->insert([
                'user_id' => $household->user_id,
                'household_id' => $household->id,
            ]);*/
            return ['success' => true, 'new_id' => $household->id];
        } catch (\Exception $e) {
            $message = str_contains($e->getMessage(), 'ix_households_name_unique_per_user') ? 'This household already exists!' : 'There was an error while processing your request';
            return ['success' => false, 'message' => $message];
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Household $household)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Household $household)
    {
        $household = Household::find($request->id);
        if (empty($household) || !HouseholdController::has_access(\Auth::id(), $household->id)) {
            return ['success' => false, 'message' => 'Household not found'];
        }

        $household->name = $request->name;
        $household->save();
        return ['success' => true];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $household_id = $request->id;

        DB::table('bills')
            ->where('household_id', '=', $household_id)
            ->whereNull('company_id')
            ->delete();

        try {
            DB::table('households')->where('id', '=', $household_id)->delete();
            return ['success' => true];
        } catch (\Exception $e) {
            $message = str_contains($e->getMessage(), 'fk_bills_households') ? 'Cannot delete a non-empty household' : 'There was an error while processing your request';
            return ['success' => false, 'message' => $message];
        }
    }

    public static function has_access($user_id, $household_id)
    {
        return DB::table('household_user')
            ->where('user_id', $user_id)
            ->where('household_id', $household_id)
            ->exists();
    }

    public function get_user_households()
    {
        return ['households' => Household::get_user_households()];
    }

    public function get_households_mappings()
    {
        $user_id = \Auth::id();

        $mappings = DB::select("select u.id user_id,
       u.name,
       u.email,
       (select group_concat(hu.household_id)
        from household_user hu
                 join (select hu.household_id
                       from household_user hu
                       where hu.user_id = $user_id) as user_hhs
                      on user_hhs.household_id = hu.household_id
        where hu.user_id = u.id
        order by hu.household_id) as hh_ids
from users u
where u.id in
      (select hu.user_id
       from household_user hu
                join (select hu.household_id
                      from household_user hu
                      where hu.user_id = $user_id) as user_hhs
                     on user_hhs.household_id = hu.household_id)
  and u.id != $user_id");

        return ['mappings' => $mappings, 'invitations' => $this->get_invitations_for_inviter($user_id)];
    }

    public function get_invitations_for_inviter($user_id = null)
    {
        $user_id ??= \Auth::id();

        return HouseholdInvitation::where('invited_by', $user_id)->with(['invitee:id,name'])->get();
    }

    public function get_invitations_for_invitee($user_id = null)
    {
        $user_id ??= \Auth::id();

        $invitations = HouseholdInvitation::where('invitee_id', $user_id)
            ->with(['inviter:id,name,email'])
            ->select(['id', 'household_ids', 'invited_by',]) //invited_by brings up inviter:id,name,email fields too
            ->get();

        //[{"id":6,"uuid":"ae2e3611-e4ce-4c8c-a4f2-cd6d10d7a2ff","household_ids":"20","invited_by":108,"inviter":{"id":108,"name":"Eve Vega","email":"gusy@mailinator.com"}}]


        if ($invitations->isEmpty()) {
            return [];
        }

        $concatenated_household_ids = collect($invitations)->pluck('household_ids')->implode(',');

        $hhs = DB::select("select h.id, h.name from households h where h.id in ($concatenated_household_ids)");

        foreach ($invitations as $invitation) {
            $household_ids = explode(',', $invitation->household_ids ?? '');
            $household_names = collect($hhs)->filter(function ($hh) use ($household_ids) {
                return in_array($hh->id, $household_ids);
            })->pluck('name')->toArray();

            $invitation->hh_names = $household_names;
        }

        return $invitations;
    }

    public function update_households_mappings()
    {
        $saved_mappings = $this->get_households_mappings()['mappings'];

        $saved_mappings_by_email = [];
        foreach ($saved_mappings as $mapping) $saved_mappings_by_email[$mapping->email] = $mapping;

        $new_mappings = request('mappings');
        $new_mappings_by_email = [];
        foreach ($new_mappings as $mapping) $new_mappings_by_email[$mapping['email']] = $mapping;


        foreach ($new_mappings_by_email as $invitee_email => $new_mapping) {
            if (!$new_mapping['hh_ids']) continue;

            $new_hh_ids = $this->ubms_helper->strToSortedIntArray($new_mapping['hh_ids']);
            $invitee = DB::table('users')->where('email', $invitee_email)->first();

            if ($saved_mapping = $saved_mappings_by_email[$invitee_email] ?? null) {
                unset($saved_mappings_by_email[$invitee_email]); //so mark as processed

                //update
                //detect changed hh ids
                $saved_hh_ids = $this->ubms_helper->strToSortedIntArray($saved_mapping->hh_ids);
                $changes = $this->ubms_helper->detectChanges($saved_hh_ids, $new_hh_ids);

                $this->invite(\Auth::id(), $invitee_email, $invitee?->id ?? null, $changes['added']);

                /*              foreach ($changes['added'] as $new_hh_id)
                                  //TODO: send emails for newly assigned households?
                                  DB::table('household_user')->insert([
                                      'user_id' => $saved_mapping->user_id,
                                      'household_id' => $new_hh_id,
                                      'created_at' => now(),
                                      'updated_at' => now(),
                                  ]);*/

                foreach ($changes['removed'] as $new_hh_id)
                    DB::table('household_user')
                        ->where('user_id', $saved_mapping->user_id)
                        ->where('household_id', $new_hh_id)
                        ->delete();

            } else {
                //add
                $this->invite(\Auth::id(), $invitee_email, $invitee?->id ?? null, $new_hh_ids);
            }
        }

        //delete
        foreach ($saved_mappings_by_email as $mapping) {
            $saved_hh_ids = $this->ubms_helper->strToSortedIntArray($mapping->hh_ids);

            DB::table('household_user')
                ->where('user_id', $mapping->user_id)
                ->whereIn('household_id', $saved_hh_ids)
                ->delete();
        }

        return ['success' => true,
            'mappings' => $this->get_households_mappings()['mappings'],
            'invitations' => $this->get_invitations_for_inviter()];

    }

    private function invite(int $invited_by, string $invitee_email, int|null $invitee_id, array $hh_ids)
    {
        if (empty($hh_ids)) return;

        $hh_ids = collect($hh_ids)->sort()->values()->toArray();
        $hh_ids_str = implode(',', $hh_ids);

        //remove previously sent invitations if any
        DB::statement("DELETE FROM household_invitations
            WHERE invited_by = ?
            AND invitee_email = ?
            AND ? like CONCAT('%', household_ids, '%')", [
            $invited_by,
            $invitee_email,
            $hh_ids_str
        ]);

        $uuid = \Str::uuid();
        $hh_invitation = HouseholdInvitation::create([
            'uuid' => $uuid,
            'invitee_email' => $invitee_email,
            'invitee_id' => $invitee_id,
            'invited_by' => $invited_by,
            'household_ids' => implode(',', $hh_ids),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($invitee_id) {
            $locale = ($invitee = DB::table('users')->find($invitee_id))?->language;
            $invitee_name = $invitee?->name;
        }

        $locale ??= 'hr';

        $inviter_name = DB::table('users')->find($invited_by)->name;


        $household_names = DB::table('households')
            ->whereIn('id', $hh_ids)
            ->pluck('name');

        try {
            \Mail::to($invitee_email)
                ->send(new InvitationToHouseholds($locale, $uuid, $inviter_name, $invitee_name ?? null, $household_names));

            $hh_invitation->update([
                'invitation_status' => 'sent',
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send household invitation email', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'invitee_email' => $invitee_email,
                'invited_by' => $invited_by,
                'household_ids' => $hh_ids
            ]);


// Update the status of the household invitation
            $hh_invitation->update([
                'invitation_status' => 'error',
            ]);
        }

    }

    private function send_invitation_status_to_hh_owner($invitation, $status)
    {
        $data = DB::table('users')->where('id', $invitation->invited_by)->select('email', 'name')->first();
        $householdOwnerEmail = $data->email;
        $ownerName = $data->name;

        $locale = DB::table('users')->where('id', $invitation->invited_by)->value('language') ?? 'hr';
        $householdIds = explode(',', $invitation->household_ids);
        $householdNames = DB::table('households')->whereIn('id', $householdIds)->pluck('name')->toArray() ?? 'unknown household';
        $householdNames = implode(', ', $householdNames);

        Mail::to($householdOwnerEmail)->send(new InvitationStatusNotification(
            $locale,
            $ownerName,
            $householdNames,
            $status // 'approved' or 'declined'
        ));
    }

    public function accept($uuid)
    {
        $invitation = HouseholdInvitation::where('uuid', $uuid)->with(['invitee:id,name'])->first();

        if (!$invitation) {
            return Inertia::render('Information', [
                'title' => 'Error',
                'status' => 'error',
                'text' => 'Invitation not found.',
            ]);
        }

        //check if the current user is not the invitee
        if (\Auth::id() !== $invitation->invitee_id) {
            \Auth::logout();
        }

        $households = $invitation->households();

        $invitee = User::where('email', $invitation->invitee_email)->first();

        if (!$invitee) {

            $invitation->update([
                'invitation_status' => 'accepted',
            ]);

            $this->send_invitation_status_to_hh_owner($invitation, 'approved');

//            session()->flash('no_email_verification_required', 'true');

            return redirect()->route('register')->with([
                'error' => 'The invitee is not a registered user.',
                'email' => $invitation->invitee_email,
            ]);
        }

        //the invitee is an existing user
        Household::add_user_households($invitee->id, $invitation->household_ids);

        // Delete the invitation from the database
        $invitation->delete();

        ob_start();
        ?>
        <p>Invitation accepted successfully.</p>
        <p>You've got access to the following households:
        <ul>
            <?php
            foreach ($households as $household) : ?>
                <li><?= $household->name ?></li>
            <?php endforeach; ?>
        </ul>
        </p>

        <a href="<?= route('dashboard') ?>">Go to dashboard</a>
        <?php
        $html = ob_get_clean();

        return Inertia::render('Information', [
            'title' => 'Success',
            'status' => 'success',
            'text' => $html,
        ]);

    }

    public function decline($uuid)
    {

        $invitation = HouseholdInvitation::where('uuid', $uuid)->first();

        if (!$invitation) {
            return Inertia::render('Information', [
                'title' => 'Error',
                'status' => 'error',
                'text' => 'Invitation not found.',
            ]);
        }

        $this->send_invitation_status_to_hh_owner($invitation, 'declined');

        $invitation->delete();

        // Update the invitation_status to 'declined'
        /*$invitation->update([
            'invitation_status' => 'declined',
        ]);*/

        return Inertia::render('Information', [
            'title' => 'Success',
            'status' => 'success',
            'text' => 'Invitation has been declined.',
        ]);
    }

    public function accept_ajax($id)
    {
        $invitation = HouseholdInvitation::where('id', $id)->with(['invitee:id,name'])->first();
        if (!$invitation) {
            return ['success' => false, 'message' => 'Invitation not found'];
        }

        Household::add_user_households(auth()->id(), $invitation->household_ids);

        $this->send_invitation_status_to_hh_owner($invitation, 'approved');

        $invitation->delete();

        return ['success' => true, 'invitations' => $this->get_invitations_for_invitee(), 'user_households' => Household::get_user_households(),];
    }

    public function decline_ajax($id)
    {
        $invitation = HouseholdInvitation::where('id', $id)->with(['invitee:id,name'])->first();
        if (!$invitation) {
            return ['success' => false, 'message' => 'Invitation not found'];
        }

        $this->send_invitation_status_to_hh_owner($invitation, 'declined');

        $invitation->delete();

        return ['success' => true, 'invitations' => $this->get_invitations_for_invitee(),];
    }

    public function delete_invitation($id)
    {

        $invitation = HouseholdInvitation::find($id);

        $invitation?->delete();

        return ['success' => true,
            'mappings' => $this->get_households_mappings()['mappings'],
            'invitations' => $this->get_invitations_for_inviter()];
    }
}
