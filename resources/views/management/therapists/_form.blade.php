@php
    $isModal = $isModal ?? false;
    $useOld = $useOld ?? true;
    $formIdPrefix = $formIdPrefix ?? 'therapist-form';
    $formMethod = $formMethod ?? ($therapist->exists ? 'PUT' : 'POST');
    $formAction = $formAction ?? ($therapist->exists ? route('management.therapists.update', $therapist) : route('management.therapists.store'));
    $submitLabel = $submitLabel ?? ($therapist->exists ? 'Save changes' : 'Create therapist');
    $modalKey = $modalKey ?? null;
    $recordAction = $recordAction ?? $formAction;
    $fieldValue = fn (string $name, mixed $value = null) => $useOld ? old($name, $value) : $value;
    $createsAccount = filter_var($fieldValue('create_account', false), FILTER_VALIDATE_BOOLEAN);
@endphp

<form method="POST" action="{{ $formAction }}" @if ($isModal) data-record-form @endif class="{{ $isModal ? 'space-y-8 p-5 sm:p-6' : 'spa-panel mx-auto max-w-4xl space-y-8 p-6 sm:p-8' }}">
    @csrf
    @if (strtoupper($formMethod) !== 'POST') @method($formMethod) @endif
    @if ($modalKey)<input type="hidden" name="_modal" value="{{ $modalKey }}">@endif
    @if ($isModal)<input type="hidden" name="_record_action" value="{{ $recordAction }}">@endif

    <div><h3 class="spa-section-title">Profile details</h3><p class="mt-1 text-sm text-cocoa-500">Account links are optional, while the care profile remains available for scheduling.</p></div>

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form.select name="user_id" label="Link to Existing User" :id="$formIdPrefix.'-user'" :use-old="$useOld" hint="Only therapist-role accounts without another profile may be linked." wrapper-class="sm:col-span-2">
            <option value="">No linked account</option>
            @foreach ($users as $user)
                @php($linkedProfileId = $user->therapistProfile?->id)
                <option value="{{ $user->id }}" data-linked-record="{{ $linkedProfileId }}" @disabled($isModal && $linkedProfileId && $linkedProfileId !== $therapist->id) @selected((string) $fieldValue('user_id', $therapist->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }}){{ $linkedProfileId && $linkedProfileId !== $therapist->id ? ' - linked' : '' }}</option>
            @endforeach
        </x-form.select>
        <x-form.input name="first_name" label="First name" :id="$formIdPrefix.'-first-name'" :value="$therapist->first_name" :use-old="$useOld" required />
        <x-form.input name="last_name" label="Last name" :id="$formIdPrefix.'-last-name'" :value="$therapist->last_name" :use-old="$useOld" />
        <x-form.input name="employee_code" label="Employee code" :id="$formIdPrefix.'-employee-code'" :value="$therapist->employee_code" :use-old="$useOld" />
        <x-form.input name="specialty" label="Specialty" :id="$formIdPrefix.'-specialty'" :value="$therapist->specialty" :use-old="$useOld" />
        <x-form.input name="email" label="Email" :id="$formIdPrefix.'-email'" type="email" :value="$therapist->email" :use-old="$useOld" hint="Required when creating a therapist account." />
        <x-form.input name="phone" label="Phone" :id="$formIdPrefix.'-phone'" :value="$therapist->phone" :use-old="$useOld" />
        <x-form.input name="commission_rate" label="Commission rate (%)" :id="$formIdPrefix.'-commission-rate'" type="number" :value="$therapist->commission_rate ?? 0" :use-old="$useOld" min="0" max="100" step="0.01" required />
        <x-form.select name="status" label="Status" :id="$formIdPrefix.'-status'" :use-old="$useOld" required><option value="active" @selected($fieldValue('status', $therapist->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected($fieldValue('status', $therapist->status) === 'inactive')>Inactive</option></x-form.select>
        <x-form.input name="hired_at" label="Hire date" :id="$formIdPrefix.'-hired-at'" type="date" :value="$therapist->hired_at?->format('Y-m-d')" :use-old="$useOld" />
        @unless ($therapist->user_id)
            <div class="rounded-2xl border border-cream-200 bg-cream-50 p-4 sm:col-span-2">
                <div class="flex items-start gap-3">
                    <input id="{{ $formIdPrefix }}-create-account" name="create_account" type="checkbox" value="1" @checked($createsAccount) class="mt-1 size-4 rounded border-cream-300 text-sage-700">
                    <div>
                        <label for="{{ $formIdPrefix }}-create-account" class="font-semibold text-cocoa-900">Create Therapist Account</label>
                        <p class="mt-1 text-sm leading-6 text-cocoa-500">This account will be used to access Casa Paraiso. Leave unchecked for a profile-only therapist record or use the existing-user selector above.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <x-form.input name="account_password" label="Account password" :id="$formIdPrefix.'-account-password'" type="password" :use-old="$useOld" autocomplete="new-password" hint="Required only when creating an account." />
                    <x-form.input name="account_password_confirmation" label="Confirm account password" :id="$formIdPrefix.'-account-password-confirmation'" type="password" :use-old="$useOld" autocomplete="new-password" />
                </div>
            </div>
        @endunless
        <x-form.textarea name="notes" label="Notes" :id="$formIdPrefix.'-notes'" :value="$therapist->notes" :use-old="$useOld" rows="4" wrapper-class="sm:col-span-2" />
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end">
        @if ($isModal)<x-button type="button" variant="secondary" data-modal-close>Cancel</x-button>@else<x-button :href="route('management.therapists.index')" variant="secondary">Cancel</x-button>@endif
        <x-button type="submit">{{ $submitLabel }}</x-button>
    </div>
</form>
