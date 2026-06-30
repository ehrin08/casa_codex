@php
    $isModal = $isModal ?? false;
    $useOld = $useOld ?? true;
    $formIdPrefix = $formIdPrefix ?? 'customer-form';
    $formMethod = $formMethod ?? ($customer->exists ? 'PUT' : 'POST');
    $formAction = $formAction ?? ($customer->exists ? route('management.customers.update', $customer) : route('management.customers.store'));
    $submitLabel = $submitLabel ?? ($customer->exists ? 'Save changes' : 'Create customer');
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

    <div><h3 class="spa-section-title">Guest details</h3><p class="mt-1 text-sm text-cocoa-500">Contact and profile information helps the team prepare a more thoughtful visit.</p></div>

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form.select name="user_id" label="Link to Existing User" :id="$formIdPrefix.'-user'" :use-old="$useOld" hint="Only customer-role accounts without another profile may be linked." wrapper-class="sm:col-span-2">
            <option value="">No linked account (walk-in)</option>
            @foreach ($users as $user)
                @php($linkedProfileId = $user->customerProfile?->id)
                <option value="{{ $user->id }}" data-linked-record="{{ $linkedProfileId }}" @disabled($isModal && $linkedProfileId && $linkedProfileId !== $customer->id) @selected((string) $fieldValue('user_id', $customer->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }}){{ $linkedProfileId && $linkedProfileId !== $customer->id ? ' - linked' : '' }}</option>
            @endforeach
        </x-form.select>
        <x-form.input name="first_name" label="First name" :id="$formIdPrefix.'-first-name'" :value="$customer->first_name" :use-old="$useOld" required />
        <x-form.input name="last_name" label="Last name" :id="$formIdPrefix.'-last-name'" :value="$customer->last_name" :use-old="$useOld" />
        <x-form.input name="email" label="Email" :id="$formIdPrefix.'-email'" type="email" :value="$customer->email" :use-old="$useOld" hint="Required when creating a customer account." />
        <x-form.input name="phone" label="Phone" :id="$formIdPrefix.'-phone'" :value="$customer->phone" :use-old="$useOld" />
        <x-form.input name="birth_date" label="Birth date" :id="$formIdPrefix.'-birth-date'" type="date" :value="$customer->birth_date?->format('Y-m-d')" :use-old="$useOld" :max="now()->format('Y-m-d')" />
        <x-form.select name="gender" label="Gender" :id="$formIdPrefix.'-gender'" :use-old="$useOld"><option value="">Prefer not to specify</option><option value="female" @selected($fieldValue('gender', $customer->gender) === 'female')>Female</option><option value="male" @selected($fieldValue('gender', $customer->gender) === 'male')>Male</option><option value="other" @selected($fieldValue('gender', $customer->gender) === 'other')>Other</option><option value="prefer_not_to_say" @selected($fieldValue('gender', $customer->gender) === 'prefer_not_to_say')>Prefer not to say</option></x-form.select>
        <x-form.select name="is_active" label="Status" :id="$formIdPrefix.'-status'" :use-old="$useOld" required><option value="1" @selected((string) $fieldValue('is_active', (int) ($customer->exists ? $customer->is_active : true)) === '1')>Active</option><option value="0" @selected((string) $fieldValue('is_active', (int) $customer->is_active) === '0')>Inactive</option></x-form.select>
        @unless ($customer->user_id)
            <div class="rounded-2xl border border-cream-200 bg-cream-50 p-4 sm:col-span-2">
                <div class="flex items-start gap-3">
                    <input id="{{ $formIdPrefix }}-create-account" name="create_account" type="checkbox" value="1" @checked($createsAccount) class="mt-1 size-4 rounded border-cream-300 text-sage-700">
                    <div>
                        <label for="{{ $formIdPrefix }}-create-account" class="font-semibold text-cocoa-900">Create Customer Account</label>
                        <p class="mt-1 text-sm leading-6 text-cocoa-500">This account will be used to access Casa Paraiso. Leave unchecked for a walk-in profile or use the existing-user selector above.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <x-form.input name="account_password" label="Account password" :id="$formIdPrefix.'-account-password'" type="password" :use-old="$useOld" autocomplete="new-password" hint="Required only when creating an account." />
                    <x-form.input name="account_password_confirmation" label="Confirm account password" :id="$formIdPrefix.'-account-password-confirmation'" type="password" :use-old="$useOld" autocomplete="new-password" />
                </div>
            </div>
        @endunless
        <x-form.textarea name="address" label="Address" :id="$formIdPrefix.'-address'" :value="$customer->address" :use-old="$useOld" rows="3" wrapper-class="sm:col-span-2" />
        <x-form.textarea name="notes" label="Notes" :id="$formIdPrefix.'-notes'" :value="$customer->notes" :use-old="$useOld" rows="4" wrapper-class="sm:col-span-2" />
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end">
        @if ($isModal)<x-button type="button" variant="secondary" data-modal-close>Cancel</x-button>@else<x-button :href="route('management.customers.index')" variant="secondary">Cancel</x-button>@endif
        <x-button type="submit">{{ $submitLabel }}</x-button>
    </div>
</form>
