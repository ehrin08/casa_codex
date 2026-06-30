@php
    $isModal = $isModal ?? false;
    $formIdPrefix = $formIdPrefix ?? 'walk-in';
    $modalKey = $modalKey ?? null;
    $selectedCustomerType = old('customer_type', old('customer_profile_id') ? 'existing' : 'guest');
@endphp

<form
    method="POST"
    action="{{ route('management.walk-ins.store') }}"
    class="{{ $isModal ? 'space-y-7 p-5 sm:p-6' : 'spa-panel p-6 sm:p-8' }}"
    data-appointment-booking-form
    data-slots-url="{{ route('management.walk-ins.slots') }}"
>
    @csrf
    @if ($modalKey)<input type="hidden" name="_modal" value="{{ $modalKey }}">@endif

    @unless ($isModal)
        <div class="mb-7">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-700">Staff-created appointment</p>
            <h2 class="mt-2 text-xl font-semibold text-cocoa-950">Book a walk-in guest</h2>
            <p class="mt-1 text-sm text-cocoa-500">Create a walk-in appointment for an existing customer or a guest who does not need an online account.</p>
        </div>
    @endunless

    <div class="grid gap-6 sm:grid-cols-2">
        <fieldset class="sm:col-span-2" data-customer-type-group>
            <legend class="text-sm font-semibold text-cocoa-700">Customer Type <span class="text-red-700" aria-hidden="true">*</span></legend>
            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                <label @class(['flex cursor-pointer gap-3 rounded-xl border bg-white p-4 transition', 'border-sage-400 ring-2 ring-sage-100' => $selectedCustomerType === 'existing', 'border-cream-300 hover:border-sage-300' => $selectedCustomerType !== 'existing'])>
                    <input type="radio" name="customer_type" value="existing" class="mt-1" data-customer-type-option @checked($selectedCustomerType === 'existing')>
                    <span>
                        <span class="block font-semibold text-cocoa-900">Existing Customer</span>
                        <span class="mt-1 block text-xs leading-5 text-cocoa-500">Link the appointment to an active customer profile.</span>
                    </span>
                </label>
                <label @class(['flex cursor-pointer gap-3 rounded-xl border bg-white p-4 transition', 'border-sage-400 ring-2 ring-sage-100' => $selectedCustomerType === 'guest', 'border-cream-300 hover:border-sage-300' => $selectedCustomerType !== 'guest'])>
                    <input type="radio" name="customer_type" value="guest" class="mt-1" data-customer-type-option @checked($selectedCustomerType === 'guest')>
                    <span>
                        <span class="block font-semibold text-cocoa-900">Walk-in Guest</span>
                        <span class="mt-1 block text-xs leading-5 text-cocoa-500">Use this for guests who do not need an online account.</span>
                    </span>
                </label>
            </div>
            @error('customer_type')<p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
        </fieldset>

        <div data-customer-type-panel="existing" @class(['sm:col-span-2', 'hidden' => $selectedCustomerType !== 'existing'])>
            <x-form.select :id="$formIdPrefix.'-customer'" name="customer_profile_id" label="Customer" :required="$selectedCustomerType === 'existing'" hint="Only active customer profiles are available." data-customer-type-required="existing">
                <option value="">Select a customer</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_profile_id') === (string) $customer->id)>{{ trim($customer->first_name.' '.$customer->last_name) }}{{ $customer->phone ? ' - '.$customer->phone : '' }}</option>
                @endforeach
            </x-form.select>
        </div>

        <div data-customer-type-panel="guest" @class(['grid gap-6 sm:col-span-2 sm:grid-cols-2', 'hidden' => $selectedCustomerType !== 'guest'])>
            <x-form.input :id="$formIdPrefix.'-guest-name'" name="guest_name" label="Guest Name" :required="$selectedCustomerType === 'guest'" data-customer-type-required="guest" />
            <x-form.input :id="$formIdPrefix.'-guest-contact'" name="guest_contact" label="Guest Contact Number" maxlength="30" hint="Optional." />
        </div>

        <x-form.select :id="$formIdPrefix.'-service'" name="service_id" label="Service" required wrapper-class="sm:col-span-2" hint="Select a spa service to determine the required appointment duration.">
            <option value="">Select a service</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" data-duration="{{ $service->duration_minutes }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }} - {{ $service->duration_minutes }} minutes - PHP {{ number_format((float) $service->price, 2) }}</option>
            @endforeach
        </x-form.select>

        <x-form.select :id="$formIdPrefix.'-therapist'" name="therapist_profile_id" label="Therapist" required wrapper-class="sm:col-span-2" hint="Choose a specific therapist.">
            <option value="">Select a therapist</option>
            @foreach ($therapists as $therapist)
                <option value="{{ $therapist->id }}" @selected((string) old('therapist_profile_id') === (string) $therapist->id)>{{ trim($therapist->first_name.' '.$therapist->last_name) }}{{ $therapist->specialty ? ' - '.$therapist->specialty : '' }}</option>
            @endforeach
        </x-form.select>

        <x-form.input :id="$formIdPrefix.'-date'" name="appointment_date" label="Appointment date" type="date" :min="now()->toDateString()" required hint="Defaults to today for walk-ins, but future dates can also be scheduled." />

        <fieldset class="sm:col-span-2" data-slot-picker>
            <legend class="text-sm font-semibold text-cocoa-700">Available times <span class="text-red-700" aria-hidden="true">*</span></legend>
            <input type="hidden" name="appointment_time" value="{{ old('appointment_time') }}" data-selected-slot>
            <p class="mt-1.5 text-xs leading-5 text-cocoa-500">Today and future dates are supported. Past times and conflicting appointments are excluded automatically.</p>
            <div class="mt-3 rounded-2xl border border-dashed border-cream-300 bg-cream-50/70 px-5 py-6 text-center text-sm text-cocoa-600" data-slot-status role="status" aria-live="polite">Select all booking details to view available times.</div>
            <div class="mt-3 hidden grid-cols-2 gap-3 sm:grid-cols-3" data-slot-options></div>
            <div class="mt-3 hidden rounded-2xl border border-dashed border-cream-300 bg-cream-50/70 px-5 py-7 text-center" data-slot-empty>
                <p class="font-semibold text-cocoa-900">No available times for this date.</p>
                <p class="mt-1 text-sm text-cocoa-500">Choose another date or therapist.</p>
            </div>
            @error('appointment_time')<p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
        </fieldset>

        <x-form.textarea :id="$formIdPrefix.'-notes'" name="notes" label="Walk-in Notes" rows="4" maxlength="2000" placeholder="Add preferences or details that will help the therapist prepare." wrapper-class="sm:col-span-2" hint="Optional." />
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end {{ $isModal ? '' : 'mt-7' }}">
        @if ($isModal)
            <x-button type="button" variant="secondary" data-modal-close>Cancel</x-button>
        @else
            <x-button :href="route('management.index')" variant="secondary">Cancel</x-button>
        @endif
        <x-button type="submit" data-booking-submit disabled>Book Walk-in Appointment</x-button>
    </div>
</form>
